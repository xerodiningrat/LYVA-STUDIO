<?php

use App\Models\User;

test('guild picker shows manageable guilds even when bot has not joined yet', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession([
            'discord_managed_guilds' => [
                [
                    'id' => '123',
                    'name' => 'LYVA Community',
                    'icon_url' => null,
                    'owner' => true,
                    'bot_joined' => false,
                ],
            ],
        ])
        ->get(route('guilds.select'));

    $response
        ->assertOk()
        ->assertSee('LYVA Community')
        ->assertSee('Bot Missing')
        ->assertSee('Bot belum masuk');
});

test('guild picker blocks selecting a guild when the bot has not joined yet', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession([
            'discord_managed_guilds' => [
                [
                    'id' => '123',
                    'name' => 'LYVA Community',
                    'icon_url' => null,
                    'owner' => true,
                    'bot_joined' => false,
                ],
            ],
        ])
        ->post(route('guilds.select.store', '123'));

    $response->assertForbidden();
});

test('guild picker restores the previously selected guild when session guilds are missing', function () {
    $user = User::factory()->create([
        'discord_user_id' => 'discord-123',
        'selected_guild_id' => '999',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('guilds.select'));

    $response->assertRedirect(route('dashboard'));
    $this->assertSame('999', session('managed_guild.id'));
});
