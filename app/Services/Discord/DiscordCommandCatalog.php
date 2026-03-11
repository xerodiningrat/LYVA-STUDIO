<?php

namespace App\Services\Discord;

class DiscordCommandCatalog
{
    private const CHANNEL_TYPE_GUILD_TEXT = 0;

    private const CHANNEL_TYPE_GUILD_CATEGORY = 4;

    private const CHANNEL_TYPE_GUILD_ANNOUNCEMENT = 5;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'name' => 'ping',
                'description' => 'Cek apakah bot hidup.',
                'type' => 1,
            ],
            [
                'name' => 'status',
                'description' => 'Ringkasan health dashboard Roblox ops.',
                'type' => 1,
            ],
            [
                'name' => 'sales',
                'description' => 'Command ringkasan sales Roblox.',
                'type' => 1,
                'options' => [
                    $this->subcommand('summary', 'Ringkasan sales hari ini.'),
                    $this->subcommand('live', 'Event sales terbaru yang terpantau.'),
                ],
            ],
            [
                'name' => 'server',
                'description' => 'Monitoring server dan incident.',
                'type' => 1,
                'options' => [
                    $this->subcommand('health', 'Cek kondisi server dan game.'),
                    $this->subcommand('shutdowns', 'Lihat shutdown atau incident terbaru.'),
                ],
            ],
            [
                'name' => 'deploy',
                'description' => 'Deploy log dan update place.',
                'type' => 1,
                'options' => [
                    $this->subcommand('log', 'Lihat log deploy terbaru.'),
                    $this->subcommand('announce', 'Kirim pengumuman deploy ke channel pilihan.', [
                        $this->stringOption('message', 'Catatan deploy untuk tim.', true),
                        $this->channelOption(
                            'channel',
                            'Channel tujuan pengumuman.',
                            true,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->stringOption('type', 'Tipe pengumuman.', false, [
                            ['name' => 'Announcement', 'value' => 'announcement'],
                            ['name' => 'Update', 'value' => 'update'],
                            ['name' => 'Maintenance', 'value' => 'maintenance'],
                            ['name' => 'Hotfix', 'value' => 'hotfix'],
                            ['name' => 'Event', 'value' => 'event'],
                        ]),
                        $this->stringOption('mention', 'Mention yang akan ikut dikirim.', false, [
                            ['name' => 'Tanpa mention', 'value' => 'none'],
                            ['name' => '@here', 'value' => 'here'],
                            ['name' => '@everyone', 'value' => 'everyone'],
                        ]),
                        $this->roleOption('role', 'Role tambahan yang akan di-mention.'),
                        $this->stringOption('title', 'Judul pengumuman deploy.'),
                    ]),
                ],
            ],
            [
                'name' => 'report',
                'description' => 'Laporan player atau bug.',
                'type' => 1,
                'options' => [
                    $this->subcommand('player', 'Buat player report.', [
                        $this->stringOption('player', 'Nama player yang dilaporkan.', true),
                        $this->stringOption('reason', 'Alasan laporan.', true),
                    ]),
                    $this->subcommand('bug', 'Buat bug report.', [
                        $this->stringOption('summary', 'Ringkasan bug.', true),
                        $this->stringOption('severity', 'Level severity.', false, [
                            ['name' => 'low', 'value' => 'low'],
                            ['name' => 'medium', 'value' => 'medium'],
                            ['name' => 'high', 'value' => 'high'],
                        ]),
                    ]),
                ],
            ],
            [
                'name' => 'verify',
                'description' => 'Verifikasi Discord ke Roblox.',
                'type' => 1,
                'options' => [
                    $this->subcommand('setup', 'Kirim panel verifikasi ke channel pilihan.', [
                        $this->channelOption(
                            'channel',
                            'Channel tujuan panel verifikasi.',
                            true,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->roleOption('role', 'Role yang diberikan setelah verifikasi berhasil.'),
                        $this->stringOption('title', 'Judul panel verifikasi.'),
                    ]),
                    $this->subcommand('start', 'Mulai proses verifikasi akun.'),
                    $this->subcommand('check', 'Cek status verifikasi.'),
                    $this->subcommand('unlink', 'Putuskan akun Discord dan Roblox.'),
                ],
            ],
            [
                'name' => 'webhook',
                'description' => 'Tes alur webhook dan alert.',
                'type' => 1,
                'options' => [
                    $this->subcommand('test', 'Tes response bot dan integrasi webhook.'),
                ],
            ],
            [
                'name' => 'ticket',
                'description' => 'Setup panel ticket bantuan dan pembelian.',
                'type' => 1,
                'options' => [
                    $this->subcommand('setup', 'Kirim panel ticket ke channel pilihan.', [
                        $this->channelOption(
                            'channel',
                            'Channel tujuan panel ticket.',
                            true,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->channelOption(
                            'category',
                            'Category tempat channel ticket dibuat.',
                            false,
                            [self::CHANNEL_TYPE_GUILD_CATEGORY],
                        ),
                        $this->roleOption('support_role', 'Role staff yang bisa melihat ticket.'),
                        $this->channelOption(
                            'log_channel',
                            'Channel untuk kirim transcript ticket.',
                            false,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->stringOption('title', 'Judul panel ticket.'),
                    ]),
                ],
            ],
            [
                'name' => 'moderation',
                'description' => 'Setup auto-moderation dan anti-spam server.',
                'type' => 1,
                'options' => [
                    $this->subcommand('setup-spam', 'Aktifkan auto-ban spam lintas channel.', [
                        $this->channelOption(
                            'announcement_channel',
                            'Channel publik untuk pengumuman user yang diban.',
                            true,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->channelOption(
                            'log_channel',
                            'Channel staff untuk log detail spam.',
                            false,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->integerOption('threshold', 'Minimal jumlah channel berbeda untuk trigger ban.', false, 2, 10),
                        $this->integerOption('window_seconds', 'Jendela waktu deteksi spam dalam detik.', false, 10, 300),
                    ]),
                ],
            ],
            [
                'name' => 'rules',
                'description' => 'Kirim panel rules ke channel pilihan.',
                'type' => 1,
                'options' => [
                    $this->subcommand('create', 'Buka form rules yang lebih rapi untuk teks panjang.', [
                        $this->channelOption(
                            'channel',
                            'Channel tujuan rules.',
                            true,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                    ]),
                    $this->subcommand('post', 'Posting rules komunitas ke channel tertentu.', [
                        $this->channelOption(
                            'channel',
                            'Channel tujuan rules.',
                            true,
                            [self::CHANNEL_TYPE_GUILD_TEXT, self::CHANNEL_TYPE_GUILD_ANNOUNCEMENT],
                        ),
                        $this->stringOption('rules', 'Isi rules. Pisahkan setiap aturan dengan baris baru.', true),
                        $this->stringOption('title', 'Judul panel rules.'),
                    ]),
                ],
            ],
            [
                'name' => 'script',
                'description' => 'Ambil template script Roblox.',
                'type' => 1,
                'options' => [
                    $this->subcommand('devproduct', 'Kirim script developer product reporter.'),
                    $this->subcommand('gamepass-server', 'Kirim script server untuk game pass reporter.'),
                    $this->subcommand('gamepass-client', 'Kirim LocalScript untuk game pass reporter.'),
                    $this->subcommand('catalog', 'Kirim template product catalog.'),
                    $this->subcommand('remote', 'Kirim panduan RemoteEvent game pass.'),
                    $this->subcommand('readme', 'Kirim panduan setup Roblox script.'),
                ],
            ],
            [
                'name' => 'race',
                'description' => 'Kelola event balap komunitas.',
                'type' => 1,
                'options' => [
                    $this->subcommand('create', 'Buat event balap baru.', [
                        $this->stringOption('title', 'Nama event balap.', true),
                        $this->integerOption('max_players', 'Jumlah slot pemain.', true),
                        $this->integerOption('entry_fee', 'Biaya masuk Robux.'),
                        $this->stringOption('notes', 'Aturan singkat event.'),
                        $this->integerOption('mulai_dalam_menit', 'Countdown mulai balapan dalam menit.'),
                        $this->roleOption('role_event', 'Role yang akan di-mention saat event dibuat.'),
                    ]),
                    $this->subcommand('list', 'Lihat event balap terbaru.'),
                    $this->subcommand('join', 'Daftar ke event balap.', [
                        $this->integerOption('event_id', 'ID event balap.', true),
                        $this->stringOption('roblox_username', 'Username Roblox kamu.', true),
                        $this->stringOption('notes', 'Catatan tambahan.'),
                    ]),
                    $this->subcommand('finish', 'Input hasil akhir dan umumkan pemenang balapan.', [
                        $this->integerOption('event_id', 'ID event balap.', true),
                        $this->stringOption('winners', 'Urutan pemenang. Pisahkan dengan koma, contoh: Lyva, Fenzane, Nadim', true),
                        $this->stringOption('notes', 'Catatan hasil akhir atau hadiah.'),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return array<string, mixed>
     */
    private function subcommand(string $name, string $description, array $options = []): array
    {
        $subcommand = [
            'type' => 1,
            'name' => $name,
            'description' => $description,
        ];

        if ($options !== []) {
            $subcommand['options'] = $options;
        }

        return $subcommand;
    }

    /**
     * @param  array<int, array{name: string, value: string}>  $choices
     * @return array<string, mixed>
     */
    private function stringOption(string $name, string $description, bool $required = false, array $choices = []): array
    {
        $option = [
            'type' => 3,
            'name' => $name,
            'description' => $description,
            'required' => $required,
        ];

        if ($choices !== []) {
            $option['choices'] = $choices;
        }

        return $option;
    }

    /**
     * @param  array<int, int>  $channelTypes
     * @return array<string, mixed>
     */
    private function channelOption(string $name, string $description, bool $required = false, array $channelTypes = []): array
    {
        $option = [
            'type' => 7,
            'name' => $name,
            'description' => $description,
            'required' => $required,
        ];

        if ($channelTypes !== []) {
            $option['channel_types'] = $channelTypes;
        }

        return $option;
    }

    /**
     * @return array<string, mixed>
     */
    private function roleOption(string $name, string $description, bool $required = false): array
    {
        return [
            'type' => 8,
            'name' => $name,
            'description' => $description,
            'required' => $required,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function integerOption(string $name, string $description, bool $required = false, ?int $minValue = null, ?int $maxValue = null): array
    {
        $option = [
            'type' => 4,
            'name' => $name,
            'description' => $description,
            'required' => $required,
        ];

        if ($minValue !== null) {
            $option['min_value'] = $minValue;
        }

        if ($maxValue !== null) {
            $option['max_value'] = $maxValue;
        }

        return $option;
    }
}
