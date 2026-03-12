import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
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
import { createLaravelVipTitleChange, createLaravelVipTitleCheckout, createLaravelVipTitleClaim, fetchLaravelVipTitleClaims, fetchLaravelVipTitleMaps, fetchLaravelVipTitlePaymentMethods, fetchLaravelVipTitlePaymentStatus } from './laravel-api.js';

const TITLE_PANEL_BUTTON_PREFIX = 'title_claim_open:';
const TITLE_BUY_BUTTON_PREFIX = 'title_buy_open:';
const TITLE_UPDATE_BUTTON_PREFIX = 'title_update_open:';
const TITLE_SCRIPT_BUTTON_PREFIX = 'title_claim_script:';
const TITLE_CLAIM_MODAL_PREFIX = 'title_claim_modal:';
const TITLE_BUY_MODAL_PREFIX = 'title_buy_modal:';
const TITLE_UPDATE_MODAL_PREFIX = 'title_update_modal:';
const TITLE_SETUP_SELECT_PREFIX = 'title_setup_map_select:';
const TITLE_PAYMENT_SELECT_PREFIX = 'title_payment_select:';
const TITLE_PAYMENT_REFRESH_PREFIX = 'title_payment_refresh:';
const MODULE_DIR = path.dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = path.resolve(MODULE_DIR, '..');
const CLAIMS_PATH = path.join(PROJECT_ROOT, 'data', 'vip-title-claims.json');
const RESERVED_TERMS = ['admin', 'administrator', 'dev', 'developer', 'owner', 'mod', 'moderator', 'staff'];
const PROFANITY_TERMS = ['anjing', 'babi', 'bangsat', 'kontol', 'memek', 'ngentot', 'goblok', 'tolol', 'jancok', 'fuck', 'bitch'];
const USER_LOOKUP_CACHE_TTL_MS = 5 * 60 * 1000;
const VIP_CHECK_CACHE_TTL_MS = 2 * 60 * 1000;
const TITLE_COLOR_PRESETS = {
  white: { mode: 'SOLID', preset: 'VIP', color: { r: 255, g: 255, b: 255 }, label: 'White' },
  gold: { mode: 'SOLID', preset: 'VIP', color: { r: 255, g: 215, b: 0 }, label: 'Gold' },
  yellow: { mode: 'SOLID', preset: 'VIP', color: { r: 255, g: 221, b: 51 }, label: 'Yellow' },
  red: { mode: 'SOLID', preset: 'VIP', color: { r: 239, g: 68, b: 68 }, label: 'Red' },
  orange: { mode: 'SOLID', preset: 'VIP', color: { r: 249, g: 115, b: 22 }, label: 'Orange' },
  green: { mode: 'SOLID', preset: 'VIP', color: { r: 34, g: 197, b: 94 }, label: 'Green' },
  lime: { mode: 'SOLID', preset: 'VIP', color: { r: 132, g: 204, b: 22 }, label: 'Lime' },
  cyan: { mode: 'SOLID', preset: 'VIP', color: { r: 34, g: 211, b: 238 }, label: 'Cyan' },
  blue: { mode: 'SOLID', preset: 'VIP', color: { r: 59, g: 130, b: 246 }, label: 'Blue' },
  purple: { mode: 'SOLID', preset: 'VIP', color: { r: 168, g: 85, b: 247 }, label: 'Purple' },
  pink: { mode: 'SOLID', preset: 'VIP', color: { r: 236, g: 72, b: 153 }, label: 'Pink' },
  silver: { mode: 'SOLID', preset: 'VIP', color: { r: 203, g: 213, b: 225 }, label: 'Silver' },
  black: { mode: 'SOLID', preset: 'VIP', color: { r: 15, g: 23, b: 42 }, label: 'Black' },
  rgb: { mode: 'RGB', preset: 'VIP', color: { r: 255, g: 255, b: 255 }, label: 'RGB Rainbow' },
  rainbow: { mode: 'RGB', preset: 'VIP', color: { r: 255, g: 255, b: 255 }, label: 'RGB Rainbow' },
};
const userLookupCache = new Map();
const vipOwnershipCache = new Map();
const paymentMethodSessionCache = new Map();
const PAYMENT_METHOD_SESSION_TTL_MS = 10 * 60 * 1000;

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

