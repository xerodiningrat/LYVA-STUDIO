import {
  VoiceConnectionStatus,
  entersState,
  getVoiceConnection,
  joinVoiceChannel,
} from '@discordjs/voice';
import {
  ActionRowBuilder,
  ActivityType,
  AttachmentBuilder,
  ButtonBuilder,
  ButtonStyle,
  ChannelType,
  Client,
  EmbedBuilder,
  GatewayIntentBits,
  ModalBuilder,
  PermissionFlagsBits,
  TextInputBuilder,
  TextInputStyle,
} from 'discord.js';
import { commandDefinitions } from './commands.js';
import { loadBotConfig } from './config.js';
import {
  acknowledgeLaravelRules,
  createLaravelVerification,
  createLaravelRace,
  createLaravelReport,
  deleteLaravelVerification,
  fetchLaravelGuildSettings,
  fetchLaravelVerification,
  fetchLaravelRace,
  fetchLaravelRaces,
  fetchLaravelSales,
  fetchLaravelStatus,
  updateLaravelGuildSettings,
  joinLaravelRace,
  updateLaravelRace,
} from './laravel-api.js';
import { registerCommands } from './register-commands.js';
import { buildRulesAcknowledgementAttachment } from './rules-acknowledgement-image.js';
import { getScriptTemplate } from './script-templates.js';
import { handleTitileButton, handleTitileCommand, handleTitileModal } from './title-claim.js';

const config = loadBotConfig();
const guildSettingsCache = new Map();
const spamTracker = new Map();
const spamEnforcementLocks = new Set();
const GUILD_SETTINGS_CACHE_TTL_MS = 60_000;
const DEFAULT_SPAM_THRESHOLD = 3;
const DEFAULT_SPAM_WINDOW_SECONDS = 45;
const MAX_PURGE_MESSAGES_PER_CHANNEL = 100;
const MAX_PURGE_AGE_MS = 14 * 24 * 60 * 60 * 1000;
const clientIntents = [GatewayIntentBits.Guilds, GatewayIntentBits.GuildVoiceStates];
const persistentVoiceSessions = new Map();

function truncateForDiscordContent(value, maxLength = 1800) {
  const text = String(value || '').trim();
  if (text.length <= maxLength) {
    return text;
  }

  return `${text.slice(0, Math.max(0, maxLength - 3))}...`;
}

if (config.enableMessageContentIntent) {
  clientIntents.push(GatewayIntentBits.GuildMessages, GatewayIntentBits.MessageContent);
}

const client = new Client({
  intents: clientIntents,
});

client.once('clientReady', async (readyClient) => {
  console.log(`Discord gateway bot online as ${readyClient.user.tag}`);
  readyClient.user.setPresence({
    activities: [{ name: 'dashboard Roblox', type: ActivityType.Watching }],
    status: 'online',
  });

  if (!config.autoSyncCommands) {
    console.log('Skipping command sync because DISCORD_AUTO_SYNC_COMMANDS=false.');
    return;
  }

  try {
    await registerCommands(config, commandDefinitions);
  } catch (error) {
    console.error('Failed to auto-sync Discord commands on startup.');
    console.error(error);
  }

  if (!config.internalToken) {
    return;
  }

  const laravelStatus = await fetchLaravelStatus(config).catch((error) => {
    console.warn(`Laravel API belum siap: ${error.message}`);
    return null;
  });

  if (laravelStatus) {
    console.log(`Laravel API ready at ${config.botApiUrl}`);
  }

  if (!config.enableMessageContentIntent) {
    console.warn('Anti-spam pesan nonaktif. Aktifkan DISCORD_ENABLE_MESSAGE_CONTENT_INTENT=true setelah Message Content Intent di Discord Developer Portal dinyalakan.');
  }
});

client.on('interactionCreate', async (interaction) => {
  try {
    if (interaction.isButton()) {
      await handleButton(interaction);
      return;
    }

    if (interaction.isModalSubmit()) {
      await handleModalSubmit(interaction);
      return;
    }

    if (!interaction.isChatInputCommand()) {
      return;
    }

    switch (interaction.commandName) {
      case 'ping':
        await interaction.reply({
          embeds: [
            new EmbedBuilder()
              .setColor(0x2563eb)
              .setTitle('Bot Sedang Online')
              .setDescription('Bot Discord aktif dan siap menerima perintah.')
              .setTimestamp(),
          ],
          ephemeral: true,
        });
        return;
      case 'status':
        await handleStatus(interaction);
        return;
      case 'sales':
        await handleSales(interaction);
        return;
      case 'server':
        await handleServer(interaction);
        return;
      case 'deploy':
        await handleDeploy(interaction);
        return;
      case 'report':
        await handleReport(interaction);
        return;
      case 'verify':
        await handleVerify(interaction);
        return;
      case 'webhook':
        await interaction.reply({
          embeds: [
            new EmbedBuilder()
              .setColor(0x16a34a)
              .setTitle('Pemeriksaan Webhook')
              .setDescription('Bot Node menerima slash command dan alur interaksi berjalan normal.')
              .setTimestamp(),
          ],
          ephemeral: true,
        });
        return;
      case 'ticket':
        await handleTicket(interaction);
        return;
      case 'moderation':
        await handleModeration(interaction);
        return;
      case 'rules':
        await handleRules(interaction);
        return;
      case 'script':
        await handleScript(interaction);
        return;
      case 'voice':
        await handleVoice(interaction);
        return;
      case 'titile':
        await handleTitileCommand(interaction, config);
        return;
      case 'race':
        await handleRace(interaction);
        return;
      default:
        await interaction.reply({ content: 'Perintah belum dikenali.', ephemeral: true });
    }
  } catch (error) {
    console.error(error);

    const payload = {
      content: truncateForDiscordContent(`Perintah gagal dijalankan: ${error.message}`),
      ephemeral: true,
    };

    if (interaction.deferred || interaction.replied) {
      await interaction.followUp(payload).catch(() => null);
      return;
    }

    await interaction.reply(payload).catch(() => null);
  }
});

client.on('messageCreate', async (message) => {
  try {
    await handleMessageCreate(message);
  } catch (error) {
    console.error('Anti-spam handler gagal dijalankan.');
    console.error(error);
  }
});

client.on('voiceStateUpdate', (oldState, newState) => {
  if (newState.id !== client.user?.id) {
    return;
  }

  if (!newState.channelId) {
    persistentVoiceSessions.delete(newState.guild.id);
    return;
  }

  persistentVoiceSessions.set(newState.guild.id, {
    channelId: newState.channelId,
    guildId: newState.guild.id,
  });
});

async function handleVoice(interaction) {
  if (!interaction.inGuild()) {
    await interaction.reply({
      content: 'Command voice hanya bisa dipakai di server.',
      ephemeral: true,
    });
    return;
  }

  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'join') {
    const memberChannel = interaction.member?.voice?.channel;

    if (!memberChannel) {
      await interaction.reply({
        content: 'Masuk dulu ke voice channel, baru pakai `/voice join`.',
        ephemeral: true,
      });
      return;
    }

    await interaction.deferReply({ ephemeral: true });

    const previousConnection = getVoiceConnection(interaction.guildId);
    if (previousConnection) {
      previousConnection.destroy();
    }

    const connection = joinVoiceChannel({
      adapterCreator: interaction.guild.voiceAdapterCreator,
      channelId: memberChannel.id,
      guildId: interaction.guildId,
      selfDeaf: true,
      selfMute: false,
    });

    persistentVoiceSessions.set(interaction.guildId, {
      channelId: memberChannel.id,
      guildId: interaction.guildId,
    });

    connection.on(VoiceConnectionStatus.Disconnected, async () => {
      try {
        await Promise.race([
          entersState(connection, VoiceConnectionStatus.Signalling, 5_000),
          entersState(connection, VoiceConnectionStatus.Connecting, 5_000),
        ]);
      } catch {
        persistentVoiceSessions.delete(interaction.guildId);
        connection.destroy();
      }
    });

    try {
      await entersState(connection, VoiceConnectionStatus.Ready, 15_000);
    } catch {
      await interaction.editReply({
        content: `Bot sedang mencoba masuk ke ${memberChannel}, tapi koneksinya belum stabil. Cek permission voice channel lalu coba lagi.`,
      });
      return;
    }

    await interaction.editReply({
      embeds: [
        new EmbedBuilder()
          .setColor(0x16a34a)
          .setTitle('Voice Stay Aktif')
          .setDescription(`Bot sudah masuk ke ${memberChannel} dan tidak akan auto-keluar walau channel kosong.`)
          .addFields(
            { name: 'Mode', value: 'AFK 24 jam / persistent', inline: true },
            { name: 'Self Deaf', value: 'Aktif', inline: true },
          )
          .setTimestamp(),
      ],
    });
    return;
  }

  if (subcommand === 'leave') {
    const connection = getVoiceConnection(interaction.guildId);

    if (!connection) {
      await interaction.reply({
        content: 'Bot sedang tidak ada di voice channel server ini.',
        ephemeral: true,
      });
      return;
    }

    persistentVoiceSessions.delete(interaction.guildId);
    connection.destroy();

    await interaction.reply({
      content: 'Bot sudah keluar dari voice channel.',
      ephemeral: true,
    });
    return;
  }

  const session = persistentVoiceSessions.get(interaction.guildId);
  const channelMention = session?.channelId ? `<#${session.channelId}>` : 'Tidak terhubung';

  await interaction.reply({
    embeds: [
      new EmbedBuilder()
        .setColor(0x2563eb)
        .setTitle('Status Voice Bot')
        .addFields(
          { name: 'Channel', value: channelMention, inline: true },
          { name: 'Mode', value: session ? 'Stay aktif' : 'Offline', inline: true },
        )
        .setTimestamp(),
    ],
    ephemeral: true,
  });
}

async function handleStatus(interaction) {
  await interaction.deferReply({ ephemeral: true });
  const status = await fetchLaravelStatus(config).catch(() => null);

  if (!status) {
    await interaction.editReply({
      content: 'Bot online, tapi status Laravel belum bisa diambil. Cek `BOT_API_URL` atau `APP_URL`, lalu pastikan `DISCORD_INTERNAL_TOKEN` benar.',
    });
    return;
  }

    const embed = new EmbedBuilder()
      .setColor(0x2563eb)
      .setTitle('Status Operasional Roblox')
      .setDescription('Ringkasan cepat kondisi dashboard, webhook, report, dan penjualan.')
      .addFields(
      { name: 'Game Terpantau', value: `\`${status.tracked_games}\``, inline: true },
      { name: 'Webhook Aktif', value: `\`${status.active_webhooks}\``, inline: true },
      { name: 'Alert Terbuka', value: `\`${status.open_alerts}\``, inline: true },
      { name: 'Report Pending', value: `\`${status.pending_reports}\``, inline: true },
      { name: 'Event Penjualan', value: `\`${status.sales_events ?? 0}\``, inline: true },
      { name: 'Sumber Data', value: status.has_bot_tables ? 'Live dari Laravel' : 'Fallback lokal', inline: true },
    )
    .setFooter({ text: 'ProjectBotDC • Command center' })
    .setTimestamp();

  await interaction.editReply({
    embeds: [embed],
  });
}

