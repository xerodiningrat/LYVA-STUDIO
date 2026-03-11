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

        if ($guilds->count() === 1) {
            $guild = $guilds->first();
            $request->session()->put('managed_guild', $guild);
            $request->user()?->forceFill(['selected_guild_id' => $guild['id']])->save();

            return redirect()->route('dashboard');
        }

        return view('discord.guild-select', [
            'guilds' => $guilds,
        ]);
    }

    public function select(Request $request, string $guildId): RedirectResponse
    {
        $guilds = collect($request->session()->get('discord_managed_guilds', []));
        $guild = $guilds->firstWhere('id', $guildId);

        abort_if(! $guild, 404);

        $request->session()->put('managed_guild', $guild);
        $request->user()?->forceFill(['selected_guild_id' => $guild['id']])->save();

        return redirect()->route('dashboard');
    }
}
