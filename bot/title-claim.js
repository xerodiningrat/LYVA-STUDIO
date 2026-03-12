import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  ActionRowBuilder,
  AttachmentBuilder,
  ButtonBuilder,
  ButtonStyle,
  EmbedBuilder,
  ModalBuilder,
  PermissionFlagsBits,
  StringSelectMenuBuilder,
  StringSelectMenuOptionBuilder,
  TextInputBuilder,
  TextInputStyle,
} from 'discord.js';
import { createLaravelVipTitleCheckout, createLaravelVipTitleClaim, fetchLaravelVipTitleClaims, fetchLaravelVipTitleMaps } from './laravel-api.js';

const TITLE_PANEL_BUTTON_PREFIX = 'title_claim_open:';
const TITLE_BUY_BUTTON_PREFIX = 'title_buy_open:';
const TITLE_SCRIPT_BUTTON_PREFIX = 'title_claim_script:';
const TITLE_CLAIM_MODAL_PREFIX = 'title_claim_modal:';
const TITLE_BUY_MODAL_PREFIX = 'title_buy_modal:';
const TITLE_SETUP_SELECT_PREFIX = 'title_setup_map_select:';
const MODULE_DIR = path.dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = path.resolve(MODULE_DIR, '..');
const CLAIMS_PATH = path.join(PROJECT_ROOT, 'data', 'vip-title-claims.json');
const RESERVED_TERMS = ['admin', 'administrator', 'dev', 'developer', 'owner', 'mod', 'moderator', 'staff'];
const PROFANITY_TERMS = ['anjing', 'babi', 'bangsat', 'kontol', 'memek', 'ngentot', 'goblok', 'tolol', 'jancok', 'fuck', 'bitch'];
const USER_LOOKUP_CACHE_TTL_MS = 5 * 60 * 1000;
const VIP_CHECK_CACHE_TTL_MS = 2 * 60 * 1000;
const userLookupCache = new Map();
const vipOwnershipCache = new Map();

function canManage(interaction) {
  return (
    interaction.memberPermissions?.has(PermissionFlagsBits.Administrator) ||
    interaction.memberPermissions?.has(PermissionFlagsBits.ManageGuild)
  );
}

function normalizeText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '');
}

function sanitizeTitle(value) {
  return String(value || '')
    .replace(/\r/g, ' ')
    .replace(/\n/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 28);
}

function validateTitle(value) {
  const title = sanitizeTitle(value);
  if (!title) {
    return { ok: false, reason: 'Title wajib diisi.' };
  }

  if (title.length < 3) {
    return { ok: false, reason: 'Title minimal 3 karakter.' };
  }

  const normalized = normalizeText(title);
  const reserved = RESERVED_TERMS.find((term) => normalized.includes(normalizeText(term)));
  if (reserved) {
    return { ok: false, reason: `Title tidak boleh memakai kata seperti "${reserved}".` };
  }

  const profanity = PROFANITY_TERMS.find((term) => normalized.includes(normalizeText(term)));
  if (profanity) {
    return { ok: false, reason: 'Title mengandung kata yang tidak diperbolehkan.' };
  }

  return { ok: true, title };
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function getCached(map, key) {
  const entry = map.get(key);
  if (!entry) {
    return null;
  }

  if (entry.expiresAt <= Date.now()) {
    map.delete(key);
    return null;
  }

  return entry.value;
}

function setCached(map, key, value, ttlMs) {
  map.set(key, {
    value,
    expiresAt: Date.now() + ttlMs,
  });
}

async function fetchWithRetry(url, options = {}, label = 'request') {
  const maxAttempts = 4;

  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    const response = await fetch(url, options);
    if (response.status !== 429) {
      return response;
    }

    if (attempt === maxAttempts) {
      throw new Error(`Roblox sedang rate limit untuk ${label}. Coba lagi sebentar.`);
    }

    const retryAfterHeader = response.headers.get('retry-after');
    const retryAfterSeconds = Number(retryAfterHeader || 0);
    const delayMs = Number.isFinite(retryAfterSeconds) && retryAfterSeconds > 0
      ? retryAfterSeconds * 1000
      : attempt * 1500;

    await sleep(delayMs);
  }

  throw new Error(`Request gagal untuk ${label}.`);
}

async function ensureClaimsFile() {
  await mkdir(path.dirname(CLAIMS_PATH), { recursive: true });
  try {
    await readFile(CLAIMS_PATH, 'utf8');
  } catch {
    await writeFile(CLAIMS_PATH, JSON.stringify({ claims: [] }, null, 2), 'utf8');
  }
}

async function readClaimsStore() {
  await ensureClaimsFile();
  try {
    const raw = await readFile(CLAIMS_PATH, 'utf8');
    return JSON.parse(raw);
  } catch {
    return { claims: [] };
  }
}

async function writeClaimsStore(store) {
  await ensureClaimsFile();
  await writeFile(CLAIMS_PATH, JSON.stringify(store, null, 2), 'utf8');
}