function clampColorChannel(value) {
  const numericValue = Number(value);
  if (!Number.isFinite(numericValue)) {
    return 255;
  }

  return Math.max(0, Math.min(255, Math.round(numericValue)));
}

function parseRgbInput(value) {
  const rawValue = String(value || '').trim();
  if (!rawValue) {
    return null;
  }

  const matches = rawValue.match(/\d{1,3}/g);
  if (!matches || matches.length !== 3) {
    return { ok: false, reason: 'Format RGB harus seperti `255,215,0`.' };
  }

  const channels = matches.map((item) => Number(item));
  if (channels.some((item) => !Number.isFinite(item) || item < 0 || item > 255)) {
    return { ok: false, reason: 'Nilai RGB harus di antara 0 sampai 255.' };
  }

  return {
    ok: true,
    color: {
      r: clampColorChannel(channels[0]),
      g: clampColorChannel(channels[1]),
      b: clampColorChannel(channels[2]),
    },
  };
}

function cloneTitleStyle(style) {
  if (!style) {
    return null;
  }

  return {
    mode: String(style.mode || 'SOLID').toUpperCase() === 'RGB' ? 'RGB' : 'SOLID',
    preset: String(style.preset || 'VIP').trim().toUpperCase() || 'VIP',
    color: {
      r: clampColorChannel(style.color?.r),
      g: clampColorChannel(style.color?.g),
      b: clampColorChannel(style.color?.b),
    },
    label: truncateForComponent(style.label || 'White', 60),
  };
}

function getTitleColorPresetSummary() {
  return 'white, gold, yellow, red, orange, green, lime, cyan, blue, purple, pink, silver, black, rgb';
}

function resolveTitleStyle(presetInput, rgbInput) {
  const parsedRgb = parseRgbInput(rgbInput);
  if (parsedRgb && !parsedRgb.ok) {
    return parsedRgb;
  }

  if (parsedRgb?.ok) {
    const { r, g, b } = parsedRgb.color;
    return {
      ok: true,
      style: {
        mode: 'SOLID',
        preset: 'VIP',
        color: parsedRgb.color,
        label: `RGB ${r},${g},${b}`,
      },
    };
  }

  const presetKey = normalizeText(presetInput);
  if (!presetKey) {
    return { ok: true, style: cloneTitleStyle(TITLE_COLOR_PRESETS.white) };
  }

  const preset = TITLE_COLOR_PRESETS[presetKey];
  if (!preset) {
    return {
      ok: false,
      reason: `Warna tidak valid. Pilih salah satu: ${getTitleColorPresetSummary()}. Atau isi RGB custom seperti 255,215,0.`,
    };
  }

  return { ok: true, style: cloneTitleStyle(preset) };
}