async function handleSales(interaction) {
  await interaction.deferReply({ ephemeral: true });
  const subcommand = interaction.options.getSubcommand();
  const mode = subcommand === 'summary' ? 'summary' : 'live';
  const sales = await fetchLaravelSales(config, mode).catch(() => null);

  if (!sales) {
    await interaction.editReply({
      content: 'Data penjualan belum bisa diambil dari Laravel. Cek `BOT_API_URL` atau `APP_URL`, `DISCORD_INTERNAL_TOKEN`, dan data sales event.',
    });
    return;
  }

  if (mode === 'summary') {
    const topProduct = sales.top_product?.product_name ?? 'belum ada';
    const topProductRobux = sales.top_product?.robux_total ?? 0;

    const embed = new EmbedBuilder()
      .setColor(0xd97706)
      .setTitle('Ringkasan Penjualan')
      .setDescription(`Ringkasan penjualan untuk periode ${sales.window}.`)
      .addFields(
        { name: 'Jumlah Transaksi', value: `\`${sales.transactions}\``, inline: true },
        { name: 'Total Robux', value: `\`${sales.robux_total} R$\``, inline: true },
        { name: 'Produk Terlaris', value: `${topProduct}\nNilai: \`${topProductRobux} R$\``, inline: false },
      )
      .setTimestamp();

    await interaction.editReply({
      embeds: [embed],
    });
    return;
  }

  if (!sales.items?.length) {
    await interaction.editReply({
      content: 'Belum ada event penjualan yang masuk ke Laravel.',
    });
    return;
  }

  const embed = new EmbedBuilder()
    .setColor(0xf59e0b)
    .setTitle('Feed Penjualan Langsung')
    .setDescription('Transaksi terbaru yang berhasil masuk ke Laravel.')
    .setTimestamp();

  sales.items.forEach((item, index) => {
    embed.addFields({
      name: `${index + 1}. ${item.product_name}`,
      value: `Pembeli: **${item.buyer_name}**\nNilai: \`${item.amount_robux} R$ x${item.quantity}\`\nTipe: ${item.product_type}`,
      inline: false,
    });
  });

  await interaction.editReply({
    embeds: [embed],
  });
}

async function handleServer(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'health') {
    await interaction.reply({
      embeds: [
        new EmbedBuilder()
          .setColor(0x0ea5e9)
          .setTitle('Kesehatan Server')
          .setDescription('Bot Node online, dashboard Laravel aktif, dan alur command berjalan normal.')
          .setTimestamp(),
      ],
      ephemeral: true,
    });
    return;
  }

  await interaction.reply({
    content: 'Belum ada feed shutdown real-time. Simpan incident ke `platform_alerts` untuk mengaktifkan command ini.',
    ephemeral: true,
  });
}

async function handleDeploy(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'log') {
    await interaction.reply({
      content: 'Deploy log belum terhubung ke pipeline publish Roblox.',
      ephemeral: true,
    });
    return;
  }

  await interaction.deferReply({ ephemeral: true });

  const message = interaction.options.getString('message', true);
  const title = interaction.options.getString('title') || 'Pengumuman Deploy';
  const channel = interaction.options.getChannel('channel', true);
  const type = interaction.options.getString('type') || 'announcement';
  const mentionMode = interaction.options.getString('mention') || 'none';
  const role = interaction.options.getRole('role');
  const actor = interaction.member?.displayName ?? interaction.user.username;
  const guildName = interaction.guild?.name ?? 'LYVA Studio';
  const actorRole = resolveMemberRoleLabel(interaction.member);
  const style = getDeployAnnouncementStyle(type);
  const mentionPrefix = buildAnnouncementMention(mentionMode, role?.id);
  const publishTimestamp = Math.floor(Date.now() / 1000);
  const announcementEmbed = new EmbedBuilder()
    .setColor(style.color)
    .setAuthor({ name: `${guildName} • Deploy Broadcast` })
    .setTitle(`🚀 ${title}`)
    .setDescription([
      `**${style.badge}**`,
      message,
      '',
      `Dipublikasikan <t:${publishTimestamp}:R> • timestamp ini bergerak otomatis di Discord.`,
    ].join('\n'))
    .setAuthor({ name: `${guildName} • ${style.header}` })
    .setTitle(`${style.icon} ${title}`)
    .addFields(
      { name: 'Diumumkan Oleh', value: actor, inline: true },
      { name: 'Role Pengirim', value: actorRole, inline: true },
      { name: 'Channel Tujuan', value: `${channel}`, inline: true },
      { name: 'Mode', value: style.mode, inline: true },
      { name: 'Broadcast', value: style.broadcastLabel, inline: true },
      { name: 'Status', value: 'Live', inline: true },
    )
    .setFooter({ text: `${style.footer} • live broadcast card` })
    .setTimestamp();

  if (!channel?.isTextBased()) {
    await interaction.editReply({
      content: 'Channel tujuan tidak mendukung pengiriman pesan bot.',
    });
    return;
  }

  const sentMessage = await channel.send({
    content: [mentionPrefix, `**${style.broadcastLabel}**`].filter(Boolean).join(' '),
    embeds: [announcementEmbed],
  }).catch(() => null);

  if (!sentMessage) {
    await interaction.editReply({
      content: `Gagal kirim pengumuman ke ${channel}. Pastikan bot punya izin kirim pesan dan embed di channel itu.`,
    });
    return;
  }

  await interaction.editReply({
    embeds: [
      new EmbedBuilder()
        .setColor(0x22c55e)
        .setTitle('Pengumuman Deploy Terkirim')
        .setDescription(`Pengumuman sudah dikirim ke ${channel}.`)
        .addFields(
          { name: 'Judul', value: title, inline: true },
          { name: 'Tipe', value: style.mode, inline: true },
          { name: 'Dikirim Oleh', value: actor, inline: true },
          { name: 'Message ID', value: sentMessage.id, inline: true },
        )
        .setFooter({ text: 'Deploy control' })
        .setTimestamp(),
    ],
  });
}

async function handleRules(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'create') {
    const channel = interaction.options.getChannel('channel', true);

    const modal = new ModalBuilder()
      .setCustomId(`rules_create_modal:${channel.id}`)
      .setTitle('Buat Panel Rules');

    const titleInput = new TextInputBuilder()
      .setCustomId('rules_title')
      .setLabel('Judul rules')
      .setPlaceholder('Contoh: ✦・𝐋𝐘𝐕𝐀 𝐂𝐎𝐌𝐌𝐔𝐍𝐈𝐓𝐘 — 𝐑𝐔𝐋𝐄𝐒・✦')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(200);

    const introInput = new TextInputBuilder()
      .setCustomId('rules_intro')
      .setLabel('Intro / welcome')
      .setPlaceholder('Contoh: 𝑾𝒆𝒍𝒄𝒐𝒎𝒆 ...')
      .setStyle(TextInputStyle.Paragraph)
      .setRequired(true)
      .setMaxLength(1000);

    const rulesInput = new TextInputBuilder()
      .setCustomId('rules_body')
      .setLabel('Isi rules')
      .setPlaceholder('Pisahkan tiap rule dengan baris baru.')
      .setStyle(TextInputStyle.Paragraph)
      .setRequired(true)
      .setMaxLength(4000);

    const punishmentInput = new TextInputBuilder()
      .setCustomId('rules_punishment')
      .setLabel('Punishment system')
      .setPlaceholder('Contoh: ① Warn\\n② Mute\\n③ Kick / Ban')
      .setStyle(TextInputStyle.Paragraph)
      .setRequired(false)
      .setMaxLength(1000);

    modal.addComponents(
      new ActionRowBuilder().addComponents(titleInput),
      new ActionRowBuilder().addComponents(introInput),
      new ActionRowBuilder().addComponents(rulesInput),
      new ActionRowBuilder().addComponents(punishmentInput),
    );

    await interaction.showModal(modal);
    return;
  }

  await interaction.deferReply({ ephemeral: true });

  const channel = interaction.options.getChannel('channel', true);
  const title = interaction.options.getString('title') || 'Peraturan Komunitas';
  const actor = interaction.member?.displayName ?? interaction.user.username;
  const rawRules = interaction.options.getString('rules', true);
  const rules = extractRuleItems(rawRules);

  if (rules.length === 0) {
    await interaction.editReply({
      content: 'Isi rules tidak boleh kosong.',
    });
    return;
  }

  if (!channel?.isTextBased()) {
    await interaction.editReply({
      content: 'Channel tujuan tidak mendukung pengiriman pesan bot.',
    });
    return;
  }

  const embed = new EmbedBuilder()
    .setColor(0x2563eb)
    .setAuthor({ name: 'LYVA Studio • Community Rules' })
    .setTitle(`📘 ${title}`)
    .setDescription([
      'Baca aturan di bawah sebelum ikut chat, event, atau aktivitas komunitas.',
      '',
      formatRuleList(rules),
    ].join('\n'))
    .addFields(
      { name: 'Diposting Oleh', value: actor, inline: true },
      { name: 'Channel', value: `${channel}`, inline: true },
      { name: 'Total Aturan', value: `${rules.length}`, inline: true },
      { name: 'Sudah Paham', value: 'Belum ada', inline: false },
    )
    .setFooter({ text: 'Community handbook' })
    .setTimestamp();

  const sentMessage = await channel.send({
    embeds: [embed],
    components: [
      new ActionRowBuilder().addComponents(
        new ButtonBuilder()
          .setCustomId('rules_acknowledge')
          .setLabel('Saya Paham')
          .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
          .setCustomId('rules_help')
          .setLabel('Butuh Bantuan')
          .setStyle(ButtonStyle.Secondary),
      ),
    ],
  }).catch(() => null);

  if (!sentMessage) {
    await interaction.editReply({
      content: `Gagal kirim rules ke ${channel}. Pastikan bot punya izin kirim pesan dan embed di channel itu.`,
    });
    return;
  }

  await interaction.editReply({
    embeds: [
      new EmbedBuilder()
        .setColor(0x22c55e)
        .setTitle('Panel Rules Terkirim')
        .setDescription(`Rules sudah diposting ke ${channel}.`)
        .addFields(
          { name: 'Judul', value: title, inline: true },
          { name: 'Jumlah Rules', value: `${rules.length}`, inline: true },
          { name: 'Message ID', value: sentMessage.id, inline: true },
        )
        .setFooter({ text: 'Rules control' })
        .setTimestamp(),
    ],
  });
}

async function handleReport(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (!config.internalToken) {
    await interaction.reply({
      content: '`DISCORD_INTERNAL_TOKEN` belum diisi, jadi report belum bisa disimpan ke Laravel.',
      ephemeral: true,
    });
    return;
  }

  if (subcommand === 'player') {
    const player = interaction.options.getString('player', true);
    const reason = interaction.options.getString('reason', true);

    const report = await createLaravelReport(config, {
      reporter_name: interaction.user.username,
      reported_player_name: player,
      category: 'player',
      summary: reason,
      priority: 'high',
      payload: {
        source: 'discord_gateway_bot',
        discord_user_id: interaction.user.id,
      },
    });

    await interaction.reply({
      embeds: [
        new EmbedBuilder()
          .setColor(0xef4444)
          .setTitle('Report Player Dibuat')
          .setDescription(`Report #${report.id} berhasil dibuat untuk ${player}.`)
          .addFields(
            { name: 'Prioritas', value: 'tinggi', inline: true },
            { name: 'Pelapor', value: interaction.user.username, inline: true },
          )
          .setTimestamp(),
      ],
      ephemeral: true,
    });

    return;
  }

  const summary = interaction.options.getString('summary', true);
  const severity = interaction.options.getString('severity') || 'medium';

  const report = await createLaravelReport(config, {
    reporter_name: interaction.user.username,
    reported_player_name: 'game-system',
    category: 'bug',
    summary,
    priority: severity,
    payload: {
      source: 'discord_gateway_bot',
      discord_user_id: interaction.user.id,
    },
  });

  await interaction.reply({
    embeds: [
        new EmbedBuilder()
          .setColor(0xef4444)
          .setTitle('Bug Report Dibuat')
          .setDescription(`Bug report #${report.id} berhasil dibuat.`)
          .addFields(
            { name: 'Severity', value: severity, inline: true },
            { name: 'Pelapor', value: interaction.user.username, inline: true },
          )
          .setTimestamp(),
    ],
    ephemeral: true,
  });
}