function truncateForComponent(value, maxLength = 100) {
  const text = String(value || '').trim();
  if (text.length <= maxLength) {
    return text;
  }

  return `${text.slice(0, Math.max(0, maxLength - 3))}...`;
}

function buildTitlePanelButtonId(mapKey) {
  return `${TITLE_PANEL_BUTTON_PREFIX}${normalizeMapKey(mapKey)}`;
}

function buildTitleScriptButtonId(mapKey) {
  return `${TITLE_SCRIPT_BUTTON_PREFIX}${normalizeMapKey(mapKey)}`;
}

function buildTitleBuyButtonId(mapKey) {
  return `${TITLE_BUY_BUTTON_PREFIX}${normalizeMapKey(mapKey)}`;
}

function parseTitlePanelButtonId(customId) {
  if (!String(customId || '').startsWith(TITLE_PANEL_BUTTON_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_PANEL_BUTTON_PREFIX.length));
}

function parseTitleScriptButtonId(customId) {
  if (!String(customId || '').startsWith(TITLE_SCRIPT_BUTTON_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_SCRIPT_BUTTON_PREFIX.length));
}

function parseTitleBuyButtonId(customId) {
  if (!String(customId || '').startsWith(TITLE_BUY_BUTTON_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_BUY_BUTTON_PREFIX.length));
}

function buildTitleClaimModalId(mapKey) {
  return `${TITLE_CLAIM_MODAL_PREFIX}${normalizeMapKey(mapKey)}`;
}

function buildTitleBuyModalId(mapKey) {
  return `${TITLE_BUY_MODAL_PREFIX}${normalizeMapKey(mapKey)}`;
}

function parseTitleClaimModalId(customId) {
  if (!String(customId || '').startsWith(TITLE_CLAIM_MODAL_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_CLAIM_MODAL_PREFIX.length));
}

function parseTitleBuyModalId(customId) {
  if (!String(customId || '').startsWith(TITLE_BUY_MODAL_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_BUY_MODAL_PREFIX.length));
}

function buildTitleSetupSelectId(channelId) {
  return `${TITLE_SETUP_SELECT_PREFIX}${channelId}`;
}

function parseTitleSetupSelectChannelId(customId) {
  if (!String(customId || '').startsWith(TITLE_SETUP_SELECT_PREFIX)) {
    return '';
  }

  return String(customId).slice(TITLE_SETUP_SELECT_PREFIX.length);
}

function normalizeMapConfig(rawMap = {}) {
  const mapKey = normalizeMapKey(rawMap.map_key ?? rawMap.mapKey ?? '');
  if (!mapKey) {
    return null;
  }

  const titlePriceIdr = Number(rawMap.title_price_idr ?? rawMap.titlePriceIdr ?? 0);
  const derivedClaimMode = titlePriceIdr > 0
    ? 'duitku'
    : String(rawMap.claim_mode ?? rawMap.claimMode ?? 'vip_gamepass').trim() || 'vip_gamepass';

  return {
    id: rawMap.id ?? null,
    mapKey,
    name: String(rawMap.name || mapKey).trim() || mapKey,
    gamepassId: Number(rawMap.gamepass_id ?? rawMap.gamepassId ?? 0),
    claimMode: derivedClaimMode,
    apiKey: String(rawMap.api_key ?? rawMap.apiKey ?? '').trim(),
    titleSlot: Number(rawMap.title_slot ?? rawMap.titleSlot ?? 0),
    titlePriceIdr,
    paymentExpiryMinutes: Number(rawMap.payment_expiry_minutes ?? rawMap.paymentExpiryMinutes ?? 60),
    buttonLabel: String(rawMap.button_label ?? rawMap.buttonLabel ?? '').trim(),
    placeIds: Array.isArray(rawMap.place_ids ?? rawMap.placeIds)
      ? (rawMap.place_ids ?? rawMap.placeIds).map((item) => String(item).trim()).filter(Boolean)
      : [],
    scriptAccessRoleIds: Array.isArray(rawMap.script_access_role_ids ?? rawMap.scriptAccessRoleIds)
      ? (rawMap.script_access_role_ids ?? rawMap.scriptAccessRoleIds).map((item) => String(item).trim()).filter(Boolean)
      : [],
    isActive: rawMap.is_active ?? rawMap.isActive ?? true,
  };
}

async function loadActiveMapConfigs(config) {
  const response = await fetchLaravelVipTitleMaps(config).catch(() => null);
  if (Array.isArray(response?.items)) {
    return response.items
      .map((item) => normalizeMapConfig(item))
      .filter((item) => item && item.isActive);
  }

  return Object.values(config?.vipTitleMaps || {})
    .map((item) => normalizeMapConfig(item))
    .filter((item) => item);
}

async function resolveMapConfig(config, rawMapKey) {
  const mapKey = normalizeMapKey(rawMapKey);
  if (!mapKey) {
    return null;
  }

  const maps = await loadActiveMapConfigs(config);
  return maps.find((item) => item.mapKey === mapKey) || null;
}