function formatTitleStyle(style) {
  const normalizedStyle = cloneTitleStyle(style);
  if (!normalizedStyle) {
    return 'White';
  }

  if (normalizedStyle.mode === 'RGB') {
    return normalizedStyle.label || 'RGB Rainbow';
  }

  const { r, g, b } = normalizedStyle.color;
  return `${normalizedStyle.label || 'White'} (${r}, ${g}, ${b})`;
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

function createPaymentMethodSession(payload) {
  const sessionId = randomUUID().replace(/-/g, '').slice(0, 24);
  setCached(paymentMethodSessionCache, sessionId, payload, PAYMENT_METHOD_SESSION_TTL_MS);
  return sessionId;
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

function buildTitleUpdateButtonId(mapKey) {
  return `${TITLE_UPDATE_BUTTON_PREFIX}${normalizeMapKey(mapKey)}`;
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

function parseTitleUpdateButtonId(customId) {
  if (!String(customId || '').startsWith(TITLE_UPDATE_BUTTON_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_UPDATE_BUTTON_PREFIX.length));
}

function buildTitleClaimModalId(mapKey) {
  return `${TITLE_CLAIM_MODAL_PREFIX}${normalizeMapKey(mapKey)}`;
}

function buildTitleBuyModalId(mapKey) {
  return `${TITLE_BUY_MODAL_PREFIX}${normalizeMapKey(mapKey)}`;
}

function buildTitleUpdateModalId(mapKey) {
  return `${TITLE_UPDATE_MODAL_PREFIX}${normalizeMapKey(mapKey)}`;
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

function parseTitleUpdateModalId(customId) {
  if (!String(customId || '').startsWith(TITLE_UPDATE_MODAL_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_UPDATE_MODAL_PREFIX.length));
}

function buildTitleSetupSelectId(channelId) {
  return `${TITLE_SETUP_SELECT_PREFIX}${channelId}`;
}

function buildTitlePaymentSelectId(sessionId) {
  return `${TITLE_PAYMENT_SELECT_PREFIX}${sessionId}`;
}

function buildTitlePaymentRefreshId(merchantOrderId) {
  return `${TITLE_PAYMENT_REFRESH_PREFIX}${merchantOrderId}`;
}

function parseTitleSetupSelectChannelId(customId) {
  if (!String(customId || '').startsWith(TITLE_SETUP_SELECT_PREFIX)) {
    return '';
  }

  return String(customId).slice(TITLE_SETUP_SELECT_PREFIX.length);
}

function parseTitlePaymentSelectId(customId) {
  if (!String(customId || '').startsWith(TITLE_PAYMENT_SELECT_PREFIX)) {
    return '';
  }

  return String(customId).slice(TITLE_PAYMENT_SELECT_PREFIX.length);
}

function parseTitlePaymentRefreshId(customId) {
  if (!String(customId || '').startsWith(TITLE_PAYMENT_REFRESH_PREFIX)) {
    return '';
  }

  return String(customId).slice(TITLE_PAYMENT_REFRESH_PREFIX.length);
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
        'Kalau title sudah pernah berhasil dipakai, user bisa klik `Ubah Title` untuk ganti title tiap 12 jam sekali.',
        `Warna title bisa dipilih dengan preset atau RGB custom. Preset: \`${getTitleColorPresetSummary()}\`.`,
        'Klik `Script Roblox` kalau admin butuh file yang harus ditaruh di game.',
      ].join('\n'),
    )
    .addFields(
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Claim VIP', value: 'Aktif', inline: true },
      { name: 'Beli IDR', value: hasPaidOption ? formatIdr(mapConfig.titlePriceIdr) : 'Nonaktif', inline: true },
      { name: 'Ubah Title', value: 'Setiap 12 jam', inline: true },
      { name: 'Filter', value: 'Reserved title + profanity diblok', inline: true },
      { name: 'Warna', value: 'Preset + RGB custom', inline: true },
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

function buildClaimSuccessEmbed(username, title, mapConfig, titleStyle) {
  return new EmbedBuilder()
    .setColor(0x16a34a)
    .setTitle('Claim Title Tersimpan')
    .setDescription(`Claim untuk **@${username}** berhasil masuk antrean.`)
    .addFields(
      { name: 'Custom Title', value: title, inline: true },
      { name: 'Warna', value: formatTitleStyle(titleStyle), inline: true },
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Status', value: 'Pending review / apply', inline: true },
    )
    .setFooter({ text: 'Admin bisa cek daftar claim dengan /titile list' });
}

function buildTitleChangeSuccessEmbed(username, title, mapConfig, titleStyle, nextChangeAt = null) {
  return new EmbedBuilder()
    .setColor(0x0891b2)
    .setTitle('Perubahan Title Tersimpan')
    .setDescription(`Perubahan title untuk **@${username}** sudah masuk antrean update.`)
    .addFields(
      { name: 'Custom Title', value: title, inline: true },
      { name: 'Warna', value: formatTitleStyle(titleStyle), inline: true },
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Cooldown', value: nextChangeAt ? `<t:${Math.floor(new Date(nextChangeAt).getTime() / 1000)}:R>` : '12 jam', inline: true },
      { name: 'Status', value: 'Menunggu apply di Roblox', inline: true },
    )
    .setFooter({ text: 'User bisa ubah title lagi setelah cooldown 12 jam selesai.' });
}

function buildPaymentCheckoutEmbed(username, title, mapConfig, payment, titleStyle = null) {
  const paymentStatus = String(payment.status || 'pending');
  const claimStatus = String(payment.claimStatus || 'awaiting_payment');
  const statusText = claimStatus === 'applied'
    ? 'Title sudah diproses di Roblox'
    : paymentStatus === 'paid'
      ? 'Pembayaran sudah diterima'
      : 'Menunggu pembayaran';

  return new EmbedBuilder()
    .setColor(0x22c55e)
    .setTitle('Checkout Title Siap')
    .setDescription(`Invoice untuk **@${username}** sudah dibuat. Lanjutkan pembayaran lewat Duitku atau klik cek status untuk update terbaru.`)
    .addFields(
      { name: 'Custom Title', value: title, inline: true },
      { name: 'Warna', value: formatTitleStyle(titleStyle), inline: true },
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Nominal', value: formatIdr(payment.amount), inline: true },
      { name: 'Order ID', value: payment.merchantOrderId, inline: false },
      { name: 'Expired', value: payment.expiresAt ? `<t:${Math.floor(new Date(payment.expiresAt).getTime() / 1000)}:R>` : `${mapConfig.paymentExpiryMinutes} menit`, inline: true },
      { name: 'Status', value: statusText, inline: true },
      { name: 'Payment', value: paymentStatus, inline: true },
      { name: 'Claim', value: claimStatus, inline: true },
    )
    .setFooter({ text: 'Setelah pembayaran sukses, title akan masuk otomatis ke antrian Roblox.' });
}

function normalizePaymentMethodOption(method = {}, baseAmount = 0) {
  const paymentMethod = String(method.paymentMethod || '').trim();
  if (!paymentMethod) {
    return null;
  }

  const totalFee = Number(method.totalFee ?? method.paymentAmount ?? baseAmount);
  const fee = Number(method.paymentFee ?? 0);

  return {
    paymentMethod,
    paymentName: String(method.paymentName || paymentMethod).trim() || paymentMethod,
    totalFee: Number.isFinite(totalFee) && totalFee > 0 ? totalFee : baseAmount,
    fee: Number.isFinite(fee) && fee >= 0 ? fee : 0,
  };
}

function buildPaymentMethodSelectEmbed(mapConfig, username, title, titleStyle, amount, methods, warning = null) {
  const lines = methods.slice(0, 10).map((method, index) => (
    `${index + 1}. **${method.paymentName}** - ${formatIdr(method.totalFee)}`
  ));

  return new EmbedBuilder()
    .setColor(0x0ea5e9)
    .setTitle('Pilih Metode Pembayaran')
    .setDescription(
      [
        `Checkout untuk **@${username}** di map **${mapConfig.name}**.`,
        `Custom title: **${title}**`,
        `Warna: **${formatTitleStyle(titleStyle)}**`,
        `Harga dasar: **${formatIdr(amount)}**`,
        warning || 'Pilih salah satu metode pembayaran Duitku di bawah ini.',
        '',
        ...lines,
      ].join('\n'),
    )
    .setFooter({ text: 'Daftar metode berasal dari channel pembayaran aktif di Duitku.' });
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
    new ButtonBuilder().setCustomId(buildTitleUpdateButtonId(mapConfig.mapKey)).setLabel('Ubah Title').setStyle(ButtonStyle.Secondary),
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

  if (interaction.isStringSelectMenu() && interaction.customId.startsWith(TITLE_PAYMENT_SELECT_PREFIX)) {
    const sessionId = parseTitlePaymentSelectId(interaction.customId);
    const session = getCached(paymentMethodSessionCache, sessionId);
    if (!session) {
      await interaction.update({
        content: 'Sesi pembayaran sudah kadaluarsa. Klik `Beli Title` lagi dari panel.',
        embeds: [],
        components: [],
      });
      return true;
    }

    const paymentMethod = String(interaction.values?.[0] || '').trim();
    const selectedMethod = (session.methods || []).find((method) => method.paymentMethod === paymentMethod);
    if (!selectedMethod) {
      await interaction.update({
        content: 'Metode pembayaran tidak valid. Coba pilih ulang dari panel.',
        embeds: [],
        components: [],
      });
      return true;
    }

    try {
      const checkout = await createLaravelVipTitleCheckout(config, {
        map_key: session.mapKey,
        roblox_user_id: session.robloxUserId,
        roblox_username: session.robloxUsername,
        requested_title: session.requestedTitle,
        discord_user_id: session.discordUserId,
        discord_tag: session.discordTag,
        payment_method: paymentMethod,
        meta: {
          source: 'discord-bot',
          payment_method_name: selectedMethod.paymentName,
          title_style: session.titleStyle,
        },
      });

      const paymentUrl = checkout?.payment?.paymentUrl;
      if (!paymentUrl) {
        throw new Error('Duitku belum mengembalikan payment URL.');
      }

      paymentMethodSessionCache.delete(sessionId);

      await interaction.update({
        content: session.lookupWarning ?? null,
        embeds: [buildPaymentCheckoutEmbed(session.robloxUsername, session.requestedTitle, session.mapConfig, {
          ...checkout.payment,
          amount: selectedMethod.totalFee || checkout.payment.amount,
          claimStatus: checkout?.claim?.status ?? 'awaiting_payment',
        }, session.titleStyle)],
        components: [
          new ActionRowBuilder().addComponents(
            new ButtonBuilder().setLabel(`Bayar via ${selectedMethod.paymentName}`).setStyle(ButtonStyle.Link).setURL(paymentUrl),
            new ButtonBuilder().setCustomId(buildTitlePaymentRefreshId(checkout.payment.merchantOrderId)).setLabel('Cek Status').setStyle(ButtonStyle.Secondary),
          ),
        ],
      });
    } catch (error) {
      await interaction.update({
        content: truncateDiscordContent(`Gagal buat checkout pembayaran: ${error.message}`),
        embeds: [],
        components: [],
      });
    }

    return true;
  }

  if (!interaction.isButton()) {
    return false;
  }

  const refreshMerchantOrderId = parseTitlePaymentRefreshId(interaction.customId);
  if (refreshMerchantOrderId) {
    await interaction.deferReply({ ephemeral: true });

    try {
      const status = await fetchLaravelVipTitlePaymentStatus(config, refreshMerchantOrderId);
      const mapConfig = await resolveMapConfig(config, status?.claim?.mapKey || '');
      const fallbackMapConfig = mapConfig || {
        name: status?.claim?.mapKey || 'Unknown Map',
        paymentExpiryMinutes: 60,
      };

      await interaction.editReply({
        embeds: [buildPaymentCheckoutEmbed(
          status?.claim?.robloxUsername || '-',
          status?.claim?.requestedTitle || '-',
          fallbackMapConfig,
          {
            ...status.payment,
            claimStatus: status?.claim?.status || 'unknown',
          },
          status?.claim?.titleStyle || null,
        )],
        components: status?.payment?.paymentUrl
          ? [
              new ActionRowBuilder().addComponents(
                new ButtonBuilder().setLabel('Bayar via Duitku').setStyle(ButtonStyle.Link).setURL(status.payment.paymentUrl),
                new ButtonBuilder().setCustomId(buildTitlePaymentRefreshId(status.payment.merchantOrderId)).setLabel('Cek Status').setStyle(ButtonStyle.Secondary),
              ),
            ]
          : [],
      });
    } catch (error) {
      await interaction.editReply({
        content: truncateDiscordContent(`Gagal ambil status pembayaran: ${error.message}`),
      });
    }

    return true;
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

    const colorPresetInput = new TextInputBuilder()
      .setCustomId('title_color_preset')
      .setLabel('Warna Preset / rgb (opsional)')
      .setStyle(TextInputStyle.Short)
      .setRequired(false)
      .setMaxLength(20)
      .setPlaceholder('gold, blue, pink, rgb');

    const colorRgbInput = new TextInputBuilder()
      .setCustomId('title_color_rgb')
      .setLabel('RGB Custom (opsional)')
      .setStyle(TextInputStyle.Short)
      .setRequired(false)
      .setMaxLength(20)
      .setPlaceholder('255,215,0');

    modal.addComponents(
      new ActionRowBuilder().addComponents(usernameInput),
      new ActionRowBuilder().addComponents(titleInput),
      new ActionRowBuilder().addComponents(colorPresetInput),
      new ActionRowBuilder().addComponents(colorRgbInput),
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

    const colorPresetInput = new TextInputBuilder()
      .setCustomId('title_color_preset')
      .setLabel('Warna Preset / rgb (opsional)')
      .setStyle(TextInputStyle.Short)
      .setRequired(false)
      .setMaxLength(20)
      .setPlaceholder('gold, blue, pink, rgb');

    const colorRgbInput = new TextInputBuilder()
      .setCustomId('title_color_rgb')
      .setLabel('RGB Custom (opsional)')
      .setStyle(TextInputStyle.Short)
      .setRequired(false)
      .setMaxLength(20)
      .setPlaceholder('255,215,0');

    modal.addComponents(
      new ActionRowBuilder().addComponents(usernameInput),
      new ActionRowBuilder().addComponents(titleInput),
      new ActionRowBuilder().addComponents(colorPresetInput),
      new ActionRowBuilder().addComponents(colorRgbInput),
    );

    await interaction.showModal(modal);
    return true;
  }

  const updateMapKey = parseTitleUpdateButtonId(interaction.customId);
  if (updateMapKey) {
    const modal = new ModalBuilder()
      .setCustomId(buildTitleUpdateModalId(updateMapKey))
      .setTitle('Ubah VIP Title');

    const usernameInput = new TextInputBuilder()
      .setCustomId('roblox_username')
      .setLabel('Roblox Username')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(20)
      .setPlaceholder('Masukkan username Roblox');

    const titleInput = new TextInputBuilder()
      .setCustomId('custom_title')
      .setLabel('Custom Title Baru')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(28)
      .setPlaceholder('Masukkan custom title baru');

    const colorPresetInput = new TextInputBuilder()
      .setCustomId('title_color_preset')
      .setLabel('Warna Preset / rgb (opsional)')
      .setStyle(TextInputStyle.Short)
      .setRequired(false)
      .setMaxLength(20)
      .setPlaceholder('gold, blue, pink, rgb');

    const colorRgbInput = new TextInputBuilder()
      .setCustomId('title_color_rgb')
      .setLabel('RGB Custom (opsional)')
      .setStyle(TextInputStyle.Short)
      .setRequired(false)
      .setMaxLength(20)
      .setPlaceholder('255,215,0');

    modal.addComponents(
      new ActionRowBuilder().addComponents(usernameInput),
      new ActionRowBuilder().addComponents(titleInput),
      new ActionRowBuilder().addComponents(colorPresetInput),
      new ActionRowBuilder().addComponents(colorRgbInput),
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
  const isUpdateModal = interaction.isModalSubmit() && interaction.customId.startsWith(TITLE_UPDATE_MODAL_PREFIX);

  if (!isClaimModal && !isBuyModal && !isUpdateModal) {
    return false;
  }

  await interaction.deferReply({ ephemeral: true });

  const robloxUsername = String(interaction.fields.getTextInputValue('roblox_username') || '').trim();
  const titleCheck = validateTitle(interaction.fields.getTextInputValue('custom_title'));
  const titleStyleCheck = resolveTitleStyle(
    interaction.fields.getTextInputValue('title_color_preset'),
    interaction.fields.getTextInputValue('title_color_rgb'),
  );
  const mapKey = isBuyModal
    ? parseTitleBuyModalId(interaction.customId)
    : isUpdateModal
      ? parseTitleUpdateModalId(interaction.customId)
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

  if (!titleStyleCheck.ok) {
    await interaction.editReply({ content: titleStyleCheck.reason });
    return true;
  }

  const titleStyle = titleStyleCheck.style;

  let robloxUser = null;
  try {
    robloxUser = await resolveRobloxUser(robloxUsername);
  } catch (error) {
    if (isBuyModal || isUpdateModal) {
      robloxUser = {
        userId: 0,
        username: robloxUsername,
        displayName: robloxUsername,
      };
    } else {
      await interaction.editReply({
        content: truncateDiscordContent(`Gagal cek username Roblox: ${error.message}`),
      });
      return true;
    }
  }

  if (!robloxUser) {
    if (isBuyModal || isUpdateModal) {
      robloxUser = {
        userId: 0,
        username: robloxUsername,
        displayName: robloxUsername,
      };
    } else {
      await interaction.editReply({
        content: 'Username Roblox tidak ditemukan.',
      });
      return true;
    }
  }

  if (isBuyModal) {
    if ((mapConfig.titlePriceIdr || 0) <= 0) {
      await interaction.editReply({
        content: 'Map ini belum punya harga IDR, jadi tombol beli title belum aktif.',
      });
      return true;
    }
    try {
      const paymentMethodResponse = await fetchLaravelVipTitlePaymentMethods(config, {
        map_key: mapKey,
      });

      const methods = (paymentMethodResponse?.items || [])
        .map((item) => normalizePaymentMethodOption(item, mapConfig.titlePriceIdr))
        .filter(Boolean)
        .slice(0, 25);

      if (methods.length === 0) {
        throw new Error('Metode pembayaran Duitku untuk nominal ini belum tersedia.');
      }

      const sessionId = createPaymentMethodSession({
        mapKey,
        mapConfig,
        robloxUserId: robloxUser.userId,
        robloxUsername: robloxUser.username,
        requestedTitle: titleCheck.title,
        titleStyle,
        discordUserId: interaction.user.id,
        discordTag: interaction.user.tag,
        lookupWarning: robloxUser.userId === 0
          ? 'Lookup Roblox sedang timeout dari server, jadi checkout lanjut pakai username yang kamu isi. Pastikan username Roblox benar.'
          : null,
        methods,
      });

      const select = new StringSelectMenuBuilder()
        .setCustomId(buildTitlePaymentSelectId(sessionId))
        .setPlaceholder('Pilih metode pembayaran Duitku')
        .addOptions(methods.map((method) =>
          new StringSelectMenuOptionBuilder()
            .setLabel(truncateForComponent(method.paymentName))
            .setDescription(truncateForComponent(`${formatIdr(method.totalFee)} | Kode: ${method.paymentMethod}`))
            .setValue(method.paymentMethod),
        ));

      await interaction.editReply({
        content: undefined,
        embeds: [buildPaymentMethodSelectEmbed(
          mapConfig,
          robloxUser.username,
          titleCheck.title,
          titleStyle,
          mapConfig.titlePriceIdr,
          methods,
          robloxUser.userId === 0
            ? 'Lookup Roblox sedang timeout dari server, jadi checkout akan lanjut pakai username yang kamu isi.'
            : null,
        )],
        components: [new ActionRowBuilder().addComponents(select)],
      });
    } catch (error) {
      await interaction.editReply({
        content: truncateDiscordContent(`Gagal buat checkout pembayaran: ${error.message}`),
      });
    }

    return true;
  }

  if (isUpdateModal) {
    try {
      const updateResponse = await createLaravelVipTitleChange(config, {
        map_key: mapKey,
        roblox_user_id: robloxUser.userId,
        roblox_username: robloxUser.username,
        requested_title: titleCheck.title,
        discord_user_id: interaction.user.id,
        discord_tag: interaction.user.tag,
        meta: {
          source: 'discord-bot',
          title_style: titleStyle,
        },
      });

      await interaction.editReply({
        content: robloxUser.userId === 0
          ? 'Lookup Roblox sedang timeout dari server, jadi perubahan title disimpan pakai username yang kamu isi. Pastikan username Roblox benar.'
          : undefined,
        embeds: [buildTitleChangeSuccessEmbed(
          robloxUser.username,
          titleCheck.title,
          mapConfig,
          titleStyle,
          updateResponse?.nextChangeAt || null,
        )],
      });
    } catch (error) {
      await interaction.editReply({
        content: truncateDiscordContent(`Gagal ubah title: ${error.message}`),
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
        title_style: titleStyle,
      },
    });
  } catch (error) {
    await interaction.editReply({
      content: truncateDiscordContent(`Gagal simpan claim title: ${error.message}`),
    });
    return true;
  }

  await interaction.editReply({
    embeds: [buildClaimSuccessEmbed(robloxUser.username, titleCheck.title, mapConfig, titleStyle)],
  });

  return true;
}