async function handleVerify(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'setup') {
    await interaction.deferReply({ ephemeral: true });

    const channel = interaction.options.getChannel('channel', true);
    const role = interaction.options.getRole('role');
    const title = interaction.options.getString('title') || 'Verifikasi Akun Roblox';
    const actor = interaction.member?.displayName ?? interaction.user.username;

    if (!channel?.isTextBased()) {
      await interaction.editReply({
        content: 'Channel tujuan verifikasi tidak mendukung pesan bot.',
      });
      return;
    }

    const sentMessage = await channel.send({
      embeds: [
        new EmbedBuilder()
          .setColor(0x2563eb)
          .setAuthor({ name: 'LYVA Studio • Verification Desk' })
          .setTitle(`✅ ${title}`)
          .setDescription([
            'Klik tombol di bawah untuk memulai verifikasi akun Roblox.',
            '',
            role
              ? `Setelah verifikasi berhasil, bot akan memberikan role ${role}.`
              : 'Setelah verifikasi berhasil, bot akan memberikan role verified yang diset oleh admin.',
          ].join('\n'))
          .addFields(
            { name: 'Langkah 1', value: 'Klik `Verifikasi Sekarang`', inline: false },
            { name: 'Langkah 2', value: 'Masukkan username Roblox kamu', inline: false },
            { name: 'Langkah 3', value: 'Bot akan cek akun dan memberi role verified', inline: false },
          )
          .setFooter({ text: `Diposting oleh ${actor}` })
          .setTimestamp(),
      ],
      components: [
        new ActionRowBuilder().addComponents(
          new ButtonBuilder()
            .setCustomId('verify_start_button')
            .setLabel('Verifikasi Sekarang')
            .setStyle(ButtonStyle.Primary),
        ),
      ],
    }).catch(() => null);

    if (!sentMessage) {
      await interaction.editReply({
        content: `Gagal kirim panel verifikasi ke ${channel}. Pastikan bot punya izin kirim pesan dan embed di channel itu.`,
      });
      return;
    }

    await updateLaravelGuildSettings(config, interaction.guildId, {
      verification_channel_id: channel.id,
      verification_message_id: sentMessage.id,
      verification_role_id: role?.id ?? null,
    }).catch(() => null);

    await interaction.editReply({
      embeds: [
        new EmbedBuilder()
          .setColor(0x22c55e)
          .setTitle('Panel Verifikasi Terkirim')
          .setDescription(`Panel verifikasi sudah diposting ke ${channel}.`)
          .addFields(
            { name: 'Judul', value: title, inline: true },
            { name: 'Role Setelah Verify', value: role ? `${role}` : 'Belum diset', inline: true },
            { name: 'Dikirim Oleh', value: actor, inline: true },
            { name: 'Message ID', value: sentMessage.id, inline: true },
          )
          .setFooter({ text: 'Verification control' })
          .setTimestamp(),
      ],
    });
    return;
  }

  if (subcommand === 'start') {
    const modal = new ModalBuilder()
      .setCustomId('verify_start_modal')
      .setTitle('Verifikasi Roblox');

    const usernameInput = new TextInputBuilder()
      .setCustomId('roblox_username')
      .setLabel('Username Roblox')
      .setPlaceholder('Masukkan username Roblox kamu')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(255);

    modal.addComponents(new ActionRowBuilder().addComponents(usernameInput));

    await interaction.showModal(modal);
    return;
  }

  if (subcommand === 'check') {
    const verification = await fetchLaravelVerification(config, interaction.user.id).catch(() => null);

    if (!verification?.verified) {
      await interaction.reply({
        embeds: [
          new EmbedBuilder()
            .setColor(0x7c3aed)
            .setTitle('Status Verifikasi')
            .setDescription('Akun Discord ini belum terverifikasi ke Roblox.')
            .setTimestamp(),
        ],
        ephemeral: true,
      });
      return;
    }

    await interaction.reply({
      embeds: [
        new EmbedBuilder()
          .setColor(0x22c55e)
          .setTitle('Akun Sudah Terverifikasi')
          .setDescription(`Akun Discord kamu sudah terhubung ke **${verification.roblox_username}**.`)
          .addFields(
            { name: 'Display Name', value: verification.roblox_display_name, inline: true },
            { name: 'Roblox ID', value: verification.roblox_user_id, inline: true },
            { name: 'Verified At', value: verification.verified_at ? `<t:${Math.floor(new Date(verification.verified_at).getTime() / 1000)}:F>` : '-', inline: false },
          )
          .setTimestamp(),
      ],
      ephemeral: true,
    });
    return;
  }

  await deleteLaravelVerification(config, interaction.user.id).catch(() => null);

  const verifiedRole = await resolveVerifiedRole(interaction.guild);
  if (verifiedRole && interaction.member?.roles?.remove) {
    await interaction.member.roles.remove(verifiedRole).catch(() => null);
  }

  await interaction.reply({
    embeds: [
      new EmbedBuilder()
        .setColor(0xf97316)
        .setTitle('Verifikasi Dilepas')
        .setDescription('Link akun Discord dan Roblox sudah dilepas.')
        .setTimestamp(),
      ],
    ephemeral: true,
  });
}

async function handleModeration(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand !== 'setup-spam') {
    await interaction.reply({
      content: 'Subcommand moderation tidak dikenal.',
      ephemeral: true,
    });
    return;
  }

  await interaction.deferReply({ ephemeral: true });

  if (!config.enableMessageContentIntent) {
    await interaction.editReply({
      content: 'Anti-spam butuh Message Content Intent. Aktifkan dulu di Discord Developer Portal, lalu set `DISCORD_ENABLE_MESSAGE_CONTENT_INTENT=true` di `.env` dan restart bot.',
    });
    return;
  }

  const announcementChannel = interaction.options.getChannel('announcement_channel', true);
  const logChannel = interaction.options.getChannel('log_channel');
  const threshold = interaction.options.getInteger('threshold') ?? DEFAULT_SPAM_THRESHOLD;
  const windowSeconds = interaction.options.getInteger('window_seconds') ?? DEFAULT_SPAM_WINDOW_SECONDS;

  if (!announcementChannel?.isTextBased()) {
    await interaction.editReply({
      content: 'Channel pengumuman anti-spam tidak mendukung pesan bot.',
    });
    return;
  }

  if (logChannel && !logChannel.isTextBased()) {
    await interaction.editReply({
      content: 'Channel log anti-spam tidak mendukung pesan bot.',
    });
    return;
  }

  const updatedSetting = await updateLaravelGuildSettings(config, interaction.guildId, {
    spam_enabled: true,
    spam_announcement_channel_id: announcementChannel.id,
    spam_log_channel_id: logChannel?.id ?? null,
    spam_threshold: threshold,
    spam_window_seconds: windowSeconds,
  }).catch(() => null);

  if (!updatedSetting) {
    await interaction.editReply({
      content: 'Gagal menyimpan setup anti-spam ke Laravel. Cek koneksi bot ke backend.',
    });
    return;
  }

  rememberGuildSetting(interaction.guildId, updatedSetting);

  await announcementChannel.send({
    content: '@everyone',
    allowedMentions: {
      parse: ['everyone'],
    },
    embeds: [
      new EmbedBuilder()
        .setColor(0xf97316)
        .setAuthor({ name: 'LYVA Studio • Anti-Spam Notice' })
        .setTitle('Anti-Spam Server Sudah Aktif')
        .setDescription([
          'Sistem anti-spam sekarang aktif di semua channel teks server.',
          '',
          `Mohon hindari spam, flood, atau kirim pesan berulang. Jika mencapai **${threshold} pesan dalam ${windowSeconds} detik**, bot bisa langsung menghapus chat dan memberi ban otomatis.`,
        ].join('\n'))
        .addFields(
          { name: 'Cakupan', value: 'Semua channel teks server', inline: true },
          { name: 'Batas Spam', value: `${threshold} pesan`, inline: true },
          { name: 'Jendela Waktu', value: `${windowSeconds} detik`, inline: true },
        )
        .setFooter({ text: 'Harap chat dengan normal dan hindari spam.' })
        .setTimestamp(),
    ],
  }).catch(() => null);

  await interaction.editReply({
    embeds: [
      new EmbedBuilder()
        .setColor(0xdc2626)
        .setTitle('Anti-Spam Aktif')
        .setDescription('Bot sekarang akan memantau semua channel teks dan menindak spam otomatis.')
        .addFields(
          { name: 'Cakupan', value: 'Semua channel teks server', inline: true },
          { name: 'Channel Pengumuman', value: `${announcementChannel}`, inline: true },
          { name: 'Channel Log', value: logChannel ? `${logChannel}` : 'Tidak diset', inline: true },
          { name: 'Trigger', value: `${threshold} pesan dalam ${windowSeconds} detik`, inline: true },
          { name: 'Jendela Waktu', value: `${windowSeconds} detik`, inline: true },
          { name: 'Tindakan', value: 'Hapus pesan recent + auto ban', inline: true },
        )
        .setFooter({ text: 'Moderation guard' })
        .setTimestamp(),
    ],
  });
}

async function handleTicket(interaction) {
  const subcommand = interaction.options.getSubcommand();

  if (subcommand !== 'setup') {
    await interaction.reply({
      content: 'Subcommand ticket tidak dikenal.',
      ephemeral: true,
    });
    return;
  }

  await interaction.deferReply({ ephemeral: true });

  const panelChannel = interaction.options.getChannel('channel', true);
  const category = interaction.options.getChannel('category');
  const supportRole = interaction.options.getRole('support_role');
  const logChannel = interaction.options.getChannel('log_channel');
  const title = interaction.options.getString('title') || 'Pusat Ticket Bantuan';
  const actor = interaction.member?.displayName ?? interaction.user.username;

  if (!panelChannel?.isTextBased()) {
    await interaction.editReply({
      content: 'Channel tujuan panel ticket tidak mendukung pesan bot.',
    });
    return;
  }

  const sentMessage = await panelChannel.send({
    embeds: [
      new EmbedBuilder()
        .setColor(0x2563eb)
        .setAuthor({ name: 'LYVA Studio • Ticket Desk' })
        .setTitle(`🎫 ${title}`)
        .setDescription([
          'Butuh bantuan pembelian, report pembayaran, atau pertanyaan lain?',
          '',
          'Klik salah satu tombol di bawah untuk membuat ticket private.',
        ].join('\n'))
        .addFields(
          { name: 'Kategori 1', value: '🛒 Bantuan Pembelian', inline: true },
          { name: 'Kategori 2', value: '💳 Pembayaran', inline: true },
          { name: 'Kategori 3', value: '🛠️ Bantuan Lainnya', inline: true },
        )
        .setFooter({ text: `Diposting oleh ${actor}` })
        .setTimestamp(),
    ],
    components: [
      new ActionRowBuilder().addComponents(
        new ButtonBuilder()
          .setCustomId('ticket_create:purchase')
          .setLabel('Bantuan Pembelian')
          .setStyle(ButtonStyle.Primary),
        new ButtonBuilder()
          .setCustomId('ticket_create:payment')
          .setLabel('Pembayaran')
          .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
          .setCustomId('ticket_create:other')
          .setLabel('Bantuan Lainnya')
          .setStyle(ButtonStyle.Secondary),
      ),
    ],
  }).catch(() => null);

  if (!sentMessage) {
    await interaction.editReply({
      content: `Gagal kirim panel ticket ke ${panelChannel}. Pastikan bot punya izin kirim pesan dan embed di channel itu.`,
    });
    return;
  }

  await updateLaravelGuildSettings(config, interaction.guildId, {
    ticket_panel_channel_id: panelChannel.id,
    ticket_panel_message_id: sentMessage.id,
    ticket_support_role_id: supportRole?.id ?? null,
    ticket_category_id: category?.id ?? null,
    ticket_log_channel_id: logChannel?.id ?? null,
  }).catch(() => null);

  await interaction.editReply({
    embeds: [
      new EmbedBuilder()
        .setColor(0x22c55e)
        .setTitle('Panel Ticket Terkirim')
        .setDescription(`Panel ticket sudah diposting ke ${panelChannel}.`)
        .addFields(
          { name: 'Kategori Channel', value: category ? `${category}` : 'Tidak diset', inline: true },
          { name: 'Role Support', value: supportRole ? `${supportRole}` : 'Tidak diset', inline: true },
          { name: 'Log Channel', value: logChannel ? `${logChannel}` : 'Tidak diset', inline: true },
          { name: 'Message ID', value: sentMessage.id, inline: true },
        )
        .setFooter({ text: 'Ticket control' })
        .setTimestamp(),
    ],
  });
}

