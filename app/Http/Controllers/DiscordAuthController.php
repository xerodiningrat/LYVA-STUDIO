<?php

namespace App\Http\Controllers;

use App\Models\DiscordGuildSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscordAuthController extends Controller
{
    private const ADMINISTRATOR_PERMISSION = 0x8;
    private const MANAGE_GUILD_PERMISSION = 0x20;

    public function redirect(Request $request): RedirectResponse
    {
        $clientId = (string) config('services.discord.application_id');
        $redirectUri = $this->redirectUri();

        abort_if($clientId === '' || $redirectUri === '', 500, 'Discord OAuth belum dikonfigurasi.');

        $state = Str::random(40);
        $request->session()->put('discord_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'identify guilds',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away("https://discord.com/oauth2/authorize?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('discord_oauth_state', '');
        $incomingState = (string) $request->query('state', '');

        abort_if($expectedState === '' || ! hash_equals($expectedState, $incomingState), 403, 'State Discord tidak valid.');

        $code = (string) $request->query('code', '');
        abort_if($code === '', 422, 'Code Discord tidak ditemukan.');

        $tokenPayload = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => (string) config('services.discord.application_id'),
            'client_secret' => (string) config('services.discord.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ])->throw()->json();

        $accessToken = (string) ($tokenPayload['access_token'] ?? '');
        abort_if($accessToken === '', 500, 'Access token Discord gagal diambil.');

        $discordUser = Http::withToken($accessToken)
            ->get('https://discord.com/api/users/@me')
            ->throw()
            ->json();

        $guilds = Http::withToken($accessToken)
            ->get('https://discord.com/api/users/@me/guilds')
            ->throw()
            ->json();

        $displayName = $discordUser['global_name'] ?: $discordUser['username'];

        $user = User::query()->updateOrCreate(
            ['discord_user_id' => (string) $discordUser['id']],
            [
                'name' => $displayName,
                'email' => sprintf('%s@discord.lyva.local', $discordUser['id']),
                'password' => bcrypt(Str::random(40)),
                'discord_username' => $discordUser['username'],
                'discord_avatar' => $discordUser['avatar'] ?? null,
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, true);

        $manageableGuilds = $this->filterManageableGuilds($guilds);
        $request->session()->put('discord_managed_guilds', $manageableGuilds);

        if (count($manageableGuilds) === 1) {
            $guild = $manageableGuilds[0];
            $user->forceFill(['selected_guild_id' => $guild['id']])->save();
            $request->session()->put('managed_guild', $guild);

            return redirect()->route('dashboard');
        }

        return redirect()->route('guilds.select');
    }

    private function filterManageableGuilds(array $guilds): array
    {
        $knownGuildIds = DiscordGuildSetting::query()->pluck('guild_id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();

        if ($knownGuildIds === []) {
            $knownGuildIds = collect(
                preg_split('/[\s,]+/', (string) env('DISCORD_GUILD_IDS', env('DISCORD_GUILD_ID', ''))) ?: []
            )
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all();
        }

        $botGuildIds = $this->fetchBotGuildIds();

        if ($botGuildIds !== []) {
            $knownGuildIds = $knownGuildIds === []
                ? $botGuildIds
                : array_values(array_intersect($knownGuildIds, $botGuildIds));
        }

        return collect($guilds)
            ->filter(function (array $guild) use ($knownGuildIds) {
                $permissions = (int) ($guild['permissions'] ?? 0);
                $canManage = ($permissions & self::ADMINISTRATOR_PERMISSION) === self::ADMINISTRATOR_PERMISSION
                    || ($permissions & self::MANAGE_GUILD_PERMISSION) === self::MANAGE_GUILD_PERMISSION;

                if (! $canManage) {
                    return false;
                }

                if ($knownGuildIds === []) {
                    return true;
                }

                return in_array((string) ($guild['id'] ?? ''), $knownGuildIds, true);
            })
            ->map(function (array $guild) {
                $icon = $guild['icon'] ?? null;
                $id = (string) ($guild['id'] ?? '');

                return [
                    'id' => $id,
                    'name' => $guild['name'] ?? 'Unknown Guild',
                    'icon_url' => $icon ? "https://cdn.discordapp.com/icons/{$id}/{$icon}.png?size=128" : null,
                    'owner' => (bool) ($guild['owner'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    private function fetchBotGuildIds(): array
    {
        $token = (string) env('DISCORD_BOT_TOKEN', env('DISCORD_TOKEN', ''));

        if ($token === '') {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bot '.$token,
            ])->get('https://discord.com/api/users/@me/guilds');

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json())
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function redirectUri(): string
    {
        $configured = (string) config('services.discord.redirect_uri');

        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/auth/discord/callback';
    }
}
