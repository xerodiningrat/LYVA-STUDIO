<?php

use App\Models\PlayerReport;
use App\Models\RaceEvent;
use App\Models\DiscordVerification;
use App\Models\DiscordGuildSetting;
use App\Models\RulesAcknowledgement;
use App\Models\SalesEvent;
use App\Models\VipTitleClaim;
use App\Models\VipTitleMapSetting;
use App\Models\VipTitlePayment;
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
        'claim_mode' => 'vip_gamepass',
        'api_key' => 'lyva_active_secret',
        'title_slot' => 10,
        'title_price_idr' => null,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Claim Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => ['123456789012345678'],
        'is_active' => true,
    ]);

    VipTitleMapSetting::query()->create([
        'name' => 'Legacy Map',
        'map_key' => 'legacymap',
        'gamepass_id' => 123456,
        'claim_mode' => 'vip_gamepass',
        'api_key' => 'lyva_inactive_secret',
        'title_slot' => 8,
        'title_price_idr' => null,
        'payment_expiry_minutes' => 60,
        'button_label' => null,
        'place_ids' => [],
        'script_access_role_ids' => [],
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
        ->assertJsonPath('items.0.claim_mode', 'vip_gamepass')
        ->assertJsonPath('items.0.api_key', 'lyva_active_secret')
        ->assertJsonPath('items.0.place_ids.0', '76880221507840')
        ->assertJsonPath('items.0.script_access_role_ids.0', '123456789012345678');
});