async function handleScript(interaction) {
  const subcommand = interaction.options.getSubcommand();
  const slug = subcommand.replace('-', '_');
  const template = await getScriptTemplate(config, slug);

  const attachment = new AttachmentBuilder(Buffer.from(template.content, 'utf8'), {
    name: template.filename,
  });

  const embed = new EmbedBuilder()
    .setColor(0x16a34a)
    .setTitle('Script Roblox Siap')
    .setDescription(`Template \`${template.filename}\` siap dipakai di Roblox Studio.`)
    .addFields(
      { name: 'Template', value: `\`${subcommand.replace('-', ' ')}\``, inline: true },
      { name: 'Format', value: 'Lampiran file', inline: true },
    )
    .setFooter({ text: 'Unduh, sesuaikan bila perlu, lalu tempel ke Roblox Studio.' });

  await interaction.reply({
    embeds: [embed],
    files: [attachment],
    ephemeral: true,
  });
}

async function handleRace(interaction) {
  await interaction.deferReply({ ephemeral: subcommandIsPrivate(interaction.options.getSubcommand()) });
  const subcommand = interaction.options.getSubcommand();

  if (subcommand === 'create') {
    const title = interaction.options.getString('title', true);
    const maxPlayers = interaction.options.getInteger('max_players', true);
    const entryFee = interaction.options.getInteger('entry_fee') || 0;
    const notes = interaction.options.getString('notes') || null;
    const startsInMinutes = interaction.options.getInteger('mulai_dalam_menit');
    const selectedRole = interaction.options.getRole('role_event');
    const startsAt = startsInMinutes ? new Date(Date.now() + (startsInMinutes * 60 * 1000)) : null;

    const race = await createLaravelRace(config, {
      title,
      max_players: maxPlayers,
      entry_fee_robux: entryFee,
      notes,
      created_by_discord_id: interaction.user.id,
      created_by_name: interaction.user.username,
      starts_at: startsAt ? startsAt.toISOString() : null,
      meta: {
        announce_role_id: selectedRole?.id ?? null,
        announce_role_name: selectedRole?.name ?? null,
      },
    });

    const joinButton = new ButtonBuilder()
      .setCustomId(`race_join:${race.id}`)
      .setLabel('Daftar Balapan')
      .setStyle(ButtonStyle.Success);

    const listButton = new ButtonBuilder()
      .setCustomId('race_list')
      .setLabel('Segarkan Daftar')
      .setStyle(ButtonStyle.Secondary);

    const closeButton = new ButtonBuilder()
      .setCustomId(`race_close:${race.id}:${interaction.user.id}`)
      .setLabel('Tutup Pendaftaran')
      .setStyle(ButtonStyle.Danger);

    const startButton = new ButtonBuilder()
      .setCustomId(`race_start:${race.id}:${interaction.user.id}`)
      .setLabel('Mulai Balapan')
      .setStyle(ButtonStyle.Primary);

    const finishButton = new ButtonBuilder()
      .setCustomId(`race_finish_open:${race.id}:${interaction.user.id}`)
      .setLabel('Input Hasil')
      .setStyle(ButtonStyle.Secondary)
      .setDisabled(true);

    const updatedRace = await updateLaravelRace(config, race.id, {
      meta: {
        announce_role_id: selectedRole?.id ?? null,
        announce_role_name: selectedRole?.name ?? null,
        created_message_channel_id: interaction.channelId,
      },
    }).catch(() => race);

    const embed = buildModernRacePanelEmbed({
      id: race.id,
      title,
      status: updatedRace.status ?? race.status,
      max_players: maxPlayers,
      participants_count: 0,
      entry_fee_robux: entryFee,
      starts_at: startsAt ? startsAt.toISOString() : null,
      notes,
      host: interaction.user.username,
    });

    await interaction.editReply({
      content: selectedRole ? `${selectedRole}` : null,
      embeds: [embed],
      components: [
        new ActionRowBuilder().addComponents(joinButton, listButton),
        new ActionRowBuilder().addComponents(closeButton, startButton, finishButton),
      ],
    });

    const sentMessage = await interaction.fetchReply();

    await updateLaravelRace(config, race.id, {
      meta: {
        announce_role_id: selectedRole?.id ?? null,
        announce_role_name: selectedRole?.name ?? null,
        created_message_channel_id: interaction.channelId,
        created_message_id: sentMessage.id,
      },
    }).catch(() => null);
    return;
  }

  if (subcommand === 'list') {
    await replyRaceList(interaction, true);
    return;
  }

  if (subcommand === 'finish') {
    const eventId = interaction.options.getInteger('event_id', true);
    const winners = parseRaceWinners(interaction.options.getString('winners', true));
    const resultNotes = interaction.options.getString('notes') || null;

    if (winners.length === 0) {
      await interaction.editReply({
        content: 'Masukkan minimal satu pemenang. Pisahkan dengan koma, misalnya `Lyva, Fenzane, Nadim`.',
      });
      return;
    }

    const currentRace = await fetchLaravelRace(config, eventId).catch(() => null);

    if (!currentRace) {
      await interaction.editReply({
        content: `Event #${eventId} tidak ditemukan atau belum bisa diambil dari Laravel.`,
      });
      return;
    }

    const updated = await updateLaravelRace(config, eventId, {
      status: 'finished',
      meta: {
        ...(currentRace.meta ?? {}),
        winners,
        result_notes: resultNotes,
        finished_at: new Date().toISOString(),
      },
    });

    await syncRacePanelMessage(eventId);

    const announcement = buildRaceResultEmbed({
      id: updated.id,
      title: currentRace.title,
      winners,
      notes: resultNotes,
    });

    await interaction.editReply({
      content: currentRace.meta?.announce_role_id ? `<@&${currentRace.meta.announce_role_id}>` : null,
      embeds: [announcement],
    });
    return;
  }

  const registration = await joinLaravelRace(
    config,
    interaction.options.getInteger('event_id', true),
    {
      discord_user_id: interaction.user.id,
      discord_username: interaction.user.username,
      roblox_username: interaction.options.getString('roblox_username', true),
      notes: interaction.options.getString('notes') || null,
    },
  );

  await interaction.editReply({
    embeds: [
      new EmbedBuilder()
        .setColor(0x22c55e)
        .setTitle('Pendaftaran Berhasil')
        .setDescription(`Kamu masuk ke event #${registration.race_event_id}.`)
        .addFields(
          { name: 'Status', value: registration.status, inline: true },
          { name: 'User Discord', value: interaction.user.username, inline: true },
          { name: 'Username Roblox', value: registration.roblox_username ?? '-', inline: true },
        )
        .setTimestamp(),
    ],
  });
}

