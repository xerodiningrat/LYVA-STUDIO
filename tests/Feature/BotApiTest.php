<?php

use App\Models\PlayerReport;
use App\Models\RaceEvent;
use App\Models\DiscordVerification;
use App\Models\DiscordGuildSetting;
use App\Models\RulesAcknowledgement;
use App\Models\SalesEvent;
use App\Models\VipTitleMapSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('bot status api requires internal token', function () {
    $this->getJson(route('api.bot.status'))->assertUnauthorized();
});

test('bot status api returns dashboard counters', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.status'));

    $response
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure([
            'has_bot_tables',
            'tracked_games',
            'active_webhooks',
            'open_alerts',
            'pending_reports',
            'sales_events',
        ]);
});

test('bot report api stores reports', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.reports'), [
        'reporter_name' => 'node-bot-user',
        'reported_player_name' => 'ShadowVex',
        'category' => 'player',
        'summary' => 'Gateway bot test report',
        'priority' => 'high',
        'payload' => [
            'source' => 'test',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('status', 'new');

    expect(PlayerReport::query()->count())->toBe(1);
});

test('bot sales api returns live sales items', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    SalesEvent::query()->create([
        'product_name' => 'Premium Crate',
        'product_type' => 'dev_product',
        'buyer_name' => 'Aqila',
        'amount_robux' => 120,
        'quantity' => 1,
        'purchased_at' => now(),
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.sales'));

    $response
        ->assertOk()
        ->assertJsonPath('items.0.product_name', 'Premium Crate');
});

test('bot sales api returns summary data', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    SalesEvent::query()->create([
        'product_name' => 'Premium Crate',
        'product_type' => 'dev_product',
        'buyer_name' => 'Aqila',
        'amount_robux' => 120,
        'quantity' => 2,
        'purchased_at' => now(),
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.sales', ['mode' => 'summary']));

    $response
        ->assertOk()
        ->assertJsonPath('transactions', 1)
        ->assertJsonPath('robux_total', 240)
        ->assertJsonPath('top_product.product_name', 'Premium Crate');
});

test('bot vip title maps api returns active dashboard maps', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra',
        'map_key' => 'mountxyra',
        'gamepass_id' => 1700114697,
        'api_key' => 'lyva_active_secret',
        'title_slot' => 10,
        'place_ids' => ['76880221507840'],
        'is_active' => true,
    ]);

    VipTitleMapSetting::query()->create([
        'name' => 'Legacy Map',
        'map_key' => 'legacymap',
        'gamepass_id' => 123456,
        'api_key' => 'lyva_inactive_secret',
        'title_slot' => 8,
        'place_ids' => [],
        'is_active' => false,
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.vip-title-maps.index'));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.name', 'Mount Xyra')
        ->assertJsonPath('items.0.map_key', 'mountxyra')
        ->assertJsonPath('items.0.gamepass_id', 1700114697)
        ->assertJsonMissing(['api_key' => 'lyva_active_secret']);
});

test('roblox sales event api stores incoming sales event', function () {
    config()->set('services.roblox.ingest_token', 'roblox-shared-secret');

    $response = $this->withHeaders([
        'X-Roblox-Token' => 'roblox-shared-secret',
    ])->postJson(route('api.roblox.sales-events'), [
        'product_name' => 'Speed Coil',
        'product_type' => 'game_pass',
        'buyer_name' => 'Nadim',
        'amount_robux' => 75,
        'quantity' => 1,
        'payload' => [
            'source' => 'roblox-test',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('stored', true);

    expect(SalesEvent::query()->count())->toBe(1);
});

test('bot can create, list, and join race events', function () {
    config()->set('services.discord.internal_token', 'shared-secret');
    Http::fake([
        'https://users.roblox.com/v1/usernames/users' => Http::response([
            'data' => [[
                'id' => 99123,
                'name' => 'RobloxRacer',
                'displayName' => 'Roblox Racer',
            ]],
        ]),
    ]);

    $create = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.races.store'), [
        'title' => 'Sprint Heat #1',
        'max_players' => 8,
        'entry_fee_robux' => 25,
        'created_by_discord_id' => '123',
        'created_by_name' => 'AdminRace',
    ]);

    $create
        ->assertCreated()
        ->assertJsonPath('title', 'Sprint Heat #1');

    $eventId = $create->json('id');

    $list = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.races.index'));

    $list
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Sprint Heat #1');

    $join = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.races.join', ['event' => $eventId]), [
        'discord_user_id' => '777',
        'discord_username' => 'PlayerOne',
        'roblox_username' => 'RobloxRacer',
    ]);

    $join
        ->assertCreated()
        ->assertJsonPath('status', 'registered')
        ->assertJsonPath('roblox_username', 'RobloxRacer')
        ->assertJsonPath('roblox_user_id', '99123');

    expect(RaceEvent::query()->first()?->participants()->count())->toBe(1);
});

