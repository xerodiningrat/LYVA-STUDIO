import { ChannelType, SlashCommandBuilder } from 'discord.js';

export const commandDefinitions = [
  new SlashCommandBuilder()
    .setName('ping')
    .setDescription('Cek apakah bot hidup.'),
  new SlashCommandBuilder()
    .setName('status')
    .setDescription('Ringkasan health dashboard Roblox ops.'),
  new SlashCommandBuilder()
    .setName('sales')
    .setDescription('Command ringkasan sales Roblox.')
    .addSubcommand((subcommand) =>
      subcommand.setName('summary').setDescription('Ringkasan sales hari ini.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('live').setDescription('Event sales terbaru yang terpantau.'),
    ),
  new SlashCommandBuilder()
    .setName('server')
    .setDescription('Monitoring server dan incident.')
    .addSubcommand((subcommand) =>
      subcommand.setName('health').setDescription('Cek kondisi server dan game.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('shutdowns').setDescription('Lihat shutdown atau incident terbaru.'),
    ),
  new SlashCommandBuilder()
    .setName('deploy')
    .setDescription('Deploy log dan update place.')
    .addSubcommand((subcommand) =>
      subcommand.setName('log').setDescription('Lihat log deploy terbaru.'),
    )
    .addSubcommand((subcommand) =>
      subcommand
        .setName('announce')
        .setDescription('Kirim pengumuman deploy ke channel pilihan.')
        .addStringOption((option) =>
          option.setName('message').setDescription('Catatan deploy untuk tim.').setRequired(true),
        )
        .addChannelOption((option) =>
          option
            .setName('channel')
            .setDescription('Channel tujuan pengumuman.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(true),
        )
        .addStringOption((option) =>
          option
            .setName('type')
            .setDescription('Tipe pengumuman.')
            .setChoices(
              { name: 'Announcement', value: 'announcement' },
              { name: 'Update', value: 'update' },
              { name: 'Maintenance', value: 'maintenance' },
              { name: 'Hotfix', value: 'hotfix' },
              { name: 'Event', value: 'event' },
            )
            .setRequired(false),
        )
        .addStringOption((option) =>
          option
            .setName('mention')
            .setDescription('Mention yang akan ikut dikirim.')
            .setChoices(
              { name: 'Tanpa mention', value: 'none' },
              { name: '@here', value: 'here' },
              { name: '@everyone', value: 'everyone' },
            )
            .setRequired(false),
        )
        .addRoleOption((option) =>
          option.setName('role').setDescription('Role tambahan yang akan di-mention.').setRequired(false),
        )
        .addStringOption((option) =>
          option.setName('title').setDescription('Judul pengumuman deploy.').setRequired(false),
        ),
    ),
  new SlashCommandBuilder()
    .setName('report')
    .setDescription('Laporan player atau bug.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('player')
        .setDescription('Buat player report.')
        .addStringOption((option) =>
          option.setName('player').setDescription('Nama player yang dilaporkan.').setRequired(true),
        )
        .addStringOption((option) =>
          option.setName('reason').setDescription('Alasan laporan.').setRequired(true),
        ),
    )
    .addSubcommand((subcommand) =>
      subcommand
        .setName('bug')
        .setDescription('Buat bug report.')
        .addStringOption((option) =>
          option.setName('summary').setDescription('Ringkasan bug.').setRequired(true),
        )
        .addStringOption((option) =>
          option
            .setName('severity')
            .setDescription('Level severity.')
            .setChoices(
              { name: 'low', value: 'low' },
              { name: 'medium', value: 'medium' },
              { name: 'high', value: 'high' },
            ),
        ),
    ),
  new SlashCommandBuilder()
    .setName('verify')
    .setDescription('Verifikasi Discord ke Roblox.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('setup')
        .setDescription('Kirim panel verifikasi ke channel pilihan.')
        .addChannelOption((option) =>
          option
            .setName('channel')
            .setDescription('Channel tujuan panel verifikasi.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(true),
        )
        .addRoleOption((option) =>
          option.setName('role').setDescription('Role yang diberikan setelah verifikasi berhasil.').setRequired(false),
        )
        .addStringOption((option) =>
          option.setName('title').setDescription('Judul panel verifikasi.').setRequired(false),
        ),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('start').setDescription('Mulai proses verifikasi akun.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('check').setDescription('Cek status verifikasi.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('unlink').setDescription('Putuskan akun Discord dan Roblox.'),
    ),
  new SlashCommandBuilder()
    .setName('webhook')
    .setDescription('Tes alur webhook dan alert.')
    .addSubcommand((subcommand) =>
      subcommand.setName('test').setDescription('Tes response bot dan integrasi webhook.'),
    ),
  new SlashCommandBuilder()
    .setName('ticket')
    .setDescription('Setup panel ticket bantuan dan pembelian.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('setup')
        .setDescription('Kirim panel ticket ke channel pilihan.')
        .addChannelOption((option) =>
          option
            .setName('channel')
            .setDescription('Channel tujuan panel ticket.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(true),
        )
        .addChannelOption((option) =>
          option
            .setName('category')
            .setDescription('Category tempat channel ticket dibuat.')
            .addChannelTypes(ChannelType.GuildCategory)
            .setRequired(false),
        )
        .addRoleOption((option) =>
          option.setName('support_role').setDescription('Role staff yang bisa melihat ticket.').setRequired(false),
        )
        .addChannelOption((option) =>
          option
            .setName('log_channel')
            .setDescription('Channel untuk kirim transcript ticket.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(false),
        )
        .addStringOption((option) =>
          option.setName('title').setDescription('Judul panel ticket.').setRequired(false),
        ),
    ),
  new SlashCommandBuilder()
    .setName('moderation')
    .setDescription('Setup auto-moderation dan anti-spam server.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('setup-spam')
        .setDescription('Aktifkan auto-ban spam lintas channel.')
        .addChannelOption((option) =>
          option
            .setName('announcement_channel')
            .setDescription('Channel publik untuk pengumuman user yang diban.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(true),
        )
        .addChannelOption((option) =>
          option
            .setName('log_channel')
            .setDescription('Channel staff untuk log detail spam.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(false),
        )
        .addIntegerOption((option) =>
          option
            .setName('threshold')
            .setDescription('Minimal jumlah channel berbeda untuk trigger ban.')
            .setMinValue(2)
            .setMaxValue(10)
            .setRequired(false),
        )
        .addIntegerOption((option) =>
          option
            .setName('window_seconds')
            .setDescription('Jendela waktu deteksi spam dalam detik.')
            .setMinValue(10)
            .setMaxValue(300)
            .setRequired(false),
        ),
    ),
  new SlashCommandBuilder()
    .setName('rules')
    .setDescription('Kirim panel rules ke channel pilihan.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('create')
        .setDescription('Buka form rules yang lebih rapi untuk teks panjang.')
        .addChannelOption((option) =>
          option
            .setName('channel')
            .setDescription('Channel tujuan rules.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(true),
        ),
    )
    .addSubcommand((subcommand) =>
      subcommand
        .setName('post')
        .setDescription('Posting rules komunitas ke channel tertentu.')
        .addChannelOption((option) =>
          option
            .setName('channel')
            .setDescription('Channel tujuan rules.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(true),
        )
        .addStringOption((option) =>
          option
            .setName('rules')
            .setDescription('Isi rules. Pisahkan setiap aturan dengan baris baru.')
            .setRequired(true),
        )
        .addStringOption((option) =>
          option.setName('title').setDescription('Judul panel rules.').setRequired(false),
        ),
    ),
  new SlashCommandBuilder()
    .setName('script')
    .setDescription('Ambil template script Roblox.')
    .addSubcommand((subcommand) =>
      subcommand.setName('devproduct').setDescription('Kirim script developer product reporter.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('gamepass-server').setDescription('Kirim script server untuk game pass reporter.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('gamepass-client').setDescription('Kirim LocalScript untuk game pass reporter.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('catalog').setDescription('Kirim template product catalog.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('remote').setDescription('Kirim panduan RemoteEvent game pass.'),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('readme').setDescription('Kirim panduan setup Roblox script.'),
    ),
  new SlashCommandBuilder()
    .setName('race')
    .setDescription('Kelola event balap komunitas.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('create')
        .setDescription('Buat event balap baru.')
        .addStringOption((option) =>
          option.setName('title').setDescription('Nama event balap.').setRequired(true),
        )
        .addIntegerOption((option) =>
          option.setName('max_players').setDescription('Jumlah slot pemain.').setRequired(true),
        )
        .addIntegerOption((option) =>
          option.setName('entry_fee').setDescription('Biaya masuk Robux.').setRequired(false),
        )
        .addStringOption((option) =>
          option.setName('notes').setDescription('Aturan singkat event.').setRequired(false),
        )
        .addIntegerOption((option) =>
          option.setName('mulai_dalam_menit').setDescription('Countdown mulai balapan dalam menit.').setRequired(false),
        )
        .addRoleOption((option) =>
          option.setName('role_event').setDescription('Role yang akan di-mention saat event dibuat.').setRequired(false),
        ),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('list').setDescription('Lihat event balap terbaru.'),
    )
    .addSubcommand((subcommand) =>
      subcommand
        .setName('join')
        .setDescription('Daftar ke event balap.')
        .addIntegerOption((option) =>
          option.setName('event_id').setDescription('ID event balap.').setRequired(true),
        )
        .addStringOption((option) =>
          option.setName('roblox_username').setDescription('Username Roblox kamu.').setRequired(true),
        )
        .addStringOption((option) =>
          option.setName('notes').setDescription('Catatan tambahan.').setRequired(false),
        ),
    )
    .addSubcommand((subcommand) =>
      subcommand
        .setName('finish')
        .setDescription('Input hasil akhir dan umumkan pemenang balapan.')
        .addIntegerOption((option) =>
          option.setName('event_id').setDescription('ID event balap.').setRequired(true),
        )
        .addStringOption((option) =>
          option
            .setName('winners')
            .setDescription('Urutan pemenang. Pisahkan dengan koma, contoh: Lyva, Fenzane, Nadim')
            .setRequired(true),
        )
        .addStringOption((option) =>
          option.setName('notes').setDescription('Catatan hasil akhir atau hadiah.').setRequired(false),
        ),
    ),
  new SlashCommandBuilder()
    .setName('titile')
    .setDescription('Panel claim VIP custom title.')
    .addSubcommand((subcommand) =>
      subcommand
        .setName('setup')
        .setDescription('Kirim panel claim title ke channel pilihan.')
        .addChannelOption((option) =>
          option
            .setName('channel')
            .setDescription('Channel tujuan panel title.')
            .addChannelTypes(ChannelType.GuildText, ChannelType.GuildAnnouncement)
            .setRequired(false),
        ),
    )
    .addSubcommand((subcommand) =>
      subcommand.setName('list').setDescription('Lihat daftar claim title yang masuk.'),
    ),
].map((command) => command.toJSON());
