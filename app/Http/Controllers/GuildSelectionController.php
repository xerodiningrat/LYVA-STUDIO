<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuildSelectionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $guilds = collect($request->session()->get('discord_managed_guilds', []))->values();
        $selectableGuilds = $guilds->where('bot_joined', true)->values();

        if ($selectableGuilds->count() === 1) {
            $guild = $selectableGuilds->first();
            $request->session()->put('managed_guild', $guild);
            $request->user()?->forceFill(['selected_guild_id' => $guild['id']])->save();

            return redirect()->route('dashboard');
        }

        return view('discord.guild-select', [
            'guilds' => $guilds,
            'joinedGuilds' => $selectableGuilds,
        ]);
    }

    public function select(Request $request, string $guildId): RedirectResponse
    {
        $guilds = collect($request->session()->get('discord_managed_guilds', []));
        $guild = $guilds->firstWhere('id', $guildId);

        abort_if(! $guild, 404);
        abort_unless((bool) ($guild['bot_joined'] ?? false), 403, 'Bot LYVA belum masuk ke server ini.');

        $request->session()->put('managed_guild', $guild);
        $request->user()?->forceFill(['selected_guild_id' => $guild['id']])->save();

        return redirect()->route('dashboard');
    }
}
