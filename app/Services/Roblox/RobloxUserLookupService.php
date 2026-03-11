<?php

namespace App\Services\Roblox;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class RobloxUserLookupService
{
    public function findByUsername(string $username): ?array
    {
        $response = Http::acceptJson()
            ->timeout(10)
            ->post('https://users.roblox.com/v1/usernames/users', [
                'usernames' => [trim($username)],
                'excludeBannedUsers' => false,
            ]);

        $response->throw();

        $user = data_get($response->json(), 'data.0');

        if (! is_array($user) || empty($user['id']) || empty($user['name'])) {
            return null;
        }

        return [
            'id' => (string) $user['id'],
            'name' => (string) $user['name'],
            'display_name' => (string) ($user['displayName'] ?? $user['name']),
        ];
    }
}
