<?php

use App\Models\VipTitleClaim;
use App\Models\VipTitleMapSetting;
use App\Models\VipTitlePayment;
use App\Models\VipTitleWithdrawal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertSee('Roblox Discord Ops')
        ->assertSee('Tracked experiences')
        ->assertSee('Player and bug reports');
});

test('dashboard shows vip title wallet summary for selected guild', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $claim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '9001',
        'status' => 'applied',
        'requested_at' => now()->subDays(3),
        'consumed_at' => now()->subDays(3),
    ]);

    VipTitlePayment::query()->create([
        'vip_title_claim_id' => $claim->id,
        'map_key' => 'mountxyra',
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'merchant_order_id' => 'VIPTITLE-100-ABCDE',
        'amount' => 100000,
        'admin_fee_amount' => 5000,
        'seller_net_amount' => 95000,
        'status' => 'paid',
        'paid_at' => now()->subDays(3),
        'frozen_until' => now()->subDay(),
        'buyer_discord_user_id' => '9001',
    ]);

    VipTitleWithdrawal::query()->create([
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'user_id' => $user->id,
        'requester_discord_user_id' => '9001',
        'requester_name' => 'Tester',
        'gross_amount' => 30000,
        'withdrawal_fee_amount' => 2500,
        'net_amount' => 27500,
        'status' => 'processing',
        'requested_at' => now()->subHours(2),
        'ready_at' => now()->addHours(22),
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('VIP Title Wallet')
        ->assertSee('Lyva Community')
        ->assertSee('Rp 100.000')
        ->assertSee('Rp 65.000')
        ->assertSee('Request Penarikan');
});

test('dashboard wallet withdrawal request is stored for selected guild', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $claim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '9001',
        'status' => 'applied',
        'requested_at' => now()->subDays(3),
        'consumed_at' => now()->subDays(3),
    ]);

    VipTitlePayment::query()->create([
        'vip_title_claim_id' => $claim->id,
        'map_key' => 'mountxyra',
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'merchant_order_id' => 'VIPTITLE-101-FGHIJ',
        'amount' => 100000,
        'admin_fee_amount' => 5000,
        'seller_net_amount' => 95000,
        'status' => 'paid',
        'paid_at' => now()->subDays(3),
        'frozen_until' => now()->subDay(),
        'buyer_discord_user_id' => '9001',
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->post(route('dashboard.wallet.withdrawals.store'), [
            'amount' => 50000,
            'bank_code' => 'bca',
            'account_number' => '1234567890',
            'account_holder_name' => 'Tester Wallet',
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('wallet_status');

    $withdrawal = VipTitleWithdrawal::query()->first();

    expect($withdrawal)->not->toBeNull();
    expect($withdrawal?->guild_id)->toBe('guild-1');
    expect($withdrawal?->gross_amount)->toBe(50000);
    expect($withdrawal?->withdrawal_fee_amount)->toBe(2500);
    expect($withdrawal?->net_amount)->toBe(47500);
    expect($withdrawal?->status)->toBe('processing');
    expect($withdrawal?->bank_name)->toBe('BCA');
    expect($withdrawal?->account_number)->toBe('1234567890');
    expect($withdrawal?->account_holder_name)->toBe('Tester Wallet');
});

test('dashboard wallet withdrawal validates account number based on bank rules', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $claim = VipTitleClaim::query()->create([
        'map_key' => 'mountxyra',
        'gamepass_id' => 0,
        'roblox_user_id' => 99123,
        'roblox_username' => 'RobloxBuyer',
        'requested_title' => 'Sky King',
        'discord_user_id' => '9001',
        'status' => 'applied',
        'requested_at' => now()->subDays(3),
        'consumed_at' => now()->subDays(3),
    ]);

    VipTitlePayment::query()->create([
        'vip_title_claim_id' => $claim->id,
        'map_key' => 'mountxyra',
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'merchant_order_id' => 'VIPTITLE-102-KLMNO',
        'amount' => 100000,
        'admin_fee_amount' => 5000,
        'seller_net_amount' => 95000,
        'status' => 'paid',
        'paid_at' => now()->subDays(3),
        'frozen_until' => now()->subDay(),
        'buyer_discord_user_id' => '9001',
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->from(route('dashboard.wallet.withdrawals.index'))
        ->post(route('dashboard.wallet.withdrawals.store'), [
            'amount' => 50000,
            'bank_code' => 'bca',
            'account_number' => '12345',
            'account_holder_name' => 'Tester Wallet',
        ]);

    $response
        ->assertRedirect(route('dashboard.wallet.withdrawals.index'))
        ->assertSessionHasErrors('account_number');
});

test('authenticated users can visit the wallet earnings page', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->get(route('dashboard.wallet.earnings'));

    $response
        ->assertOk()
        ->assertSee('VIP Title Earnings')
        ->assertSee('Transaksi penghasilan terbaru');
});

test('authenticated users can visit the wallet withdrawals page', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->get(route('dashboard.wallet.withdrawals.index'));

    $response
        ->assertOk()
        ->assertSee('VIP Title Withdrawals')
        ->assertSee('Ajukan penarikan baru');
});

test('ready withdrawal can be marked as completed from withdrawals page', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $withdrawal = VipTitleWithdrawal::query()->create([
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'user_id' => $user->id,
        'requester_discord_user_id' => '9001',
        'requester_name' => 'Tester',
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_holder_name' => 'Tester Wallet',
        'gross_amount' => 30000,
        'withdrawal_fee_amount' => 2500,
        'net_amount' => 27500,
        'status' => 'ready',
        'requested_at' => now()->subDays(2),
        'ready_at' => now()->subHour(),
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->post(route('dashboard.wallet.withdrawals.complete', $withdrawal));

    $response
        ->assertRedirect()
        ->assertSessionHas('wallet_status');

    expect($withdrawal->fresh()?->status)->toBe('completed');
    expect($withdrawal->fresh()?->completed_at)->not->toBeNull();
});

test('authenticated users can visit the discord setup page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('discord.setup'));

    $response
        ->assertOk()
        ->assertSee('Discord Command Setup')
        ->assertSee('Environment checks')
        ->assertSee('Slash commands yang sudah disiapkan');
});

test('authenticated users can visit the roblox scripts page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('roblox.scripts.index'));

    $response
        ->assertOk()
        ->assertSee('Roblox Script Library')
        ->assertSee('Dev Product Reporter');
});