function buildTitlePanelEmbed(mapConfig) {
  const hasPaidOption = mapConfig.titlePriceIdr > 0;

  return new EmbedBuilder()
    .setColor(0xf97316)
    .setTitle('VIP Title Center')
    .setDescription(
      [
        'Panel claim custom title untuk member VIP.',
        '',
        `Panel ini sudah terhubung ke map **${mapConfig.name}** dari dashboard.`,
        'Klik `Claim Title` kalau user sudah punya VIP gamepass.',
        hasPaidOption
          ? `Klik \`Beli Title\` kalau user mau bayar pakai IDR. Harga saat ini **${formatIdr(mapConfig.titlePriceIdr)}**.`
          : 'Kalau harga IDR belum diisi, panel ini hanya pakai flow claim gamepass.',
        'Klik `Script Roblox` kalau admin butuh file yang harus ditaruh di game.',
      ].join('\n'),
    )
    .addFields(
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Claim VIP', value: 'Aktif', inline: true },
      { name: 'Beli IDR', value: hasPaidOption ? formatIdr(mapConfig.titlePriceIdr) : 'Nonaktif', inline: true },
      { name: 'Filter', value: 'Reserved title + profanity diblok', inline: true },
    )
    .setFooter({ text: 'ProjectBotDC | VIP Title Panel' });
}

function formatIdr(value) {
  const amount = Number(value || 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount);
}

function resolveBuyButtonLabel(mapConfig) {
  if (mapConfig.buttonLabel) {
    return truncateForComponent(mapConfig.buttonLabel, 80);
  }

  return 'Beli Title';
}

function canAccessRobloxScript(interaction, mapConfig) {
  if (canManage(interaction)) {
    return true;
  }

  const allowedRoleIds = mapConfig?.scriptAccessRoleIds || [];
  if (allowedRoleIds.length === 0) {
    return false;
  }

  const memberRoles = interaction.member?.roles?.cache;
  if (!memberRoles) {
    return false;
  }

  return allowedRoleIds.some((roleId) => memberRoles.has(roleId));
}

async function resolveRobloxUser(username) {
  const normalizedUsername = String(username || '').trim().toLowerCase();
  const cachedUser = getCached(userLookupCache, normalizedUsername);
  if (cachedUser) {
    return cachedUser;
  }

  const response = await fetchWithRetry('https://users.roblox.com/v1/usernames/users', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      usernames: [username],
      excludeBannedUsers: true,
    }),
  });

  if (!response.ok) {
    throw new Error(`Roblox lookup gagal (${response.status}).`);
  }

  const data = await response.json();
  const user = data?.data?.[0];
  if (!user?.id) {
    return null;
  }

  const resolvedUser = {
    userId: user.id,
    username: user.name,
    displayName: user.displayName,
  };

  setCached(userLookupCache, normalizedUsername, resolvedUser, USER_LOOKUP_CACHE_TTL_MS);
  return resolvedUser;
}

async function checkVipOwnership(config, userId) {
  const gamepassId = Number(config?.vipTitleGamepassId || 0);
  if (!gamepassId) {
    throw new Error('Gamepass VIP untuk map ini belum diatur di dashboard.');
  }

  const cacheKey = `${userId}:${gamepassId}`;
  const cachedOwnership = getCached(vipOwnershipCache, cacheKey);
  if (typeof cachedOwnership === 'boolean') {
    return cachedOwnership;
  }

  const response = await fetchWithRetry(
    `https://inventory.roblox.com/v1/users/${encodeURIComponent(userId)}/items/GamePass/${encodeURIComponent(gamepassId)}`,
    {
      headers: {
        Accept: 'application/json',
      },
    },
    'VIP ownership check',
  );

  if (!response.ok) {
    throw new Error(`Cek VIP gamepass gagal (${response.status}).`);
  }

  const data = await response.json();
  const hasVip = Array.isArray(data?.data) && data.data.length > 0;
  setCached(vipOwnershipCache, cacheKey, hasVip, VIP_CHECK_CACHE_TTL_MS);
  return hasVip;
}

function buildScriptEmbed(mapConfig) {
  const roleInfo = mapConfig.scriptAccessRoleIds?.length
    ? mapConfig.scriptAccessRoleIds.map((roleId) => `<@&${roleId}>`).join(', ')
    : 'Admin only';
  const gamepassInfo = String(mapConfig.gamepassId || 0);

  return new EmbedBuilder()
    .setColor(0x60a5fa)
    .setTitle('Roblox Files Ready')
    .setDescription(
      [
        `File lampiran untuk map **${mapConfig.name}** sudah terisi otomatis.`,
        '',
        '1. `MX_VIPTitleClaim.lua` -> folder `MX_Modules`',
        '2. `MX_Main_VIPClaim_PATCH.lua` -> patch tempel ke `MX_Main`',
        '3. `MX_Main_FINAL_SAFE.lua` -> versi full script Roblox',
        '4. `VIP_TITLE_CLAIM_SETUP.md` -> panduan singkat setup',
      ].join('\n'),
    )
    .addFields(
      { name: 'Map Key', value: mapConfig.mapKey, inline: true },
      { name: 'Gamepass', value: gamepassInfo, inline: true },
      { name: 'Akses Script', value: roleInfo, inline: false },
    );
}