async function handleButton(interaction) {
  if (await handleTitileButton(interaction, config)) {
    return;
  }

  if (interaction.customId.startsWith('ticket_create:')) {
    const type = interaction.customId.split(':')[1];
    const guildSetting = await fetchLaravelGuildSettings(config, interaction.guildId).catch(() => null);

    if (!interaction.guild) {
      await interaction.reply({ content: 'Ticket hanya bisa dibuat di server.', ephemeral: true });
      return;
    }

    const parent = guildSetting?.ticket_category_id
      ? interaction.guild.channels.cache.get(guildSetting.ticket_category_id)
      : null;

    const channelName = `ticket-${type}-${sanitizeChannelName(interaction.user.username)}`.slice(0, 90);
    const overwrites = [
      {
        id: interaction.guild.roles.everyone.id,
        deny: [PermissionFlagsBits.ViewChannel],
      },
      {
        id: interaction.user.id,
        allow: [
          PermissionFlagsBits.ViewChannel,
          PermissionFlagsBits.SendMessages,
          PermissionFlagsBits.ReadMessageHistory,
          PermissionFlagsBits.AttachFiles,
        ],
      },
      {
        id: interaction.client.user.id,
        allow: [
          PermissionFlagsBits.ViewChannel,
          PermissionFlagsBits.SendMessages,
          PermissionFlagsBits.ManageChannels,
          PermissionFlagsBits.ReadMessageHistory,
          PermissionFlagsBits.AttachFiles,
        ],
      },
    ];

    if (guildSetting?.ticket_support_role_id) {
      overwrites.push({
        id: guildSetting.ticket_support_role_id,
        allow: [
          PermissionFlagsBits.ViewChannel,
          PermissionFlagsBits.SendMessages,
          PermissionFlagsBits.ReadMessageHistory,
          PermissionFlagsBits.ManageMessages,
        ],
      });
    }

    const createdChannel = await interaction.guild.channels.create({
      name: channelName,
      type: ChannelType.GuildText,
      parent: parent?.id ?? null,
      permissionOverwrites: overwrites,
      topic: `Ticket ${type} dibuka oleh ${interaction.user.tag}`,
    }).catch(() => null);

    if (!createdChannel?.isTextBased()) {
      await interaction.reply({
        content: 'Gagal membuat channel ticket. Cek permission `Manage Channels` dan category target.',
        ephemeral: true,
      });
      return;
    }

    await createdChannel.send({
      content: [
        `${interaction.user}`,
        guildSetting?.ticket_support_role_id ? `<@&${guildSetting.ticket_support_role_id}>` : null,
      ].filter(Boolean).join(' '),
      embeds: [
        new EmbedBuilder()
          .setColor(0x2563eb)
          .setTitle(`Ticket ${formatTicketType(type)}`)
          .setDescription('Jelaskan kebutuhanmu dengan jelas. Tim support akan bantu secepatnya.')
          .addFields(
            { name: 'Pembuat Ticket', value: `${interaction.user}`, inline: true },
            { name: 'Kategori', value: formatTicketType(type), inline: true },
            { name: 'Status', value: 'Open', inline: true },
            { name: 'Ditangani Oleh', value: 'Belum di-claim', inline: false },
          )
          .setTimestamp(),
      ],
      components: [
        new ActionRowBuilder().addComponents(
          new ButtonBuilder()
            .setCustomId('ticket_claim')
            .setLabel('Claim Ticket')
            .setStyle(ButtonStyle.Primary),
          new ButtonBuilder()
            .setCustomId('ticket_close')
            .setLabel('Tutup Ticket')
            .setStyle(ButtonStyle.Danger),
        ),
      ],
    }).catch(() => null);

    await interaction.reply({
      content: `Ticket berhasil dibuat di ${createdChannel}.`,
      ephemeral: true,
    });
    return;
  }

  if (interaction.customId === 'ticket_close') {
    await interaction.deferReply({ ephemeral: true });
    const guildSetting = await fetchLaravelGuildSettings(config, interaction.guildId).catch(() => null);
    const transcript = await buildTicketTranscript(interaction.channel);
    const logChannel = guildSetting?.ticket_log_channel_id
      ? interaction.guild?.channels.cache.get(guildSetting.ticket_log_channel_id)
      : null;

    if (logChannel?.isTextBased()) {
      await logChannel.send({
        embeds: [
          new EmbedBuilder()
            .setColor(0xf97316)
            .setTitle('Transcript Ticket')
            .setDescription(`Ticket ${interaction.channel} telah ditutup.`)
            .addFields(
              { name: 'Ditutup Oleh', value: `${interaction.user}`, inline: true },
              { name: 'Nama Channel', value: interaction.channel?.name ?? '-', inline: true },
            )
            .setTimestamp(),
        ],
        files: [
          new AttachmentBuilder(Buffer.from(transcript, 'utf8'), {
            name: `${interaction.channel?.name ?? 'ticket'}-transcript.txt`,
          }),
        ],
      }).catch(() => null);
    }

    await interaction.editReply({
      content: 'Ticket ditutup.',
    }).catch(() => null);
    await interaction.channel?.delete('Ticket closed from panel').catch(() => null);
    return;
  }

  if (interaction.customId === 'ticket_claim') {
    const embed = EmbedBuilder.from(interaction.message.embeds[0]);
    const fields = (interaction.message.embeds[0].fields ?? []).map((field) => {
      if (field.name === 'Ditangani Oleh') {
        return {
          name: 'Ditangani Oleh',
          value: `${interaction.user}`,
          inline: false,
        };
      }

      return field;
    });

    const claimButton = ButtonBuilder.from(interaction.message.components[0].components[0])
      .setLabel(`Di-claim ${interaction.user.username}`)
      .setDisabled(true);
    const closeButton = ButtonBuilder.from(interaction.message.components[0].components[1]);

    await interaction.update({
      embeds: [embed.setFields(fields)],
      components: [new ActionRowBuilder().addComponents(claimButton, closeButton)],
    });
    return;
  }

  if (interaction.customId === 'verify_start_button') {
    const modal = new ModalBuilder()
      .setCustomId('verify_start_modal')
      .setTitle('Verifikasi Roblox');

    const usernameInput = new TextInputBuilder()
      .setCustomId('roblox_username')
      .setLabel('Username Roblox')
      .setPlaceholder('Masukkan username Roblox kamu')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(255);

    modal.addComponents(new ActionRowBuilder().addComponents(usernameInput));

    await interaction.showModal(modal);
    return;
  }

  if (interaction.customId === 'rules_acknowledge') {
    const acknowledgement = await acknowledgeLaravelRules(config, {
      guild_id: interaction.guildId,
      channel_id: interaction.channelId,
      message_id: interaction.message.id,
      discord_user_id: interaction.user.id,
      discord_username: interaction.user.username,
    });

    const embed = EmbedBuilder.from(interaction.message.embeds[0]);
    const fields = refreshRulesAcknowledgementFields(interaction.message.embeds[0].fields ?? [], acknowledgement);
    const acknowledgementImage = await buildRulesAcknowledgementAttachment(client, acknowledgement);

    embed.setFields(fields);
    if (acknowledgementImage) {
      embed.setImage('attachment://rules-ack-strip.png');
    }

    const button = ButtonBuilder.from(interaction.message.components[0].components[0])
      .setLabel('Saya Paham');

    await interaction.update({
      embeds: [embed],
      components: [new ActionRowBuilder().addComponents(button)],
      files: acknowledgementImage ? [acknowledgementImage] : [],
    });
    return;
  }

  if (interaction.customId === 'race_list') {
    await replyRaceList(interaction, false);
    return;
  }

  if (interaction.customId.startsWith('race_close:')) {
    await handleRaceAdminAction(interaction, 'registration_closed');
    return;
  }

  if (interaction.customId.startsWith('race_start:')) {
    await handleRaceAdminAction(interaction, 'started');
    return;
  }

  if (interaction.customId.startsWith('race_finish_open:')) {
    const [, eventId, ownerId] = interaction.customId.split(':');

    if (interaction.user.id !== ownerId) {
      await interaction.reply({
        content: 'Hanya admin pembuat event yang bisa input hasil balapan.',
        ephemeral: true,
      });
      return;
    }

    const modal = new ModalBuilder()
      .setCustomId(`race_finish_modal:${eventId}:${ownerId}`)
      .setTitle(`Input Hasil Event #${eventId}`);

    const winnersInput = new TextInputBuilder()
      .setCustomId('winners')
      .setLabel('Daftar pemenang')
      .setPlaceholder('Contoh: Lyva, Fenzane, Nadim')
      .setStyle(TextInputStyle.Paragraph)
      .setRequired(true)
      .setMaxLength(800);

    const notesInput = new TextInputBuilder()
      .setCustomId('result_notes')
      .setLabel('Catatan hasil')
      .setPlaceholder('Opsional: hadiah, best of, catatan lomba')
      .setStyle(TextInputStyle.Paragraph)
      .setRequired(false)
      .setMaxLength(500);

    modal.addComponents(
      new ActionRowBuilder().addComponents(winnersInput),
      new ActionRowBuilder().addComponents(notesInput),
    );

    await interaction.showModal(modal);
    return;
  }

  if (interaction.customId.startsWith('race_join:')) {
    const eventId = interaction.customId.split(':')[1];
    const modal = new ModalBuilder()
      .setCustomId(`race_join_modal:${eventId}`)
      .setTitle(`Daftar Balapan #${eventId}`);

    const usernameInput = new TextInputBuilder()
      .setCustomId('roblox_username')
      .setLabel('Username Roblox')
      .setPlaceholder('Masukkan username Roblox kamu')
      .setStyle(TextInputStyle.Short)
      .setRequired(true)
      .setMaxLength(255);

    const notesInput = new TextInputBuilder()
      .setCustomId('notes')
      .setLabel('Catatan tambahan')
      .setPlaceholder('Opsional: tim, slot favorit, atau info lain')
      .setStyle(TextInputStyle.Paragraph)
      .setRequired(false)
      .setMaxLength(500);

    modal.addComponents(
      new ActionRowBuilder().addComponents(usernameInput),
      new ActionRowBuilder().addComponents(notesInput),
    );

    await interaction.showModal(modal);
  }
}

async function handleRaceAdminAction(interaction, status) {
  const [, eventId, ownerId] = interaction.customId.split(':');

  if (interaction.user.id !== ownerId) {
    await interaction.reply({
      content: 'Hanya admin pembuat event yang bisa memakai tombol ini.',
      ephemeral: true,
    });
    return;
  }

  const updated = await updateLaravelRace(config, eventId, {
    status,
    registration_closes_at: status === 'registration_closed' ? new Date().toISOString() : undefined,
    starts_at: status === 'started' ? new Date().toISOString() : undefined,
  });

  const races = await fetchLaravelRaces(config).catch(() => null);
  const currentRace = races?.items?.find((item) => String(item.id) === String(eventId));

  const embed = buildModernRacePanelEmbed({
    id: updated.id,
    title: updated.title,
    status: updated.status,
    max_players: currentRace?.max_players ?? 0,
    participants_count: currentRace?.participants_count ?? 0,
    entry_fee_robux: currentRace?.entry_fee_robux ?? 0,
    starts_at: updated.starts_at ?? currentRace?.starts_at ?? null,
    notes: currentRace?.notes ?? null,
    host: currentRace?.created_by_name ?? interaction.user.username,
    meta: updated.meta ?? currentRace?.meta ?? null,
  });

  const joinButton = ButtonBuilder.from(interaction.message.components[0].components[0])
    .setDisabled(updated.status !== 'registration_open');
  const listButton = ButtonBuilder.from(interaction.message.components[0].components[1]);
  const closeButton = ButtonBuilder.from(interaction.message.components[1].components[0])
    .setDisabled(updated.status !== 'registration_open');
  const startButton = ButtonBuilder.from(interaction.message.components[1].components[1])
    .setDisabled(['started', 'finished'].includes(updated.status));
  const finishButton = interaction.message.components[1].components[2]
    ? ButtonBuilder.from(interaction.message.components[1].components[2])
    : new ButtonBuilder()
      .setCustomId(`race_finish_open:${updated.id}:${ownerId}`)
      .setLabel('Input Hasil')
      .setStyle(ButtonStyle.Secondary);
  finishButton.setDisabled(!['started', 'registration_closed'].includes(updated.status) || updated.status === 'finished');

  await interaction.update({
    embeds: [embed],
    components: [
      new ActionRowBuilder().addComponents(joinButton, listButton),
      new ActionRowBuilder().addComponents(closeButton, startButton, finishButton),
    ],
  });
}