test('authenticated users can visit the vip title setup page', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);
    $this->actingAs($user);

    VipTitleMapSetting::query()->create([
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'owner_user_id' => $user->id,
        'owner_discord_user_id' => '9001',
        'name' => 'Mount Xyra',
        'map_key' => 'mountxyra',
        'gamepass_id' => 1700114697,
        'api_key' => 'lyva_test_map_key_1',
        'title_slot' => 10,
        'is_active' => true,
    ]);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->get(route('vip-title.setup'));

    $response
        ->assertOk()
        ->assertSee('VIP Title Control')
        ->assertSee('Map VIP Title baru')
        ->assertSee('Recent VIP Title claims')
        ->assertSee('Lyva Community');
});

test('vip title setup only shows maps owned by the active discord workspace', function () {
    $owner = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $other = User::factory()->create([
        'discord_user_id' => '9002',
        'selected_guild_id' => 'guild-2',
    ]);

    VipTitleMapSetting::query()->create([
        'guild_id' => 'guild-1',
        'guild_name' => 'Lyva Community',
        'owner_user_id' => $owner->id,
        'owner_discord_user_id' => '9001',
        'name' => 'Mount Xyra',
        'map_key' => 'mountxyra',
        'gamepass_id' => 1700114697,
        'api_key' => 'lyva_owner_map_key_1',
        'title_slot' => 10,
        'is_active' => true,
    ]);

    VipTitleMapSetting::query()->create([
        'guild_id' => 'guild-2',
        'guild_name' => 'Other Guild',
        'owner_user_id' => $other->id,
        'owner_discord_user_id' => '9002',
        'name' => 'Other Peak',
        'map_key' => 'otherpeak',
        'gamepass_id' => 1800000000,
        'api_key' => 'lyva_owner_map_key_2',
        'title_slot' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($owner);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->get(route('vip-title.setup'));

    $response
        ->assertOk()
        ->assertSee('Mount Xyra')
        ->assertDontSee('Other Peak')
        ->assertDontSee('otherpeak');
});

test('vip title setup stores new map under active discord workspace owner', function () {
    $user = User::factory()->create([
        'discord_user_id' => '9001',
        'selected_guild_id' => 'guild-1',
    ]);

    $this->actingAs($user);

    $response = $this
        ->withSession([
            'managed_guild' => [
                'id' => 'guild-1',
                'name' => 'Lyva Community',
            ],
        ])
        ->post(route('vip-title.setup.store'), [
            'name' => 'Mount Xyra',
            'map_key' => 'mountxyra',
            'gamepass_id' => 1700114697,
            'title_slot' => 10,
            'title_price_idr' => null,
            'payment_expiry_minutes' => 60,
            'button_label' => 'Claim Title',
            'place_ids' => '1234567890',
            'script_access_role_ids' => '111222333',
            'notes' => 'Primary map',
            'is_active' => '1',
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('status');

    $setting = VipTitleMapSetting::query()->first();

    expect($setting)->not->toBeNull();
    expect($setting?->guild_id)->toBe('guild-1');
    expect($setting?->guild_name)->toBe('Lyva Community');
    expect($setting?->owner_user_id)->toBe($user->id);
    expect($setting?->owner_discord_user_id)->toBe('9001');
});

test('authenticated users can download a roblox script template', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    config()->set('app.url', 'https://example.com');
    config()->set('services.roblox.ingest_token', 'roblox-secret');

    $response = $this->get(route('roblox.scripts.download', 'devproduct'));

    $response
        ->assertOk()
        ->assertHeader('content-disposition');

    $content = $response->streamedContent();

    expect($content)->toContain('https://example.com/api/roblox/sales-events');
    expect($content)->toContain('roblox-secret');
});