function buildClaimSuccessEmbed(username, title, mapConfig) {
  return new EmbedBuilder()
    .setColor(0x16a34a)
    .setTitle('Claim Title Tersimpan')
    .setDescription(`Claim untuk **@${username}** berhasil masuk antrean.`)
    .addFields(
      { name: 'Custom Title', value: title, inline: true },
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Status', value: 'Pending review / apply', inline: true },
    )
    .setFooter({ text: 'Admin bisa cek daftar claim dengan /titile list' });
}

function buildPaymentCheckoutEmbed(username, title, mapConfig, payment) {
  return new EmbedBuilder()
    .setColor(0x22c55e)
    .setTitle('Checkout Title Siap')
    .setDescription(`Invoice untuk **@${username}** sudah dibuat. Lanjutkan pembayaran lewat Duitku agar title diproses otomatis.`)
    .addFields(
      { name: 'Custom Title', value: title, inline: true },
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Nominal', value: formatIdr(payment.amount), inline: true },
      { name: 'Order ID', value: payment.merchantOrderId, inline: false },
      { name: 'Expired', value: payment.expiresAt ? `<t:${Math.floor(new Date(payment.expiresAt).getTime() / 1000)}:R>` : `${mapConfig.paymentExpiryMinutes} menit`, inline: true },
      { name: 'Status', value: 'Menunggu pembayaran', inline: true },
    )
    .setFooter({ text: 'Setelah pembayaran sukses, title akan masuk otomatis ke antrian Roblox.' });
}

function getRobloxTemplatePaths() {
  return {
    module: path.join(PROJECT_ROOT, 'roblox', 'MX_VIPTitleClaim.lua'),
    full: path.join(PROJECT_ROOT, 'roblox', 'MX_Main_FINAL_SAFE.lua'),
  };
}

