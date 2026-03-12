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
import { createLaravelVipTitleClaim, fetchLaravelVipTitleClaims, fetchLaravelVipTitleMaps } from './laravel-api.js';

const TITLE_PANEL_BUTTON_PREFIX = 'title_claim_open:';
const TITLE_SCRIPT_BUTTON_ID = 'title_claim_script';
const TITLE_CLAIM_MODAL_PREFIX = 'title_claim_modal:';
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

function parseTitlePanelButtonId(customId) {
  if (!String(customId || '').startsWith(TITLE_PANEL_BUTTON_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_PANEL_BUTTON_PREFIX.length));
}

function buildTitleClaimModalId(mapKey) {
  return `${TITLE_CLAIM_MODAL_PREFIX}${normalizeMapKey(mapKey)}`;
}

function parseTitleClaimModalId(customId) {
  if (!String(customId || '').startsWith(TITLE_CLAIM_MODAL_PREFIX)) {
    return '';
  }

  return normalizeMapKey(customId.slice(TITLE_CLAIM_MODAL_PREFIX.length));
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

  return {
    id: rawMap.id ?? null,
    mapKey,
    name: String(rawMap.name || mapKey).trim() || mapKey,
    gamepassId: Number(rawMap.gamepass_id ?? rawMap.gamepassId ?? 0),
    titleSlot: Number(rawMap.title_slot ?? rawMap.titleSlot ?? 0),
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
  return new EmbedBuilder()
    .setColor(0xf97316)
    .setTitle('VIP Title Center')
    .setDescription(
      [
        'Panel claim custom title untuk member VIP.',
        '',
        `Panel ini sudah terhubung ke map **${mapConfig.name}** dari dashboard.`,
        'Klik `Claim Title` lalu isi username Roblox dan custom title.',
        'Bot akan ambil map key + gamepass otomatis dari setup dashboard.',
        'Klik `Script Roblox` kalau admin butuh file yang harus ditaruh di game.',
      ].join('\n'),
    )
    .addFields(
      { name: 'Map', value: mapConfig.name, inline: true },
      { name: 'Akses', value: 'VIP User', inline: true },
      { name: 'Output', value: 'Claim tersimpan', inline: true },
      { name: 'Filter', value: 'Reserved title + profanity diblok', inline: true },
    )
    .setFooter({ text: 'ProjectBotDC | VIP Title Panel' });
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

function buildScriptEmbed() {
  return new EmbedBuilder()
    .setColor(0x60a5fa)
    .setTitle('Roblox Files Ready')
    .setDescription(
      [
        'File lampiran ini tinggal kamu taruh ke project Roblox.',
        '',
        '1. `MX_VIPTitleClaim.lua` -> folder `MX_Modules`',
        '2. `MX_Main_VIPClaim_PATCH.lua` -> patch tempel ke `MX_Main`',
        '3. `MX_Main_FINAL_SAFE.lua` -> versi full script Roblox',
        '4. `VIP_TITLE_CLAIM_SETUP.md` -> panduan singkat setup',
      ].join('\n'),
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

function getRobloxAttachmentPaths() {
  return [
    path.join(PROJECT_ROOT, 'roblox', 'MX_VIPTitleClaim.lua'),
    path.join(PROJECT_ROOT, 'roblox', 'MX_Main_VIPClaim_PATCH.lua'),
    path.join(PROJECT_ROOT, 'roblox', 'MX_Main_FINAL_SAFE.lua'),
    path.join(PROJECT_ROOT, 'roblox', 'VIP_TITLE_CLAIM_SETUP.md'),
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
  await channel.send({
    embeds: [buildTitlePanelEmbed(mapConfig)],
    components: [
      new ActionRowBuilder().addComponents(
        new ButtonBuilder().setCustomId(buildTitlePanelButtonId(mapConfig.mapKey)).setLabel('Claim Title').setStyle(ButtonStyle.Primary),
        new ButtonBuilder().setCustomId(TITLE_SCRIPT_BUTTON_ID).setLabel('Script Roblox').setStyle(ButtonStyle.Secondary),
      ),
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
    const modal = new ModalBuilder().setCustomId(buildTitleClaimModalId(panelMapKey)).setTitle('Claim VIP Title');

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

  if (interaction.customId === TITLE_SCRIPT_BUTTON_ID) {
    if (!canManage(interaction)) {
      await interaction.reply({ content: 'Tombol ini hanya bisa dipakai admin.', ephemeral: true });
      return true;
    }

    const attachments = [];
    const missingFiles = [];
    for (const filePath of getRobloxAttachmentPaths()) {
      try {
        const content = await readFile(filePath);
        attachments.push(new AttachmentBuilder(content, { name: path.basename(filePath) }));
      } catch {
        missingFiles.push(path.basename(filePath));
      }
    }

    await interaction.reply({
      embeds: [buildScriptEmbed()],
      files: attachments,
      content: attachments.length === 0
        ? `File Roblox tidak ketemu di server. Cek folder \`roblox/\`. Missing: ${missingFiles.join(', ') || 'semua file'}`
        : undefined,
      ephemeral: true,
    });
    return true;
  }

  return false;
}

export async function handleTitileModal(interaction, config) {
  if (!interaction.isModalSubmit() || !interaction.customId.startsWith(TITLE_CLAIM_MODAL_PREFIX)) {
    return false;
  }

  await interaction.deferReply({ ephemeral: true });

  const robloxUsername = String(interaction.fields.getTextInputValue('roblox_username') || '').trim();
  const titleCheck = validateTitle(interaction.fields.getTextInputValue('custom_title'));
  const mapKey = parseTitleClaimModalId(interaction.customId);

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