async function handleModalSubmit(interaction) {
  if (await handleTitileModal(interaction, config)) {
    return;
  }

  if (interaction.customId === 'verify_start_modal') {
    await interaction.deferReply({ ephemeral: true });

    const robloxUsername = interaction.fields.getTextInputValue('roblox_username');
    const verification = await createLaravelVerification(config, {
      guild_id: interaction.guildId,
      discord_user_id: interaction.user.id,
      discord_username: interaction.user.username,
      roblox_username: robloxUsername,
    });

    let roleMessage = 'Role verified belum diatur.';
    const verifiedRole = await resolveVerifiedRole(interaction.guild);

    if (verifiedRole && interaction.member?.roles?.add) {
      const roleAssigned = await interaction.member.roles.add(verifiedRole).then(() => true).catch(() => false);
      roleMessage = roleAssigned
        ? `Role ${verifiedRole} berhasil diberikan.`
        : `Akun terverifikasi, tapi role ${verifiedRole} gagal diberikan.`;
    }

    await interaction.editReply({
      embeds: [
        new EmbedBuilder()
          .setColor(0x22c55e)
          .setTitle('Verifikasi Berhasil')
          .setDescription(`Akun Discord kamu sekarang terhubung ke **${verification.roblox_username}**.`)
          .addFields(
            { name: 'Display Name', value: verification.roblox_display_name, inline: true },
            { name: 'Roblox ID', value: verification.roblox_user_id, inline: true },
            { name: 'Role Verified', value: roleMessage, inline: false },
          )
          .setTimestamp(),
      ],
      ephemeral: true,
    });
    return;
  }

  if (interaction.customId.startsWith('rules_create_modal:')) {
    await interaction.deferReply({ ephemeral: true });

    const [, channelId] = interaction.customId.split(':');
    const channel = await client.channels.fetch(channelId).catch(() => null);
    const actor = interaction.member?.displayName ?? interaction.user.username;
    const title = interaction.fields.getTextInputValue('rules_title');
    const intro = interaction.fields.getTextInputValue('rules_intro');
    const rawRules = interaction.fields.getTextInputValue('rules_body');
    const punishment = interaction.fields.getTextInputValue('rules_punishment') || null;
    const rules = extractRuleItems(rawRules);

    if (!channel?.isTextBased()) {
      await interaction.editReply({
        content: 'Channel tujuan rules tidak ditemukan atau tidak mendukung pesan bot.',
      });
      return;
    }

    if (rules.length === 0) {
      await interaction.editReply({
        content: 'Isi rules tidak boleh kosong.',
      });
      return;
    }

    const sentMessage = await publishRulesPanel({
      channel,
      actor,
      title,
      intro,
      rules,
      punishment,
    });

    if (!sentMessage) {
      await interaction.editReply({
        content: `Gagal kirim rules ke ${channel}. Pastikan bot punya izin kirim pesan dan embed di channel itu.`,
      });
      return;
    }

    await interaction.editReply({
      embeds: [
        new EmbedBuilder()
          .setColor(0x22c55e)
          .setTitle('Panel Rules Terkirim')
          .setDescription(`Rules sudah diposting ke ${channel}.`)
          .addFields(
            { name: 'Judul', value: title, inline: true },
            { name: 'Jumlah Rules', value: `${rules.length}`, inline: true },
            { name: 'Message ID', value: sentMessage.id, inline: true },
        )
          .setFooter({ text: 'Rules control' })
          .setTimestamp(),
      ],
    });
    return;
  }

  if (interaction.customId.startsWith('race_finish_modal:')) {
    const [, eventId, ownerId] = interaction.customId.split(':');

    if (interaction.user.id !== ownerId) {
      await interaction.reply({
        content: 'Hanya admin pembuat event yang bisa input hasil balapan.',
        ephemeral: true,
      });
      return;
    }

    const winners = parseRaceWinners(interaction.fields.getTextInputValue('winners'));
    const resultNotes = interaction.fields.getTextInputValue('result_notes') || null;

    if (winners.length === 0) {
      await interaction.reply({
        content: 'Masukkan minimal satu pemenang. Pisahkan dengan koma, misalnya `Lyva, Fenzane, Nadim`.',
        ephemeral: true,
      });
      return;
    }

    const currentRace = await fetchLaravelRace(config, eventId).catch(() => null);

    if (!currentRace) {
      await interaction.reply({
        content: `Event #${eventId} tidak ditemukan atau belum bisa diambil dari Laravel.`,
        ephemeral: true,
      });
      return;
    }

    const updated = await updateLaravelRace(config, eventId, {
      status: 'finished',
      meta: {
        ...(currentRace.meta ?? {}),
        winners,
        result_notes: resultNotes,
        finished_at: new Date().toISOString(),
      },
    });

    await syncRacePanelMessage(eventId);

    await interaction.reply({
      content: currentRace.meta?.announce_role_id ? `<@&${currentRace.meta.announce_role_id}>` : null,
      embeds: [
        buildRaceResultEmbed({
          id: updated.id,
          title: currentRace.title,
          winners,
          notes: resultNotes,
        }),
      ],
    });
    return;
  }

  if (!interaction.customId.startsWith('race_join_modal:')) {
    return;
  }

  const eventId = interaction.customId.split(':')[1];
  const robloxUsername = interaction.fields.getTextInputValue('roblox_username');
  const notes = interaction.fields.getTextInputValue('notes');

  const registration = await joinLaravelRace(config, eventId, {
    discord_user_id: interaction.user.id,
    discord_username: interaction.user.username,
    roblox_username: robloxUsername,
    notes: notes || null,
  });

  await interaction.reply({
    embeds: [
      new EmbedBuilder()
        .setColor(0x22c55e)
        .setTitle('Form Pendaftaran Terkirim')
        .setDescription(`Kamu berhasil daftar ke event #${registration.race_event_id}.`)
        .addFields(
          { name: 'Username Roblox', value: registration.roblox_username ?? robloxUsername, inline: true },
          { name: 'Display Name', value: registration.roblox_display_name ?? '-', inline: true },
          { name: 'Status', value: registration.status, inline: true },
        )
        .setFooter({ text: 'Pantau channel ini untuk info start balapan.' })
        .setTimestamp(),
    ],
    ephemeral: true,
  });

  await syncRacePanelMessage(eventId);
}

async function replyRaceList(interaction, useEditReply = false) {
  const races = await fetchLaravelRaces(config).catch(() => null);

  if (!races?.items?.length) {
    const payload = { content: 'Belum ada event balap yang dibuat.' };
    if (useEditReply) {
      await interaction.editReply(payload);
    } else {
      await interaction.reply({ ...payload, ephemeral: true });
    }
    return;
  }

  const embed = buildModernRaceListEmbed(races.items);

  if (useEditReply) {
    await interaction.editReply({ embeds: [embed] });
    return;
  }

  await interaction.reply({ embeds: [embed], ephemeral: true });
}

function buildRaceListEmbed(items) {
  const embed = new EmbedBuilder()
    .setColor(0x7c3aed)
    .setTitle('🏁 Papan Event Balap')
    .setDescription('Daftar event balap terbaru yang bisa dilihat atau diikuti komunitas.')
    .setTimestamp();

  items.slice(0, 5).forEach((race) => {
    embed.addFields({
      name: `#${race.id} ${race.title}`,
      value: `Status: ${formatRaceStatus(race.status)}\nSlot: ${buildProgressBar(race.participants_count, race.max_players)} ${race.participants_count}/${race.max_players}\nBiaya: ${race.entry_fee_robux} R$`,
      inline: false,
    });
  });

  return embed;
}

function formatRaceStatus(status) {
  return String(status).replaceAll('_', ' ');
}

function subcommandIsPrivate(subcommand) {
  return !['create', 'finish'].includes(subcommand);
}

function buildProgressBar(current, total) {
  const safeTotal = Math.max(total, 1);
  const size = 8;
  const filled = Math.max(0, Math.min(size, Math.round((current / safeTotal) * size)));

  return `${'█'.repeat(filled)}${'░'.repeat(size - filled)}`;
}

async function syncRacePanelMessage(eventId) {
  const race = await fetchLaravelRace(config, eventId).catch(() => null);

  if (!race?.meta?.created_message_channel_id || !race?.meta?.created_message_id) {
    return;
  }

  const channel = await client.channels.fetch(race.meta.created_message_channel_id).catch(() => null);
  if (!channel?.isTextBased()) {
    return;
  }

  const message = await channel.messages.fetch(race.meta.created_message_id).catch(() => null);
  if (!message) {
    return;
  }

  const embed = buildModernRacePanelEmbed({
    id: race.id,
    title: race.title,
    status: race.status,
    max_players: race.max_players,
    participants_count: race.participants_count ?? 0,
    entry_fee_robux: race.entry_fee_robux,
    starts_at: race.starts_at,
    notes: race.notes,
    host: race.created_by_name,
    meta: race.meta,
  });

  const joinButton = ButtonBuilder.from(message.components[0].components[0])
    .setDisabled(race.status !== 'registration_open' || (race.participants_count ?? 0) >= race.max_players);
  const listButton = ButtonBuilder.from(message.components[0].components[1]);
  const closeButton = ButtonBuilder.from(message.components[1].components[0])
    .setDisabled(race.status !== 'registration_open');
  const startButton = ButtonBuilder.from(message.components[1].components[1])
    .setDisabled(['started', 'finished'].includes(race.status));
  const finishButton = message.components[1].components[2]
    ? ButtonBuilder.from(message.components[1].components[2])
    : new ButtonBuilder()
      .setCustomId(`race_finish_open:${race.id}:${race.created_by_discord_id ?? ''}`)
      .setLabel('Input Hasil')
      .setStyle(ButtonStyle.Secondary);
  finishButton.setDisabled(!['started', 'registration_closed'].includes(race.status) || race.status === 'finished');

  await message.edit({
    content: race.meta?.announce_role_id ? `<@&${race.meta.announce_role_id}>` : null,
    embeds: [embed],
    components: [
      new ActionRowBuilder().addComponents(joinButton, listButton),
      new ActionRowBuilder().addComponents(closeButton, startButton, finishButton),
    ],
  }).catch(() => null);
}

function buildRacePanelEmbed(race) {
  const embed = new EmbedBuilder()
    .setColor(statusColor(race.status))
    .setTitle(`🏎️ Event Balap #${race.id}`)
    .setDescription(`**${race.title}**`)
    .addFields(
      { name: 'Status', value: `${statusEmoji(race.status)} ${formatRaceStatus(race.status)}`, inline: true },
      { name: 'Slot', value: `${race.max_players} pemain`, inline: true },
      { name: 'Biaya Masuk', value: `${race.entry_fee_robux} R$`, inline: true },
      { name: 'Host', value: race.host ?? '-', inline: true },
      { name: 'Progress Slot', value: `${buildProgressBar(race.participants_count, race.max_players)} ${race.participants_count}/${race.max_players}`, inline: false },
      { name: 'Countdown', value: race.starts_at ? `<t:${Math.floor(new Date(race.starts_at).getTime() / 1000)}:R>` : 'Belum dijadwalkan', inline: true },
      { name: 'Mulai Pada', value: race.starts_at ? `<t:${Math.floor(new Date(race.starts_at).getTime() / 1000)}:F>` : 'Belum dijadwalkan', inline: false },
      { name: 'Cara Daftar', value: 'Klik `Daftar Balapan`, isi form singkat, lalu tunggu pengumuman start.', inline: false },
    )
    .setFooter({ text: 'Panel balapan komunitas' })
    .setTimestamp();

  if (race.notes) {
    embed.addFields({ name: 'Aturan / Catatan', value: race.notes, inline: false });
  }

  return embed;
}

function statusEmoji(status) {
  const normalized = String(status);
  if (normalized === 'registration_open') return '🟢';
  if (normalized === 'registration_closed') return '🟠';
  if (normalized === 'started') return '🔴';
  return '⚪';
}

function statusColor(status) {
  const normalized = String(status);
  if (normalized === 'registration_open') return 0xdc2626;
  if (normalized === 'registration_closed') return 0xf59e0b;
  if (normalized === 'started') return 0x2563eb;
  if (normalized === 'finished') return 0xf59e0b;
  return 0x6b7280;
}

function buildModernRaceListEmbed(items) {
  const embed = new EmbedBuilder()
    .setColor(0x7c3aed)
    .setAuthor({ name: 'LYVA Racing Control' })
    .setTitle('Papan Event Balap')
    .setDescription('Event balap aktif yang sedang dibuka, dipersiapkan, atau sudah berjalan.')
    .setFooter({ text: 'Panel balap komunitas • live board' })
    .setTimestamp();

  items.slice(0, 5).forEach((race) => {
    embed.addFields({
      name: `${modernStatusEmoji(race.status)} Event #${race.id} • ${race.title}`,
      value: `${buildModernRaceStatusLine(race.status)}\nSlot: ${buildModernProgressBar(race.participants_count, race.max_players)} ${race.participants_count}/${race.max_players}\nBiaya Masuk: ${formatModernRobux(race.entry_fee_robux)}`,
      inline: false,
    });
  });

  return embed;
}

function buildModernRacePanelEmbed(race) {
  const countdown = buildModernCountdownText(race.starts_at);
  const occupancy = `${race.participants_count}/${race.max_players}`;
  const winnerSummary = buildWinnerSummary(race.meta?.winners ?? []);

  const embed = new EmbedBuilder()
    .setColor(statusColor(race.status))
    .setAuthor({ name: 'LYVA Racing Control' })
    .setTitle(`${buildModernPanelAccent(race.status)} Event Balap #${race.id}`)
    .setDescription([
      `**${race.title}**`,
      buildModernRaceStatusLine(race.status),
      countdown.hero,
    ].join('\n'))
    .addFields(
      { name: 'Status Lomba', value: `${modernStatusEmoji(race.status)} ${formatModernRaceStatus(race.status)}`, inline: true },
      { name: 'Grid Tersedia', value: `${race.max_players} pembalap`, inline: true },
      { name: 'Biaya Masuk', value: formatModernRobux(race.entry_fee_robux), inline: true },
      { name: 'Host', value: race.host ?? '-', inline: true },
      { name: 'Slot Masuk', value: occupancy, inline: true },
      { name: 'Mode Panel', value: 'Interaktif', inline: true },
      { name: 'Progress Slot', value: `${buildModernProgressBar(race.participants_count, race.max_players)} ${occupancy}`, inline: false },
      { name: 'Countdown', value: countdown.relative, inline: true },
      { name: 'Mulai Pada', value: countdown.absolute, inline: true },
      { name: 'Aksi Cepat', value: 'Klik `Daftar Balapan`, isi form singkat, lalu pantau panel ini. Countdown bergerak otomatis dari Discord.', inline: false },
    )
    .setFooter({ text: 'Panel balapan komunitas • live board' })
    .setTimestamp();

  if (race.notes) {
    embed.addFields({ name: 'Brief Balapan', value: race.notes, inline: false });
  }

  if (winnerSummary) {
    embed.addFields({ name: 'Hasil Akhir', value: winnerSummary, inline: false });
  }

  if (race.meta?.result_notes) {
    embed.addFields({ name: 'Catatan Finish', value: race.meta.result_notes, inline: false });
  }

  return embed;
}