function escapeLuaString(value) {
  return String(value || '')
    .replace(/\\/g, '\\\\')
    .replace(/"/g, '\\"');
}

function buildLuaAllowedPlaceIds(placeIds) {
  if (!Array.isArray(placeIds) || placeIds.length === 0) {
    return '\t-- kosong, semua place di map ini diizinkan';
  }

  return placeIds
    .map((placeId) => {
      const numericPlaceId = String(placeId).replace(/[^\d]/g, '');
      return numericPlaceId ? `\t[${numericPlaceId}] = true,` : null;
    })
    .filter(Boolean)
    .join('\n');
}

function effectiveGamepassId(mapConfig) {
  return Number(mapConfig.gamepassId || 0);
}

function buildRobloxConfigSnippet(mapConfig, appUrl) {
  return [
    `VIP_GAMEPASS_ID = ${effectiveGamepassId(mapConfig)}`,
    `VIP_TITLE_MAP_KEY = "${escapeLuaString(mapConfig.mapKey)}"`,
    `VIP_TITLE_BACKEND_URL = "${escapeLuaString(appUrl)}"`,
    `VIP_TITLE_API_KEY = "${escapeLuaString(mapConfig.apiKey || '')}"`,
    `VIP_TITLE_SLOT = ${Number(mapConfig.titleSlot || 10)}`,
    'VIP_TITLE_POLL_INTERVAL = 30',
    'VIP_TITLE_ALLOW_NONVIP_IN_STUDIO = true',
    'VIP_TITLE_ALLOWED_PLACE_IDS = {',
    buildLuaAllowedPlaceIds(mapConfig.placeIds),
    '}',
  ].join('\n');
}

function buildRobloxPatchContent(mapConfig, appUrl) {
  return [
    'local VIPClaimModule = require(ModFolder:WaitForChild("MX_VIPTitleClaim"))',
    '',
    `CONFIG.VIP_GAMEPASS_ID = ${effectiveGamepassId(mapConfig)}`,
    `CONFIG.VIP_TITLE_MAP_KEY = "${escapeLuaString(mapConfig.mapKey)}"`,
    `CONFIG.VIP_TITLE_BACKEND_URL = "${escapeLuaString(appUrl)}"`,
    `CONFIG.VIP_TITLE_API_KEY = "${escapeLuaString(mapConfig.apiKey || '')}"`,
    `CONFIG.VIP_TITLE_SLOT = ${Number(mapConfig.titleSlot || 10)}`,
    'CONFIG.VIP_TITLE_POLL_INTERVAL = 30',
    'CONFIG.VIP_TITLE_ALLOW_NONVIP_IN_STUDIO = true',
    'CONFIG.VIP_TITLE_ALLOWED_PLACE_IDS = {',
    buildLuaAllowedPlaceIds(mapConfig.placeIds),
    '}',
    '',
    'local VIPClaim = VIPClaimModule.Init({',
    '\tData = Data,',
    '\tsafeRefreshTitle = safeRefreshTitle,',
    '\tapplyCustomTitlesFromData = applyCustomTitlesFromData,',
    '\tnotifyPlayer = notifyPlayer,',
    '\tVIPGamepassId = CONFIG.VIP_GAMEPASS_ID,',
    '\tBackendUrl = CONFIG.VIP_TITLE_BACKEND_URL,',
    '\tApiKey = CONFIG.VIP_TITLE_API_KEY,',
    '\tMapKey = CONFIG.VIP_TITLE_MAP_KEY,',
    '\tAllowedPlaceIds = CONFIG.VIP_TITLE_ALLOWED_PLACE_IDS,',
    '\tClaimSlot = CONFIG.VIP_TITLE_SLOT,',
    '\tPollInterval = CONFIG.VIP_TITLE_POLL_INTERVAL,',
    '\tAllowNonVipInStudio = CONFIG.VIP_TITLE_ALLOW_NONVIP_IN_STUDIO,',
    '})',
    '',
    'task.defer(function()',
    '\tVIPClaim.CheckPlayer(player)',
    'end)',
    '',
  ].join('\n');
}

function injectRobloxConfigIntoFullScript(content, mapConfig, appUrl) {
  return String(content)
    .replace(/VIP_GAMEPASS_ID = .*?,/, `VIP_GAMEPASS_ID = ${effectiveGamepassId(mapConfig)},`)
    .replace(/VIP_TITLE_MAP_KEY = ".*?",/, `VIP_TITLE_MAP_KEY = "${escapeLuaString(mapConfig.mapKey)}",`)
    .replace(/VIP_TITLE_BACKEND_URL = ".*?",/, `VIP_TITLE_BACKEND_URL = "${escapeLuaString(appUrl)}",`)
    .replace(/VIP_TITLE_API_KEY = ".*?",/, `VIP_TITLE_API_KEY = "${escapeLuaString(mapConfig.apiKey || '')}",`)
    .replace(/VIP_TITLE_SLOT = .*?,/, `VIP_TITLE_SLOT = ${Number(mapConfig.titleSlot || 10)},`)
    .replace(/VIP_TITLE_POLL_INTERVAL = .*?,/, 'VIP_TITLE_POLL_INTERVAL = 30,')
    .replace(/VIP_TITLE_ALLOW_NONVIP_IN_STUDIO = .*?,/, 'VIP_TITLE_ALLOW_NONVIP_IN_STUDIO = true,')
    .replace(/VIP_TITLE_ALLOWED_PLACE_IDS = \{[\s\S]*?\n\t\},/, `VIP_TITLE_ALLOWED_PLACE_IDS = {\n${buildLuaAllowedPlaceIds(mapConfig.placeIds)}\n\t},`);
}

function buildRobloxSetupGuide(mapConfig, appUrl) {
  const roleInfo = mapConfig.scriptAccessRoleIds?.length
    ? mapConfig.scriptAccessRoleIds.map((roleId) => `- <@&${roleId}>`).join('\n')
    : '- Admin server Discord';
  const configSnippet = buildRobloxConfigSnippet(mapConfig, appUrl);

  return [
    '# VIP Title Claim Setup',
    '',
    `Map: ${mapConfig.name}`,
    `Map key: ${mapConfig.mapKey}`,
    `Mode claim: ${(mapConfig.titlePriceIdr || 0) > 0 ? 'Hybrid: claim gamepass + bayar IDR' : 'VIP Gamepass'}`,
    `Backend URL: ${appUrl}`,
    `Gamepass ID: ${effectiveGamepassId(mapConfig)}`,
    `Title slot: ${Number(mapConfig.titleSlot || 10)}`,
    `Harga title: ${(mapConfig.titlePriceIdr || 0) > 0 ? formatIdr(mapConfig.titlePriceIdr) : '-'}`,
    `Allowed Place IDs: ${mapConfig.placeIds?.length ? mapConfig.placeIds.join(', ') : 'Semua place diizinkan'}`,
    '',
    '## Langkah setup',
    '1. Taruh `MX_VIPTitleClaim.lua` ke folder `MX_Modules`.',
    '2. Tempel isi `MX_Main_VIPClaim_PATCH.lua` ke script `MX_Main`.',
    '3. Atau pakai `MX_Main_FINAL_SAFE.lua` kalau mau versi full yang sudah terisi config.',
    '4. Aktifkan `Allow HTTP Requests` di Roblox Studio.',
    '5. Jalankan panel Discord dengan `/titile setup` dan pilih map yang benar.',
    '',
    '## Config yang sudah terisi',
    '```lua',
    configSnippet,
    '```',
    '',
    '## Role yang boleh ambil script di Discord',
    roleInfo,
    '',
  ].join('\n');
}

async function buildRobloxAttachments(mapConfig, config) {
  const templatePaths = getRobloxTemplatePaths();
  const appUrl = String(config?.appUrl || '').trim();
  if (!appUrl) {
    throw new Error('APP_URL bot belum diisi, jadi script Roblox belum bisa dibuat otomatis.');
  }
  if (!mapConfig?.apiKey) {
    throw new Error('API key map belum tersedia dari dashboard, jadi script belum bisa diisi otomatis.');
  }

  const [moduleContent, fullScriptContent] = await Promise.all([
    readFile(templatePaths.module, 'utf8'),
    readFile(templatePaths.full, 'utf8'),
  ]);

  const hydratedMapConfig = {
    ...mapConfig,
    apiKey: mapConfig.apiKey || '',
  };

  return [
    new AttachmentBuilder(Buffer.from(moduleContent, 'utf8'), { name: 'MX_VIPTitleClaim.lua' }),
    new AttachmentBuilder(Buffer.from(buildRobloxPatchContent(hydratedMapConfig, appUrl), 'utf8'), { name: 'MX_Main_VIPClaim_PATCH.lua' }),
    new AttachmentBuilder(Buffer.from(injectRobloxConfigIntoFullScript(fullScriptContent, hydratedMapConfig, appUrl), 'utf8'), { name: 'MX_Main_FINAL_SAFE.lua' }),
    new AttachmentBuilder(Buffer.from(buildRobloxSetupGuide(hydratedMapConfig, appUrl), 'utf8'), { name: 'VIP_TITLE_CLAIM_SETUP.md' }),
  ];
}

function normalizeMapKey(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9_-]/g, '');
}

