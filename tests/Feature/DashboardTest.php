<?php

use App\Models\User;

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