function formatModernRaceStatus(status) {
  const normalized = String(status);
  if (normalized === 'registration_open') return 'Pendaftaran Dibuka';
  if (normalized === 'registration_closed') return 'Pendaftaran Ditutup';
  if (normalized === 'started') return 'Sedang Berjalan';
  if (normalized === 'finished') return 'Selesai';
  return 'Draft';
}

function buildModernRaceStatusLine(status) {
  const normalized = String(status);
  if (normalized === 'registration_open') {
    return 'Registrasi masih terbuka. Pembalap baru masih bisa masuk ke grid.';
  }

  if (normalized === 'registration_closed') {
    return 'Registrasi sudah ditutup. Grid sedang dikunci untuk persiapan start.';
  }

  if (normalized === 'started') {
    return 'Balapan sudah dimulai. Panel ini sekarang jadi papan kontrol live.';
  }

  if (normalized === 'finished') {
    return 'Balapan sudah selesai. Pemenang resmi sudah diumumkan di panel ini.';
  }

  return 'Event masih disiapkan oleh admin.';
}

function buildModernProgressBar(current, total) {
  const safeTotal = Math.max(total, 1);
  const size = 8;
  const filled = Math.max(0, Math.min(size, Math.round((current / safeTotal) * size)));

  return `${'■'.repeat(filled)}${'□'.repeat(size - filled)}`;
}

function buildModernCountdownText(startsAt) {
  if (!startsAt) {
    return {
      hero: 'Mesin belum dinyalakan. Jadwal start belum ditentukan.',
      relative: 'Belum dijadwalkan',
      absolute: 'Belum dijadwalkan',
    };
  }

  const timestamp = Math.floor(new Date(startsAt).getTime() / 1000);

  return {
    hero: `Start diperkirakan <t:${timestamp}:R>.`,
    relative: `<t:${timestamp}:R>`,
    absolute: `<t:${timestamp}:F>`,
  };
}

function buildModernPanelAccent(status) {
  const normalized = String(status);
  if (normalized === 'registration_open') return '🏎️';
  if (normalized === 'registration_closed') return '🚧';
  if (normalized === 'started') return '🔥';
  if (normalized === 'finished') return '🏆';
  return '🏁';
}

function modernStatusEmoji(status) {
  const normalized = String(status);
  if (normalized === 'registration_open') return '🟢';
  if (normalized === 'registration_closed') return '🟠';
  if (normalized === 'started') return '🔴';
  if (normalized === 'finished') return '🏆';
  return '⚪';
}

function formatModernRobux(amount) {
  return `${amount} R$`;
}

function extractRuleItems(rawRules) {
  return rawRules
    .replace(/\r/g, '\n')
    .replace(/[|]+/g, '\n')
    .replace(/\s+•\s+/g, '\n')
    .replace(/\s+(?=\d+[.)]\s+)/g, '\n')
    .split(/\n+/)
    .map((rule) => rule.trim())
    .map((rule) => rule.replace(/^\d+[.)]\s*/, ''))
    .map((rule) => rule.replace(/^[-*]+\s*/, ''))
    .map((rule) => rule.replace(/\s{2,}/g, ' '))
    .filter((rule) => rule !== '');
}

function formatRuleList(rules) {
  return rules
    .map((rule, index) => `**${index + 1}.** ${rule}`)
    .join('\n');
}

function refreshRulesAcknowledgementFields(fields, acknowledgement) {
  const preserved = fields.filter((field) => field.name !== 'Sudah Paham');
  const shown = Math.min(acknowledgement.total, 3);
  const remaining = Math.max(acknowledgement.total - shown, 0);
  const suffix = remaining > 0 ? `\n+${remaining} lainnya` : '';

  return [
    ...preserved,
    {
      name: 'Sudah Paham',
      value: acknowledgement.total > 0
        ? `${shown} profil terbaru ditampilkan di bawah.${suffix}\nTotal: **${acknowledgement.total}**`
        : 'Belum ada',
      inline: false,
    },
  ];
}

async function publishRulesPanel({ channel, actor, title, intro, rules, punishment }) {
  const description = [
    intro,
    '',
    formatRuleList(rules),
  ];

  if (punishment) {
    description.push('', '⚠️ **Punishment System**', punishment);
  }

  const embed = new EmbedBuilder()
    .setColor(0x2563eb)
    .setAuthor({ name: 'LYVA Studio • Community Rules' })
    .setTitle(`📘 ${title}`)
    .setDescription(description.join('\n'))
    .addFields(
      { name: 'Diposting Oleh', value: actor, inline: true },
      { name: 'Channel', value: `${channel}`, inline: true },
      { name: 'Total Aturan', value: `${rules.length}`, inline: true },
    )
    .setFooter({ text: 'Community handbook' })
    .setTimestamp();

  return channel.send({
    embeds: [embed],
    components: [
      new ActionRowBuilder().addComponents(
        new ButtonBuilder()
          .setCustomId('rules_acknowledge')
          .setLabel('Saya Paham')
          .setStyle(ButtonStyle.Success),
      ),
    ],
  }).catch(() => null);
}

function rememberGuildSetting(guildId, setting) {
  if (!guildId || !setting) {
    return;
  }

  guildSettingsCache.set(guildId, {
    value: setting,
    expiresAt: Date.now() + GUILD_SETTINGS_CACHE_TTL_MS,
  });
}

async function getGuildSetting(guildId, { force = false } = {}) {
  if (!guildId || !config.internalToken) {
    return null;
  }

  const cached = guildSettingsCache.get(guildId);
  if (!force && cached && cached.expiresAt > Date.now()) {
    return cached.value;
  }

  const setting = await fetchLaravelGuildSettings(config, guildId).catch(() => null);
  if (setting) {
    rememberGuildSetting(guildId, setting);
  }

  return setting;
}

async function handleMessageCreate(message) {
  if (!config.enableMessageContentIntent) {
    return;
  }

  if (!message.inGuild() || !message.guild || !message.member) {
    return;
  }

  if (message.author.bot || !message.content?.trim()) {
    return;
  }

  if (
    message.member.permissions.has(PermissionFlagsBits.Administrator)
    || message.member.permissions.has(PermissionFlagsBits.ManageMessages)
    || message.member.permissions.has(PermissionFlagsBits.BanMembers)
  ) {
    return;
  }

  const guildSetting = await getGuildSetting(message.guildId);
  const spamConfig = getSpamConfig(guildSetting);

  if (!spamConfig.enabled) {
    return;
  }

  const normalizedContent = normalizeSpamContent(message.content);
  if (!normalizedContent) {
    return;
  }

  const trackerKey = `${message.guildId}:${message.author.id}`;
  const now = Date.now();
  const tracker = spamTracker.get(trackerKey) ?? {
    events: [],
    lastTriggeredAt: 0,
  };

  tracker.events = tracker.events.filter((event) => now - event.createdAt <= spamConfig.windowMs);
  tracker.events.push({
    channelId: message.channelId,
    content: normalizedContent,
    rawContent: message.content.trim(),
    createdAt: now,
  });

  spamTracker.set(trackerKey, tracker);

  if (tracker.lastTriggeredAt && now - tracker.lastTriggeredAt <= spamConfig.windowMs) {
    return;
  }

  const matchingEvents = tracker.events.filter((event) => event.content === normalizedContent);
  const distinctChannelIds = [...new Set(matchingEvents.map((event) => event.channelId))];
  const repeatedCount = matchingEvents.length;
  const recentBurst = tracker.events.slice(-spamConfig.threshold);
  const burstDurationMs = recentBurst.length >= spamConfig.threshold
    ? recentBurst.at(-1).createdAt - recentBurst[0].createdAt
    : null;
  const burstChannelIds = [...new Set(recentBurst.map((event) => event.channelId))];
  const isRapidBurst = recentBurst.length >= spamConfig.threshold;

  if (distinctChannelIds.length < spamConfig.threshold && repeatedCount < spamConfig.threshold && !isRapidBurst) {
    return;
  }

  tracker.lastTriggeredAt = now;
  let triggerSummary = distinctChannelIds.length >= spamConfig.threshold
    ? `Spam sama di ${distinctChannelIds.length} channel`
    : `${repeatedCount} pesan sama beruntun`;
  let triggerEvents = matchingEvents;
  let triggerChannelIds = distinctChannelIds;

  if (isRapidBurst) {
    const burstSeconds = Math.max(1, Math.ceil(burstDurationMs / 1000));
    triggerSummary = `${recentBurst.length} pesan cepat dalam ${burstSeconds} detik`;
    triggerEvents = recentBurst;
    triggerChannelIds = burstChannelIds;
  }

  await enforceSpamModeration(message, spamConfig, triggerEvents, triggerChannelIds, triggerSummary);
  spamTracker.delete(trackerKey);
}

function getSpamConfig(guildSetting) {
  const threshold = Math.max(2, Math.min(10, Number(guildSetting?.spam_threshold ?? DEFAULT_SPAM_THRESHOLD)));
  const windowSeconds = Math.max(10, Math.min(300, Number(guildSetting?.spam_window_seconds ?? DEFAULT_SPAM_WINDOW_SECONDS)));

  return {
    enabled: Boolean(guildSetting?.spam_enabled),
    threshold,
    windowMs: windowSeconds * 1000,
    announcementChannelId: guildSetting?.spam_announcement_channel_id ?? null,
    logChannelId: guildSetting?.spam_log_channel_id ?? null,
  };
}