function truncateText(value, maxLength) {
  const text = String(value || '').trim();
  if (text.length <= maxLength) {
    return text;
  }

  return `${text.slice(0, Math.max(0, maxLength - 3))}...`;
}

function truncateDiscordContent(value, maxLength = 1800) {
  return truncateText(value, maxLength);
}

async function publishTitlePanel(channel, mapConfig) {
  const components = [
    new ButtonBuilder().setCustomId(buildTitlePanelButtonId(mapConfig.mapKey)).setLabel('Claim Title').setStyle(ButtonStyle.Primary),
  ];

  if ((mapConfig.titlePriceIdr || 0) > 0) {
    components.push(
      new ButtonBuilder().setCustomId(buildTitleBuyButtonId(mapConfig.mapKey)).setLabel(resolveBuyButtonLabel(mapConfig)).setStyle(ButtonStyle.Success),
    );
  }

  components.push(
    new ButtonBuilder().setCustomId(buildTitleScriptButtonId(mapConfig.mapKey)).setLabel('Script Roblox').setStyle(ButtonStyle.Secondary),
  );

  await channel.send({
    embeds: [buildTitlePanelEmbed(mapConfig)],
    components: [
      new ActionRowBuilder().addComponents(...components),
    ],
  });
}

function buildMapSelectEmbed(channel, maps) {
  return new EmbedBuilder()
    .setColor(0xf97316)
    .setTitle('Pilih Map VIP Title')
    .setDescription(`Ada ${maps.length} map aktif di dashboard. Pilih map yang mau dipakai untuk panel di ${channel}.`)
    .setFooter({ text: 'User nanti cukup isi username Roblox dan custom title.' });
}

export async function handleTitileCommand(interaction, config) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'setup') {
    if (!canManage(interaction)) {
      await interaction.reply({ content: 'Hanya admin server yang bisa mengirim panel title ini.', ephemeral: true });
      return;
    }

    const channel = interaction.options.getChannel('channel') || interaction.channel;
    if (!channel || typeof channel.send !== 'function') {
      await interaction.reply({ content: 'Channel target tidak valid.', ephemeral: true });
      return;
    }

    const maps = await loadActiveMapConfigs(config);
    if (maps.length === 0) {
      await interaction.reply({
        content: 'Belum ada map VIP Title aktif di dashboard. Aktifkan map dulu di panel setup.',
        ephemeral: true,
      });
      return;
    }

    if (maps.length === 1) {
      await publishTitlePanel(channel, maps[0]);
      await interaction.reply({
        content: `Panel VIP Title untuk **${maps[0].name}** berhasil dikirim ke ${channel}.`,
        ephemeral: true,
      });
      return;
    }

    const select = new StringSelectMenuBuilder()
      .setCustomId(buildTitleSetupSelectId(channel.id))
      .setPlaceholder('Pilih map dari dashboard')
      .addOptions(
        maps.slice(0, 25).map((mapConfig) =>
          new StringSelectMenuOptionBuilder()
            .setLabel(truncateForComponent(mapConfig.name))
            .setDescription(truncateForComponent(`Map key: ${mapConfig.mapKey} | Gamepass: ${mapConfig.gamepassId || '-'}`))
            .setValue(mapConfig.mapKey),
        ),
      );

    await interaction.reply({
      embeds: [buildMapSelectEmbed(channel, maps)],
      components: [new ActionRowBuilder().addComponents(select)],
      ephemeral: true,
    });
    return;
  }

  if (subcommand === 'list') {
    if (!canManage(interaction)) {
      await interaction.reply({ content: 'Hanya admin yang bisa melihat daftar claim title.', ephemeral: true });
      return;
    }

    const response = await fetchLaravelVipTitleClaims(config, { limit: 10 }).catch(() => null);
    const rows = (response?.items || [])
      .reverse()
      .map((claim, index) => {
        const mapKey = truncateText(claim.map_key, 24);
        const username = truncateText(claim.roblox_username, 24);
        const title = truncateText(claim.requested_title, 28);
        const status = truncateText(claim.status, 16);
        return `${index + 1}. [${mapKey}] @${username} -> ${title} [${status}]`;
      });

    await interaction.reply({
      embeds: [
        new EmbedBuilder()
          .setColor(0xf97316)
          .setTitle('Daftar VIP Title Claim')
          .setDescription(rows.join('\n') || 'Belum ada claim title tersimpan.')
          .setFooter({ text: `Total ditampilkan: ${rows.length}` }),
      ],
      ephemeral: true,
    });
  }
}