test('bot can create duitku vip title checkout', function () {
    config()->set('services.discord.internal_token', 'shared-secret');
    config()->set('services.duitku.merchant_code', 'D1234');
    config()->set('services.duitku.api_key', 'secret-key');
    config()->set('services.duitku.sandbox', true);
    config()->set('services.duitku.default_phone_number', '081234567890');
    config()->set('app.url', 'https://lyvaindonesia.my.id');

    Http::fake([
        'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry' => Http::response([
            'merchantCode' => 'D1234',
            'merchantOrderId' => 'VIPTITLE-1-ABCDEFGH',
            'reference' => 'DUITKU-REF-001',
            'paymentUrl' => 'https://sandbox.duitku.com/pay/test-123',
        ]),
    ]);

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Paid',
        'map_key' => 'mountxyra-paid',
        'gamepass_id' => 0,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_paid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 90,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-checkouts.store'), [
        'map_key' => 'mountxyra-paid',
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'payment_method' => 'VC',
        'meta' => [
            'title_style' => [
                'mode' => 'SOLID',
                'preset' => 'GOLD',
                'color' => ['r' => 255, 'g' => 215, 'b' => 0],
                'label' => 'Gold',
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('flow', 'duitku')
        ->assertJsonPath('claim.status', 'awaiting_payment')
        ->assertJsonPath('payment.amount', 15000)
        ->assertJsonPath('payment.paymentUrl', 'https://sandbox.duitku.com/pay/test-123')
        ->assertJsonPath('payment.titleSlot', 10);

    expect(VipTitleClaim::query()->count())->toBe(1);
    expect(VipTitlePayment::query()->count())->toBe(1);
    expect(VipTitleClaim::query()->first()?->meta['title_style']['color']['g'])->toBe(215);
    expect(VipTitlePayment::query()->first()?->guild_id)->toBe('guild-1');
    expect(VipTitlePayment::query()->first()?->admin_fee_amount)->toBe(5000);
    expect(VipTitlePayment::query()->first()?->seller_net_amount)->toBe(10000);
});

test('second vip title checkout reserves a different slot instead of overwriting the old one', function () {
    config()->set('services.discord.internal_token', 'shared-secret');
    config()->set('services.duitku.merchant_code', 'D1234');
    config()->set('services.duitku.api_key', 'secret-key');
    config()->set('services.duitku.sandbox', true);
    config()->set('services.duitku.default_phone_number', '081234567890');
    config()->set('app.url', 'https://lyvaindonesia.my.id');

    Http::fake([
        'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry' => Http::response([
            'merchantCode' => 'D1234',
            'merchantOrderId' => 'VIPTITLE-2-HIJKLMNO',
            'reference' => 'DUITKU-REF-002',
            'paymentUrl' => 'https://sandbox.duitku.com/pay/test-456',
        ]),
    ]);

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Paid',
        'map_key' => 'mountxyra-paid',
        'gamepass_id' => 0,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_paid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 90,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-paid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Title Pertama',
        'discord_user_id' => '777',
        'status' => 'applied',
        'requested_at' => now()->subDay(),
        'consumed_at' => now()->subDay(),
        'meta' => [
            'title_slot' => 10,
        ],
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-checkouts.store'), [
        'map_key' => 'mountxyra-paid',
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Title Kedua',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'payment_method' => 'VC',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('payment.titleSlot', 1)
        ->assertJsonPath('claim.meta.title_slot', 1);
});

test('bot can fetch duitku payment methods for vip title', function () {
    config()->set('services.discord.internal_token', 'shared-secret');
    config()->set('services.duitku.merchant_code', 'D1234');
    config()->set('services.duitku.api_key', 'secret-key');
    config()->set('services.duitku.sandbox', true);

    Http::fake([
        'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod' => Http::response([
            'paymentFee' => [
                [
                    'paymentMethod' => 'VC',
                    'paymentName' => 'Virtual Account',
                    'paymentFee' => '4000',
                    'totalFee' => '19000',
                ],
                [
                    'paymentMethod' => 'QRIS',
                    'paymentName' => 'QRIS',
                    'paymentFee' => '1500',
                    'totalFee' => '16500',
                ],
            ],
        ]),
    ]);

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Paid',
        'map_key' => 'mountxyra-paid',
        'gamepass_id' => 0,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_paid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 90,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.vip-title-payment-methods.index', [
        'map_key' => 'mountxyra-paid',
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('amount', 15000)
        ->assertJsonPath('items.0.paymentMethod', 'VC')
        ->assertJsonPath('items.1.paymentMethod', 'QRIS');
});

test('bot can still create vip title claim when map also has idr price', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Hybrid',
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_hybrid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-claims.store'), [
        'map_key' => 'mountxyra-hybrid',
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'meta' => [
            'title_style' => [
                'mode' => 'RGB',
                'preset' => 'VIP',
                'color' => ['r' => 255, 'g' => 255, 'b' => 255],
                'label' => 'RGB Rainbow',
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('claim.status', 'pending')
        ->assertJsonPath('claim.meta.title_style.mode', 'RGB');
});

test('bot can list active vip titles for a discord user', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra',
        'map_key' => 'mountxyra',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'vip_gamepass',
        'api_key' => 'lyva_mount_secret',
        'title_slot' => 10,
        'title_price_idr' => 100000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleMapSetting::query()->create([
        'name' => 'Sky Summit',
        'map_key' => 'skysummit',
        'gamepass_id' => 1700114698,
        'claim_mode' => 'vip_gamepass',
        'api_key' => 'lyva_sky_secret',
        'title_slot' => 9,
        'title_price_idr' => 50000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['555555'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Title Lama',
        'discord_user_id' => '777',
        'status' => 'applied',
        'requested_at' => now()->subDays(2),
        'consumed_at' => now()->subDays(2),
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Title Baru',
        'discord_user_id' => '777',
        'status' => 'applied',
        'requested_at' => now()->subHours(20),
        'consumed_at' => now()->subHours(19),
        'meta' => [
            'title_style' => [
                'mode' => 'SOLID',
                'preset' => 'BLUE',
                'color' => ['r' => 59, 'g' => 130, 'b' => 246],
                'label' => 'Blue',
            ],
        ],
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'skysummit',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Summit King',
        'discord_user_id' => '777',
        'status' => 'applied',
        'requested_at' => now()->subHours(16),
        'consumed_at' => now()->subHours(15),
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.vip-title-active.index', [
        'discord_user_id' => '777',
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.mapName', 'Mount Xyra')
        ->assertJsonPath('items.0.currentTitle', 'Title Baru')
        ->assertJsonPath('items.0.titleSlot', 10);
});

test('bot can request vip title change after cooldown', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Hybrid',
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_hybrid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'status' => 'applied',
        'requested_at' => now()->subHours(14),
        'consumed_at' => now()->subHours(13),
        'meta' => ['claim_mode' => 'duitku'],
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-changes.store'), [
        'map_key' => 'mountxyra-hybrid',
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky Queen',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'meta' => [
            'title_style' => [
                'mode' => 'SOLID',
                'preset' => 'BLUE',
                'color' => ['r' => 59, 'g' => 130, 'b' => 246],
                'label' => 'Blue',
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('claim.status', 'pending')
        ->assertJsonPath('claim.meta.change_type', 'self_service_update')
        ->assertJsonPath('claim.meta.title_style.label', 'Blue')
        ->assertJsonPath('cooldownHours', 12)
        ->assertJsonPath('previousTitle', 'Sky King')
        ->assertJsonPath('titleSlot', 10);
});

test('bot can request vip title change by selected active claim id', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Hybrid',
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_hybrid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    $activeClaim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'status' => 'applied',
        'requested_at' => now()->subHours(20),
        'consumed_at' => now()->subHours(19),
        'meta' => ['claim_mode' => 'duitku'],
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-changes.store'), [
        'active_claim_id' => $activeClaim->id,
        'requested_title' => 'Sky Emperor',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'meta' => [
            'title_style' => [
                'mode' => 'SOLID',
                'preset' => 'GOLD',
                'color' => ['r' => 255, 'g' => 215, 'b' => 0],
                'label' => 'Gold',
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('claim.requested_title', 'Sky Emperor')
        ->assertJsonPath('previousTitle', 'Sky King')
        ->assertJsonPath('titleSlot', 10);
});

test('bot vip title change enforces 12 hour cooldown', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Hybrid',
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_hybrid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'status' => 'applied',
        'requested_at' => now()->subHours(3),
        'consumed_at' => now()->subHours(2),
        'meta' => ['claim_mode' => 'duitku'],
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-changes.store'), [
        'map_key' => 'mountxyra-hybrid',
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky Queen',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
    ]);

    $response
        ->assertStatus(422);

    expect((string) $response->json('message'))->toContain('baru bisa diubah lagi');
});

test('bot vip title change ignores stale pending request older than applied title', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Hybrid',
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_hybrid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Title Lama Pending',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'status' => 'awaiting_payment',
        'requested_at' => now()->subDays(2),
    ]);

    $activeClaim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
        'status' => 'applied',
        'requested_at' => now()->subHours(20),
        'consumed_at' => now()->subHours(19),
        'meta' => ['claim_mode' => 'duitku'],
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-changes.store'), [
        'active_claim_id' => $activeClaim->id,
        'requested_title' => 'Sky Emperor',
        'discord_user_id' => '777',
        'discord_tag' => 'Buyer#1234',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('claim.requested_title', 'Sky Emperor');
});