function normalizeSpamContent(content) {
  const normalized = String(content)
    .toLowerCase()
    .replace(/https?:\/\/\S+/g, ' ')
    .replace(/[^\p{L}\p{N}\s!?.,-]/gu, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  if (normalized === '') {
    return null;
  }

  const alphanumeric = normalized.replace(/[^\p{L}\p{N}]/gu, '');

  if (alphanumeric.length === 0) {
    return null;
  }

  return normalized.slice(0, 120);
}

async function enforceSpamModeration(message, spamConfig, matchingEvents, distinctChannelIds, triggerSummary) {
  const lockKey = `${message.guildId}:${message.author.id}`;

  if (spamEnforcementLocks.has(lockKey)) {
    return;
  }

  spamEnforcementLocks.add(lockKey);

  try {
    const deletedCount = await purgeRecentMessagesForUser(message.guild, message.author.id);
    const spamSample = truncateText(matchingEvents.at(-1)?.rawContent ?? message.content, 160);
    const formattedChannels = formatSpamChannels(message.guild, distinctChannelIds);
    const reason = `Auto-ban ${triggerSummary.toLowerCase()}`;
    let banSucceeded = false;
    let banFailureReason = null;

    try {
      await message.guild.members.ban(message.author.id, { reason });
      banSucceeded = true;
      console.log(`[SPAM GUARD] ${message.author.tag} diban otomatis di guild ${message.guild.id}. Trigger: ${triggerSummary}.`);
    } catch (error) {
      banFailureReason = error?.message ?? 'Ban gagal dijalankan.';
      console.error(`[SPAM GUARD] Gagal ban ${message.author.tag} di guild ${message.guild.id}: ${banFailureReason}`);
    }

    const announcementChannel = spamConfig.announcementChannelId
      ? message.guild.channels.cache.get(spamConfig.announcementChannelId)
      : message.channel;
    const logChannel = spamConfig.logChannelId
      ? message.guild.channels.cache.get(spamConfig.logChannelId)
      : null;

    const announcementEmbed = buildSpamAnnouncementEmbed({
      message,
      spamSample,
      channelCount: distinctChannelIds.length,
      repeatedCount: matchingEvents.length,
      deletedCount,
      banSucceeded,
      formattedChannels,
      banFailureReason,
      triggerSummary,
    });

    if (announcementChannel?.isTextBased()) {
      await announcementChannel.send({
        embeds: [announcementEmbed],
      }).catch(() => null);
    }

    if (logChannel?.isTextBased() && logChannel.id !== announcementChannel?.id) {
      await logChannel.send({
        embeds: [
          buildSpamLogEmbed({
            message,
            spamSample,
            channelCount: distinctChannelIds.length,
            repeatedCount: matchingEvents.length,
            deletedCount,
            banSucceeded,
            formattedChannels,
            banFailureReason,
            triggerSummary,
          }),
        ],
      }).catch(() => null);
    }
  } finally {
    spamEnforcementLocks.delete(lockKey);
  }
}

async function purgeRecentMessagesForUser(guild, userId) {
  let deletedCount = 0;

  const textChannels = guild.channels.cache.filter((channel) =>
    channel.isTextBased()
      && [
        ChannelType.GuildText,
        ChannelType.GuildAnnouncement,
        ChannelType.PublicThread,
        ChannelType.PrivateThread,
        ChannelType.AnnouncementThread,
      ].includes(channel.type),
  );

  for (const channel of textChannels.values()) {
    const messages = await channel.messages.fetch({ limit: MAX_PURGE_MESSAGES_PER_CHANNEL }).catch(() => null);
    if (!messages) {
      continue;
    }

    const userMessages = messages.filter((item) =>
      item.author?.id === userId
        && Date.now() - item.createdTimestamp <= MAX_PURGE_AGE_MS,
    );

    if (userMessages.size === 0) {
      continue;
    }

    if (userMessages.size >= 2) {
      const bulkDeleted = await channel.bulkDelete(userMessages, true).catch(() => null);
      if (bulkDeleted) {
        deletedCount += bulkDeleted.size;
        continue;
      }
    }

    for (const item of userMessages.values()) {
      const removed = await item.delete().then(() => 1).catch(() => 0);
      deletedCount += removed;
    }
  }

  return deletedCount;
}

function buildSpamAnnouncementEmbed({
  message,
  spamSample,
  channelCount,
  repeatedCount,
  deletedCount,
  banSucceeded,
  formattedChannels,
  banFailureReason,
  triggerSummary,
}) {
  const embed = new EmbedBuilder()
    .setColor(banSucceeded ? 0xdc2626 : 0xf97316)
    .setAuthor({ name: 'LYVA Studio • Spam Guard' })
    .setTitle('Spam Lintas Channel Terdeteksi')
    .setDescription([
      `**${message.author.username}** telah ditindak karena mengirim pesan spam berulang ke banyak channel.`,
      '',
      banSucceeded
        ? 'Aksi otomatis: pesan recent dihapus dan akun langsung diban.'
        : 'Aksi otomatis berjalan, tetapi proses ban gagal. Cek izin bot di server.',
    ].join('\n'))
    .setThumbnail(message.author.displayAvatarURL())
    .addFields(
      { name: 'User', value: `${message.author} (\`${message.author.id}\`)`, inline: true },
      { name: 'Status', value: banSucceeded ? 'Auto-ban berhasil' : 'Ban gagal', inline: true },
      { name: 'Pesan Dihapus', value: `${deletedCount}`, inline: true },
      { name: 'Trigger', value: triggerSummary, inline: true },
      { name: 'Jumlah Pesan Sama', value: `${repeatedCount}`, inline: true },
      { name: 'Jumlah Channel', value: `${channelCount}`, inline: true },
      { name: 'Channel Terdampak', value: formattedChannels, inline: false },
      { name: 'Contoh Spam', value: `\`${spamSample}\``, inline: false },
    )
    .setFooter({ text: 'Spam guard otomatis' })
    .setTimestamp();

  if (!banSucceeded && banFailureReason) {
    embed.addFields({ name: 'Catatan Bot', value: truncateText(banFailureReason, 160), inline: false });
  }

  return embed;
}

function buildSpamLogEmbed({
  message,
  spamSample,
  channelCount,
  repeatedCount,
  deletedCount,
  banSucceeded,
  formattedChannels,
  banFailureReason,
  triggerSummary,
}) {
  const embed = new EmbedBuilder()
    .setColor(0x1d4ed8)
    .setAuthor({ name: 'LYVA Studio • Moderation Log' })
    .setTitle('Log Auto-Ban Spam')
    .setDescription('Spam guard mendeteksi pola spam lintas channel dan langsung mengambil tindakan.')
    .addFields(
      { name: 'User', value: `${message.author.tag} (\`${message.author.id}\`)`, inline: false },
      { name: 'Display Name', value: message.member?.displayName ?? message.author.username, inline: true },
      { name: 'Status Ban', value: banSucceeded ? 'Berhasil' : 'Gagal', inline: true },
      { name: 'Pesan Dihapus', value: `${deletedCount}`, inline: true },
      { name: 'Trigger', value: triggerSummary, inline: true },
      { name: 'Jumlah Pesan Sama', value: `${repeatedCount}`, inline: true },
      { name: 'Jumlah Channel', value: `${channelCount}`, inline: true },
      { name: 'Channel Terdampak', value: formattedChannels, inline: false },
      { name: 'Contoh Spam', value: `\`${spamSample}\``, inline: false },
    )
    .setTimestamp();

  if (banFailureReason) {
    embed.addFields({ name: 'Error Ban', value: truncateText(banFailureReason, 180), inline: false });
  }

  return embed;
}

function formatSpamChannels(guild, channelIds) {
  return channelIds
    .map((channelId) => guild.channels.cache.get(channelId))
    .filter(Boolean)
    .map((channel) => `${channel}`)
    .join(', ') || 'Channel tidak terdeteksi';
}

function truncateText(value, maxLength) {
  const text = String(value ?? '').trim();

  if (text.length <= maxLength) {
    return text || '-';
  }

  return `${text.slice(0, Math.max(0, maxLength - 3))}...`;
}

function sanitizeChannelName(value) {
  return String(value)
    .toLowerCase()
    .replace(/[^a-z0-9-]/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '') || 'user';
}

function formatTicketType(type) {
  if (type === 'purchase') return 'Bantuan Pembelian';
  if (type === 'payment') return 'Pembayaran';
  return 'Bantuan Lainnya';
}

async function buildTicketTranscript(channel) {
  if (!channel?.isTextBased()) {
    return 'Transcript tidak tersedia.';
  }

  const messages = await channel.messages.fetch({ limit: 100 }).catch(() => null);
  if (!messages) {
    return 'Transcript tidak tersedia.';
  }

  return messages
    .sort((left, right) => left.createdTimestamp - right.createdTimestamp)
    .map((message) => {
      const timestamp = new Date(message.createdTimestamp).toISOString();
      const author = message.author?.tag ?? message.author?.username ?? 'Unknown';
      const content = message.content || '[embed/attachment]';

      return `[${timestamp}] ${author}: ${content}`;
    })
    .join('\n');
}

function getDeployAnnouncementStyle(type) {
  const normalized = String(type);

  if (normalized === 'update') {
    return {
      color: 0x2563eb,
      icon: '🛠️',
      header: 'Update Broadcast',
      badge: 'Mode update aktif',
      mode: 'Update',
      broadcastLabel: 'Update server',
      footer: 'Deployment update',
    };
  }

  if (normalized === 'maintenance') {
    return {
      color: 0xdc2626,
      icon: '🧰',
      header: 'Maintenance Broadcast',
      badge: 'Mode maintenance aktif',
      mode: 'Maintenance',
      broadcastLabel: 'Maintenance notice',
      footer: 'Maintenance announcement',
    };
  }

  if (normalized === 'hotfix') {
    return {
      color: 0xf59e0b,
      icon: '⚡',
      header: 'Hotfix Broadcast',
      badge: 'Mode hotfix aktif',
      mode: 'Hotfix',
      broadcastLabel: 'Hotfix deployed',
      footer: 'Hotfix announcement',
    };
  }

  if (normalized === 'event') {
    return {
      color: 0x7c3aed,
      icon: '🎉',
      header: 'Event Broadcast',
      badge: 'Mode event aktif',
      mode: 'Event',
      broadcastLabel: 'Event announcement',
      footer: 'Event announcement',
    };
  }

  return {
    color: 0xf97316,
    icon: '🚀',
    header: 'Announcement Broadcast',
    badge: 'Mode announcement aktif',
    mode: 'Announcement',
    broadcastLabel: 'Server announcement',
    footer: 'Deployment announcement',
  };
}

function buildAnnouncementMention(mentionMode, roleId) {
  const parts = [];

  if (mentionMode === 'here') {
    parts.push('@here');
  } else if (mentionMode === 'everyone') {
    parts.push('@everyone');
  }

  if (roleId) {
    parts.push(`<@&${roleId}>`);
  }

  return parts.join(' ');
}

function resolveMemberRoleLabel(member) {
  if (!member?.roles?.cache) {
    return 'Tanpa role khusus';
  }

  const role = member.roles.cache
    .filter((item) => item.name !== '@everyone')
    .sort((left, right) => right.position - left.position)
    .first();

  return role ? role.name : 'Tanpa role khusus';
}

async function resolveVerifiedRole(guild) {
  if (!guild) {
    return null;
  }

  const guildSetting = await fetchLaravelGuildSettings(config, guild.id).catch(() => null);
  const roleId = guildSetting?.verification_role_id || config.verifiedRoleId;

  if (!roleId) {
    return null;
  }

  return guild.roles.cache.get(roleId) ?? null;
}

function parseRaceWinners(rawValue) {
  return rawValue
    .split(/[\n,]+/)
    .map((winner) => winner.trim())
    .filter((winner, index, array) => winner !== '' && array.indexOf(winner) === index);
}

function buildWinnerSummary(winners) {
  if (!Array.isArray(winners) || winners.length === 0) {
    return null;
  }

  return winners
    .map((winner, index) => `${getPlacementLabel(index + 1)} ${winner}`)
    .join('\n');
}

function getPlacementLabel(position) {
  if (position === 1) return '🥇 Juara 1';
  if (position === 2) return '🥈 Juara 2';
  if (position === 3) return '🥉 Juara 3';
  return `🏅 Posisi ${position}`;
}

function buildRaceResultEmbed(result) {
  const winners = buildWinnerSummary(result.winners) ?? 'Belum ada pemenang yang diinput.';

  const embed = new EmbedBuilder()
    .setColor(0xf59e0b)
    .setAuthor({ name: 'LYVA Racing Control' })
    .setTitle(`🏆 Hasil Resmi Event #${result.id}`)
    .setDescription(`**${result.title}**\nPodium resmi balapan sudah masuk.`)
    .addFields(
      { name: 'Pemenang', value: winners, inline: false },
      { name: 'Status Event', value: 'Selesai', inline: true },
      { name: 'Jumlah Posisi', value: `${result.winners.length}`, inline: true },
    )
    .setFooter({ text: 'Pengumuman hasil balapan' })
    .setTimestamp();

  if (result.notes) {
    embed.addFields({ name: 'Catatan Admin', value: result.notes, inline: false });
  }

  return embed;
}

console.log(`Loaded ${commandDefinitions.length} Discord commands.`);
await client.login(config.botToken);