export async function handleTitileComponent(interaction, config) {
  if (interaction.isStringSelectMenu() && interaction.customId.startsWith(TITLE_SETUP_SELECT_PREFIX)) {
    if (!canManage(interaction)) {
      await interaction.reply({ content: 'Hanya admin yang bisa memilih map panel title.', ephemeral: true });
      return true;
    }

    const channelId = parseTitleSetupSelectChannelId(interaction.customId);
    const mapKey = normalizeMapKey(interaction.values?.[0] || '');
    const mapConfig = await resolveMapConfig(config, mapKey);

    if (!channelId) {
      await interaction.update({
        content: 'Channel target panel tidak valid.',
        embeds: [],
        components: [],
      });
      return true;
    }

    if (!mapConfig) {
      await interaction.update({
        content: 'Map yang dipilih sudah tidak aktif di dashboard. Coba kirim ulang command setup.',
        embeds: [],
        components: [],
      });
      return true;
    }

    const channel = interaction.guild
      ? interaction.guild.channels.cache.get(channelId)
        ?? await interaction.guild.channels.fetch(channelId).catch(() => null)
      : null;

    if (!channel || typeof channel.send !== 'function') {
      await interaction.update({
        content: 'Channel target tidak ditemukan atau tidak bisa dipakai.',
        embeds: [],
        components: [],
      });
      return true;
    }

    await publishTitlePanel(channel, mapConfig);
    await interaction.update({
      content: `Panel VIP Title untuk **${mapConfig.name}** berhasil dikirim ke ${channel}.`,
      embeds: [],
      components: [],
    });
    return true;
  }

  if (!interaction.isButton()) {
    return false;
  }

  const panelMapKey = parseTitlePanelButtonId(interaction.customId);
  if (panelMapKey) {
    const modal = new ModalBuilder()
      .setCustomId(buildTitleClaimModalId(panelMapKey))
      .setTitle('Claim VIP Title');

    const usernameInput = new TextInputBuilder()
      .setCustomId('roblox_username')
      .setLabel('Roblox Username')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(20)
      .setPlaceholder('Masukkan username Roblox');

    const titleInput = new TextInputBuilder()
      .setCustomId('custom_title')
      .setLabel('Custom Title')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(28)
      .setPlaceholder('Masukkan custom title');

    modal.addComponents(
      new ActionRowBuilder().addComponents(usernameInput),
      new ActionRowBuilder().addComponents(titleInput),
    );

    await interaction.showModal(modal);
    return true;
  }

  const buyMapKey = parseTitleBuyButtonId(interaction.customId);
  if (buyMapKey) {
    const modal = new ModalBuilder()
      .setCustomId(buildTitleBuyModalId(buyMapKey))
      .setTitle('Beli VIP Title');

    const usernameInput = new TextInputBuilder()
      .setCustomId('roblox_username')
      .setLabel('Roblox Username')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(20)
      .setPlaceholder('Masukkan username Roblox');

    const titleInput = new TextInputBuilder()
      .setCustomId('custom_title')
      .setLabel('Custom Title')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(28)
      .setPlaceholder('Masukkan custom title');

    modal.addComponents(
      new ActionRowBuilder().addComponents(usernameInput),
      new ActionRowBuilder().addComponents(titleInput),
    );

    await interaction.showModal(modal);
    return true;
  }

  const scriptMapKey = parseTitleScriptButtonId(interaction.customId);
  if (scriptMapKey) {
    const mapConfig = await resolveMapConfig(config, scriptMapKey);
    if (!mapConfig) {
      await interaction.reply({
        content: 'Map untuk tombol script ini sudah tidak aktif atau tidak ditemukan di dashboard.',
        ephemeral: true,
      });
      return true;
    }

    if (!canAccessRobloxScript(interaction, mapConfig)) {
      await interaction.reply({
        content: 'Kamu belum punya role yang diizinkan untuk ambil script Roblox map ini.',
        ephemeral: true,
      });
      return true;
    }

    try {
      const attachments = await buildRobloxAttachments(mapConfig, config);
      await interaction.reply({
        embeds: [buildScriptEmbed(mapConfig)],
        files: attachments,
        ephemeral: true,
      });
    } catch (error) {
      await interaction.reply({
        content: truncateDiscordContent(`Gagal buat script Roblox otomatis: ${error.message}`),
        ephemeral: true,
      });
    }

    return true;
  }

  return false;
}