test('bot race join rejects unknown roblox username', function () {
    config()->set('services.discord.internal_token', 'shared-secret');
    Http::fake([
        'https://users.roblox.com/v1/usernames/users' => Http::response([
            'data' => [],
        ]),
    ]);

    $event = RaceEvent::query()->create([
        'title' => 'Sprint Heat #3',
        'max_players' => 8,
        'entry_fee_robux' => 10,
        'status' => 'registration_open',
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.races.join', ['event' => $event->id]), [
        'discord_user_id' => '778',
        'discord_username' => 'PlayerTwo',
        'roblox_username' => 'TidakAda12345',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Username Roblox tidak ditemukan. Periksa lagi ejaan username-nya.');
});

test('bot can update race event status', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    $event = RaceEvent::query()->create([
        'title' => 'Sprint Heat #2',
        'max_players' => 8,
        'entry_fee_robux' => 10,
        'status' => 'registration_open',
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->patchJson(route('api.bot.races.update', ['event' => $event->id]), [
        'status' => 'finished',
        'meta' => [
            'winners' => ['Lyva', 'Fenzane', 'Nadim'],
            'result_notes' => 'Best of 3 selesai.',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'finished')
        ->assertJsonPath('meta.winners.0', 'Lyva')
        ->assertJsonPath('meta.result_notes', 'Best of 3 selesai.');
});

test('bot rules acknowledgement is stored once per user and message', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    $payload = [
        'guild_id' => '100',
        'channel_id' => '200',
        'message_id' => '300',
        'discord_user_id' => '400',
        'discord_username' => 'Fenzane',
    ];

    $first = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.rules.acknowledgements.store'), $payload);

    $first
        ->assertCreated()
        ->assertJsonPath('created', true)
        ->assertJsonPath('total', 1);

    $second = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.rules.acknowledgements.store'), $payload);

    $second
        ->assertOk()
        ->assertJsonPath('created', false)
        ->assertJsonPath('total', 1);

    expect(RulesAcknowledgement::query()->count())->toBe(1);
});

test('bot can verify, fetch, and unlink discord roblox account', function () {
    config()->set('services.discord.internal_token', 'shared-secret');
    Http::fake([
        'https://users.roblox.com/v1/usernames/users' => Http::response([
            'data' => [[
                'id' => 55001,
                'name' => 'CodeLuxeDev',
                'displayName' => 'Code Luxe Dev',
            ]],
        ]),
    ]);

    $store = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.verifications.store'), [
        'guild_id' => '100',
        'discord_user_id' => '200',
        'discord_username' => 'Fenzane',
        'roblox_username' => 'CodeLuxeDev',
    ]);

    $store
        ->assertCreated()
        ->assertJsonPath('verified', true)
        ->assertJsonPath('roblox_username', 'CodeLuxeDev');

    $show = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.verifications.show', ['discordUserId' => '200']));

    $show
        ->assertOk()
        ->assertJsonPath('verified', true)
        ->assertJsonPath('roblox_display_name', 'Code Luxe Dev');

    $delete = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->deleteJson(route('api.bot.verifications.destroy', ['discordUserId' => '200']));

    $delete
        ->assertOk()
        ->assertJsonPath('unlinked', true);

    expect(DiscordVerification::query()->count())->toBe(0);
});

test('bot can upsert and fetch guild verification settings', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    $upsert = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->putJson(route('api.bot.guild-settings.upsert', ['guildId' => '900']), [
        'verification_channel_id' => '111',
        'verification_message_id' => '222',
        'verification_role_id' => '333',
        'ticket_panel_channel_id' => '444',
        'ticket_panel_message_id' => '555',
        'ticket_support_role_id' => '666',
        'ticket_category_id' => '777',
        'ticket_log_channel_id' => '888',
        'spam_enabled' => true,
        'spam_announcement_channel_id' => '999',
        'spam_log_channel_id' => '1000',
        'spam_threshold' => 3,
        'spam_window_seconds' => 45,
    ]);

    $upsert
        ->assertOk()
        ->assertJsonPath('verification_role_id', '333')
        ->assertJsonPath('ticket_support_role_id', '666')
        ->assertJsonPath('ticket_log_channel_id', '888')
        ->assertJsonPath('spam_enabled', true)
        ->assertJsonPath('spam_announcement_channel_id', '999')
        ->assertJsonPath('spam_log_channel_id', '1000')
        ->assertJsonPath('spam_threshold', 3)
        ->assertJsonPath('spam_window_seconds', 45);

    $show = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.guild-settings.show', ['guildId' => '900']));

    $show
        ->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('verification_channel_id', '111')
        ->assertJsonPath('verification_message_id', '222')
        ->assertJsonPath('verification_role_id', '333')
        ->assertJsonPath('ticket_panel_channel_id', '444')
        ->assertJsonPath('ticket_panel_message_id', '555')
        ->assertJsonPath('ticket_support_role_id', '666')
        ->assertJsonPath('ticket_category_id', '777')
        ->assertJsonPath('ticket_log_channel_id', '888')
        ->assertJsonPath('spam_enabled', true)
        ->assertJsonPath('spam_announcement_channel_id', '999')
        ->assertJsonPath('spam_log_channel_id', '1000')
        ->assertJsonPath('spam_threshold', 3)
        ->assertJsonPath('spam_window_seconds', 45);

    expect(DiscordGuildSetting::query()->count())->toBe(1);
});