test('bot vip title change requires same discord owner', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra Hybrid',
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'duitku',
        'api_key' => 'lyva_hybrid_secret',
        'title_slot' => 10,
        'title_price_idr' => 15000,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Beli Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-hybrid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => 'owner-777',
        'discord_tag' => 'Buyer#1234',
        'status' => 'applied',
        'requested_at' => now()->subHours(14),
        'consumed_at' => now()->subHours(13),
        'meta' => ['claim_mode' => 'duitku'],
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->postJson(route('api.bot.vip-title-changes.store'), [
        'map_key' => 'mountxyra-hybrid',
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky Queen',
        'discord_user_id' => 'intruder-999',
        'discord_tag' => 'Intruder#9999',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Title ini terhubung ke akun Discord lain, jadi hanya owner aslinya yang bisa mengubah title.');
});

test('duitku callback marks vip title payment as paid', function () {
    config()->set('services.duitku.merchant_code', 'D1234');
    config()->set('services.duitku.api_key', 'secret-key');
    config()->set('services.duitku.sandbox', true);

    $claim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra-paid',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'status' => 'awaiting_payment',
        'requested_at' => now(),
        'meta' => ['claim_mode' => 'duitku'],
    ]);

    VipTitlePayment::query()->create([
        'vip_title_claim_id' => $claim->id,
        'map_key' => 'mountxyra-paid',
        'merchant_order_id' => 'VIPTITLE-1-ABCDEFGH',
        'duitku_reference' => 'DUITKU-REF-001',
        'amount' => 15000,
        'status' => 'pending',
        'payment_url' => 'https://sandbox.duitku.com/pay/test-123',
        'expires_at' => now()->addHour(),
        'buyer_email' => 'buyer@example.com',
    ]);

    Http::fake([
        'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus' => Http::response([
            'merchantOrderId' => 'VIPTITLE-1-ABCDEFGH',
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'reference' => 'DUITKU-REF-001',
        ]),
    ]);

    $signature = md5('D1234'.'15000'.'VIPTITLE-1-ABCDEFGH'.'secret-key');

    $response = $this->post(route('payments.duitku.callback'), [
        'merchantCode' => 'D1234',
        'amount' => '15000',
        'merchantOrderId' => 'VIPTITLE-1-ABCDEFGH',
        'signature' => $signature,
        'reference' => 'DUITKU-REF-001',
        'resultCode' => '00',
    ]);

    $response->assertOk();

    expect($claim->fresh()->status)->toBe('pending');
    expect(VipTitlePayment::query()->first()?->status)->toBe('paid');
    expect(VipTitlePayment::query()->first()?->frozen_until)->not->toBeNull();
});