export async function handleTitileModal(interaction, config) {
  const isClaimModal = interaction.isModalSubmit() && interaction.customId.startsWith(TITLE_CLAIM_MODAL_PREFIX);
  const isBuyModal = interaction.isModalSubmit() && interaction.customId.startsWith(TITLE_BUY_MODAL_PREFIX);

  if (!isClaimModal && !isBuyModal) {
    return false;
  }

  await interaction.deferReply({ ephemeral: true });

  const robloxUsername = String(interaction.fields.getTextInputValue('roblox_username') || '').trim();
  const titleCheck = validateTitle(interaction.fields.getTextInputValue('custom_title'));
  const mapKey = isBuyModal
    ? parseTitleBuyModalId(interaction.customId)
    : parseTitleClaimModalId(interaction.customId);

  if (!robloxUsername) {
    await interaction.editReply({ content: 'Username Roblox wajib diisi.' });
    return true;
  }

  if (!mapKey) {
    await interaction.editReply({ content: 'Panel claim ini belum punya map yang valid. Minta admin kirim panel baru dari dashboard.' });
    return true;
  }

  const mapConfig = await resolveMapConfig(config, mapKey);
  if (!mapConfig) {
    await interaction.editReply({
      content: `Map untuk panel ini sudah tidak aktif atau belum terdaftar di dashboard VIP Title.`,
    });
    return true;
  }

  if (!titleCheck.ok) {
    await interaction.editReply({ content: titleCheck.reason });
    return true;
  }

  let robloxUser = null;
  try {
    robloxUser = await resolveRobloxUser(robloxUsername);
  } catch (error) {
    await interaction.editReply({
      content: truncateDiscordContent(`Gagal cek username Roblox: ${error.message}`),
    });
    return true;
  }

  if (!robloxUser) {
    await interaction.editReply({
      content: 'Username Roblox tidak ditemukan.',
    });
    return true;
  }

  if (isBuyModal) {
    if ((mapConfig.titlePriceIdr || 0) <= 0) {
      await interaction.editReply({
        content: 'Map ini belum punya harga IDR, jadi tombol beli title belum aktif.',
      });
      return true;
    }

    try {
      const checkout = await createLaravelVipTitleCheckout(config, {
        map_key: mapKey,
        roblox_user_id: robloxUser.userId,
        roblox_username: robloxUser.username,
        requested_title: titleCheck.title,
        discord_user_id: interaction.user.id,
        discord_tag: interaction.user.tag,
        meta: {
          source: 'discord-bot',
        },
      });

      const paymentUrl = checkout?.payment?.paymentUrl;
      if (!paymentUrl) {
        throw new Error('Duitku belum mengembalikan payment URL.');
      }

      await interaction.editReply({
        embeds: [buildPaymentCheckoutEmbed(robloxUser.username, titleCheck.title, mapConfig, checkout.payment)],
        components: [
          new ActionRowBuilder().addComponents(
            new ButtonBuilder().setLabel('Bayar via Duitku').setStyle(ButtonStyle.Link).setURL(paymentUrl),
          ),
        ],
      });
    } catch (error) {
      await interaction.editReply({
        content: truncateDiscordContent(`Gagal buat checkout pembayaran: ${error.message}`),
      });
    }

    return true;
  }

  try {
    const hasVip = await checkVipOwnership({ vipTitleGamepassId: mapConfig.gamepassId }, robloxUser.userId);
    if (!hasVip) {
      await interaction.editReply({
        content: `@${robloxUser.username} belum terdeteksi punya VIP gamepass untuk map **${mapConfig.name}**, jadi belum bisa request title.`,
      });
      return true;
    }
  } catch (error) {
    await interaction.editReply({
      content: truncateDiscordContent(`Gagal cek status VIP: ${error.message}`),
    });
    return true;
  }

  try {
    await createLaravelVipTitleClaim(config, {
      map_key: mapKey,
      roblox_user_id: robloxUser.userId,
      roblox_username: robloxUser.username,
      requested_title: titleCheck.title,
      discord_user_id: interaction.user.id,
      discord_tag: interaction.user.tag,
      meta: {
        source: 'discord-bot',
      },
    });
  } catch (error) {
    await interaction.editReply({
      content: truncateDiscordContent(`Gagal simpan claim title: ${error.message}`),
    });
    return true;
  }

  await interaction.editReply({
    embeds: [buildClaimSuccessEmbed(robloxUser.username, titleCheck.title, mapConfig)],
  });

  return true;
}
