<?php

use App\Models\PlayerReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('discord ping interactions receive a pong response', function () {
    [$publicKey, $secretKey] = discordKeypair();

    config()->set('services.discord.public_key', $publicKey);

    $body = json_encode([
        'type' => 1,
    ], JSON_THROW_ON_ERROR);

    $timestamp = (string) now()->timestamp;
    $signature = sodium_bin2hex(sodium_crypto_sign_detached($timestamp.$body, $secretKey));

    $response = $this->call(
        'POST',
        route('discord.interactions'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE_ED25519' => $signature,
            'HTTP_X_SIGNATURE_TIMESTAMP' => $timestamp,
        ],
        $body,
    );

    $response
        ->assertOk()
        ->assertJson(['type' => 1]);
});

test('discord slash ping returns a bot message', function () {
    [$publicKey, $secretKey] = discordKeypair();

    config()->set('services.discord.public_key', $publicKey);

    $body = json_encode([
        'type' => 2,
        'data' => [
            'name' => 'ping',
        ],
        'member' => [
            'user' => [
                'username' => 'tester',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $timestamp = (string) now()->timestamp;
    $signature = sodium_bin2hex(sodium_crypto_sign_detached($timestamp.$body, $secretKey));

    $response = $this->call(
        'POST',
        route('discord.interactions'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE_ED25519' => $signature,
            'HTTP_X_SIGNATURE_TIMESTAMP' => $timestamp,
        ],
        $body,
    );

    $response
        ->assertOk()
        ->assertJsonPath('type', 4)
        ->assertJsonPath('data.content', 'Pong. Bot Discord Roblox ops aktif.');
});

test('discord report player command stores a report', function () {
    [$publicKey, $secretKey] = discordKeypair();

    config()->set('services.discord.public_key', $publicKey);

    $body = json_encode([
        'type' => 2,
        'data' => [
            'name' => 'report',
            'options' => [
                [
                    'type' => 1,
                    'name' => 'player',
                    'options' => [
                        [
                            'type' => 3,
                            'name' => 'player',
                            'value' => 'ShadowVex',
                        ],
                        [
                            'type' => 3,
                            'name' => 'reason',
                            'value' => 'Exploit test dari Discord',
                        ],
                    ],
                ],
            ],
        ],
        'member' => [
            'user' => [
                'username' => 'tester',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $timestamp = (string) now()->timestamp;
    $signature = sodium_bin2hex(sodium_crypto_sign_detached($timestamp.$body, $secretKey));

    $response = $this->call(
        'POST',
        route('discord.interactions'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE_ED25519' => $signature,
            'HTTP_X_SIGNATURE_TIMESTAMP' => $timestamp,
        ],
        $body,
    );

    $response
        ->assertOk()
        ->assertJsonPath('type', 4);

    expect(PlayerReport::query()->count())->toBe(1);
    expect(PlayerReport::query()->first()?->reported_player_name)->toBe('ShadowVex');
});

/**
 * @return array{string, string}
 */
function discordKeypair(): array
{
    $pair = sodium_crypto_sign_keypair();

    return [
        sodium_bin2hex(sodium_crypto_sign_publickey($pair)),
        sodium_crypto_sign_secretkey($pair),
    ];
}
