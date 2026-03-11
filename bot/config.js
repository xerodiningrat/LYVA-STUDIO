import 'dotenv/config';

function requireEnv(name) {
  const value = process.env[name];

  if (!value) {
    throw new Error(`${name} belum diisi di .env`);
  }

  return value;
}

function normalizeUrl(rawUrl) {
  const trimmed = rawUrl.replace(/\/$/, '');

  try {
    const parsed = new URL(trimmed);

    if (parsed.hostname === 'localhost') {
      parsed.hostname = '127.0.0.1';
    }

    return parsed.toString().replace(/\/$/, '');
  } catch {
    return trimmed;
  }
}

export function loadBotConfig() {
  const appUrl = normalizeUrl(process.env.APP_URL || 'http://127.0.0.1:8000');
  const botApiUrl = normalizeUrl(process.env.BOT_API_URL || appUrl);
  const commandScope = process.env.DISCORD_COMMAND_SCOPE || 'global';
  const guildIds = String(process.env.DISCORD_GUILD_IDS || process.env.DISCORD_GUILD_ID || '')
    .split(/[,\s]+/)
    .map((value) => value.trim())
    .filter(Boolean);

  return {
    applicationId: requireEnv('DISCORD_APPLICATION_ID'),
    botToken: requireEnv('DISCORD_BOT_TOKEN'),
    guildId: process.env.DISCORD_GUILD_ID || '',
    guildIds,
    vipTitleGamepassId: Number(process.env.ROBLOX_VIP_GAMEPASS_ID || '1700114697'),
    appUrl,
    botApiUrl,
    internalToken: process.env.DISCORD_INTERNAL_TOKEN || '',
    verifiedRoleId: process.env.DISCORD_VERIFIED_ROLE_ID || '',
    commandScope,
    autoSyncCommands: (process.env.DISCORD_AUTO_SYNC_COMMANDS || 'true') === 'true',
    enableMessageContentIntent: (process.env.DISCORD_ENABLE_MESSAGE_CONTENT_INTENT || 'false') === 'true',
  };
}