test('bot can fetch vip title payment status', function () {
    config()->set('services.discord.internal_token', 'shared-secret');

    $claim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 0,
        'roblox_user_id' => 9006398922,
        'roblox_username' => 'fenzane25',
        'requested_title' => 'FENZANE KEREN',
        'status' => 'applied',
        'requested_at' => now(),
        'consumed_at' => now(),
        'meta' => ['payment_status' => 'paid'],
    ]);

    VipTitlePayment::query()->create([
        'vip_title_claim_id' => $claim->id,
        'map_key' => 'mountxyra',
        'merchant_order_id' => 'VIPTITLE-4-HLLERIVM',
        'duitku_reference' => 'DS1690426UVGQIWU7LEEVXO6',
        'amount' => 100000,
        'status' => 'paid',
        'payment_method' => 'VC',
        'payment_url' => 'https://sandbox.duitku.com/topup/test',
        'expires_at' => now()->addHour(),
        'paid_at' => now(),
    ]);

    $response = $this->withHeaders([
        'X-Bot-Token' => 'shared-secret',
    ])->getJson(route('api.bot.vip-title-payments.show', ['merchantOrderId' => 'VIPTITLE-4-HLLERIVM']));

    $response
        ->assertOk()
        ->assertJsonPath('payment.status', 'paid')
        ->assertJsonPath('claim.status', 'applied')
        ->assertJsonPath('claim.robloxUsername', 'fenzane25');
});

test('roblox pull includes title style metadata', function () {
    VipTitleMapSetting::query()->create([
        'name' => 'Mount Xyra',
        'map_key' => 'mountxyra',
        'gamepass_id' => 1700114697,
        'claim_mode' => 'vip_gamepass',
        'api_key' => 'lyva_map_secret',
        'title_slot' => 10,
        'title_price_idr' => null,
        'payment_expiry_minutes' => 60,
        'button_label' => 'Claim Title',
        'place_ids' => ['76880221507840'],
        'script_access_role_ids' => [],
        'is_active' => true,
    ]);

    VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 1700114697,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'status' => 'pending',
        'requested_at' => now(),
        'meta' => [
            'title_slot' => 8,
            'title_style' => [
                'mode' => 'SOLID',
                'preset' => 'VIP',
                'color' => ['r' => 255, 'g' => 215, 'b' => 0],
                'label' => 'Gold',
            ],
        ],
    ]);

    $response = $this->withHeaders([
        'X-Api-Key' => 'lyva_map_secret',
    ])->postJson(route('api.roblox.vip-title-claims.pull'), [
        'userId' => 99123,
        'username' => 'RobloxBuyer',
        'mapKey' => 'mountxyra',
        'placeId' => '76880221507840',
        'universeId' => '123456789',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('claim.title', 'Sky King')
        ->assertJsonPath('claim.titleSlot', 8)
        ->assertJsonPath('claim.titleMeta.mode', 'SOLID')
        ->assertJsonPath('claim.titleMeta.color.g', 215)
        ->assertJsonPath('claim.titleMeta.label', 'Gold');
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
