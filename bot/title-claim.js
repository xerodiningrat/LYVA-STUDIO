import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import {
  ActionRowBuilder,
  AttachmentBuilder,
  ButtonBuilder,
  ButtonStyle,
  EmbedBuilder,
  ModalBuilder,
  PermissionFlagsBits,
  TextInputBuilder,
  TextInputStyle,
} from 'discord.js';

const TITLE_PANEL_BUTTON_ID = 'title_claim_open';
const TITLE_SCRIPT_BUTTON_ID = 'title_claim_script';
const TITLE_CLAIM_MODAL_ID = 'title_claim_modal';
const CLAIMS_PATH = path.resolve(process.cwd(), 'data', 'vip-title-claims.json');
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

function buildTitlePanelEmbed() {
  return new EmbedBuilder()
    .setColor(0xf97316)
    .setTitle('VIP Title Center')
    .setDescription(
      [
        'Panel claim custom title untuk member VIP.',
        '',
        'Klik `Claim Title` untuk isi username Roblox dan custom title.',
        'Bot akan cek dulu apakah username itu sudah beli VIP gamepass.',
        'Klik `Script Roblox` kalau admin butuh file yang harus ditaruh di game.',
      ].join('\n'),
    )
    .addFields(
      { name: 'Akses', value: 'VIP User', inline: true },
      { name: 'Output', value: 'Claim tersimpan', inline: true },
      { name: 'Filter', value: 'Reserved title + profanity diblok', inline: true },
    )
    .setFooter({ text: 'ProjectBotDC · VIP Title Panel' });
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
    throw new Error('ROBLOX_VIP_GAMEPASS_ID belum diisi.');
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

function buildClaimSuccessEmbed(username, title) {
  return new EmbedBuilder()
    .setColor(0x16a34a)
    .setTitle('Claim Title Tersimpan')
    .setDescription(`Claim untuk **@${username}** berhasil masuk antrean.`)
    .addFields(
      { name: 'Custom Title', value: title, inline: true },
      { name: 'Status', value: 'Pending review / apply', inline: true },
    )
    .setFooter({ text: 'Admin bisa cek daftar claim dengan /titile list' });
}

function getRobloxAttachmentPaths() {
  return [
    path.resolve(process.cwd(), 'roblox', 'MX_VIPTitleClaim.lua'),
    path.resolve(process.cwd(), 'roblox', 'MX_Main_VIPClaim_PATCH.lua'),
    path.resolve(process.cwd(), 'roblox', 'MX_Main_FINAL_SAFE.lua'),
    path.resolve(process.cwd(), 'roblox', 'VIP_TITLE_CLAIM_SETUP.md'),
  ];
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

    await channel.send({
      embeds: [buildTitlePanelEmbed()],
      components: [
        new ActionRowBuilder().addComponents(
          new ButtonBuilder().setCustomId(TITLE_PANEL_BUTTON_ID).setLabel('Claim Title').setStyle(ButtonStyle.Primary),
          new ButtonBuilder().setCustomId(TITLE_SCRIPT_BUTTON_ID).setLabel('Script Roblox').setStyle(ButtonStyle.Secondary),
        ),
      ],
    });

    await interaction.reply({ content: `Panel VIP Title berhasil dikirim ke ${channel}.`, ephemeral: true });
    return;
  }

  if (subcommand === 'list') {
    if (!canManage(interaction)) {
      await interaction.reply({ content: 'Hanya admin yang bisa melihat daftar claim title.', ephemeral: true });
      return;
    }

    const store = await readClaimsStore();
    const rows = store.claims
      .slice(-10)
      .reverse()
      .map((claim, index) => `${index + 1}. @${claim.robloxUsername} -> ${claim.title} [${claim.status}]`);

    await interaction.reply({ content: rows.join('\n') || 'Belum ada claim title tersimpan.', ephemeral: true });
  }
}

export async function handleTitileButton(interaction, config) {
  if (!interaction.isButton()) {
    return false;
  }

  if (interaction.customId === TITLE_PANEL_BUTTON_ID) {
    const modal = new ModalBuilder().setCustomId(TITLE_CLAIM_MODAL_ID).setTitle('Claim VIP Title');

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
    for (const filePath of getRobloxAttachmentPaths()) {
      try {
        const content = await readFile(filePath);
        attachments.push(new AttachmentBuilder(content, { name: path.basename(filePath) }));
      } catch {}
    }

    await interaction.reply({
      embeds: [buildScriptEmbed()],
      files: attachments,
      ephemeral: true,
    });
    return true;
  }

  return false;
}

export async function handleTitileModal(interaction, config) {
  if (!interaction.isModalSubmit() || interaction.customId !== TITLE_CLAIM_MODAL_ID) {
    return false;
  }

  await interaction.deferReply({ ephemeral: true });

  const robloxUsername = String(interaction.fields.getTextInputValue('roblox_username') || '').trim();
  const titleCheck = validateTitle(interaction.fields.getTextInputValue('custom_title'));

  if (!robloxUsername) {
    await interaction.editReply({ content: 'Username Roblox wajib diisi.' });
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
      content: `Gagal cek username Roblox: ${error.message}`,
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
    const hasVip = await checkVipOwnership(config, robloxUser.userId);
    if (!hasVip) {
      await interaction.editReply({
        content: `@${robloxUser.username} belum terdeteksi punya VIP gamepass, jadi belum bisa request title.`,
      });
      return true;
    }
  } catch (error) {
    await interaction.editReply({
      content: `Gagal cek status VIP: ${error.message}`,
    });
    return true;
  }

  const store = await readClaimsStore();
  store.claims.push({
    id: `${Date.now()}`,
    robloxUserId: robloxUser.userId,
    robloxUsername: robloxUser.username,
    title: titleCheck.title,
    discordUserId: interaction.user.id,
    discordTag: interaction.user.tag,
    createdAt: new Date().toISOString(),
    status: 'pending',
  });
  await writeClaimsStore(store);

  await interaction.editReply({
    embeds: [buildClaimSuccessEmbed(robloxUser.username, titleCheck.title)],
  });

  return true;
}
