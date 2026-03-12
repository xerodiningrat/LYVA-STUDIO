<?php

namespace App\Http\Controllers;

use App\Services\VipTitleWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VipTitleWalletController extends Controller
{
    public function earnings(Request $request, VipTitleWalletService $walletService): View|RedirectResponse
    {
        $managedGuild = $request->session()->get('managed_guild');
        $guildId = trim((string) ($managedGuild['id'] ?? ''));

        if ($guildId === '') {
            return redirect()->route('guilds.select');
        }

        return view('wallet.earnings', [
            'walletSummary' => $walletService->summarizeForGuild($guildId, (string) ($managedGuild['name'] ?? '')),
            'managedGuild' => $managedGuild,
        ]);
    }

    public function withdrawals(Request $request, VipTitleWalletService $walletService): View|RedirectResponse
    {
        $managedGuild = $request->session()->get('managed_guild');
        $guildId = trim((string) ($managedGuild['id'] ?? ''));

        if ($guildId === '') {
            return redirect()->route('guilds.select');
        }

        return view('wallet.withdrawals', [
            'walletSummary' => $walletService->summarizeForGuild($guildId, (string) ($managedGuild['name'] ?? '')),
            'managedGuild' => $managedGuild,
        ]);
    }
}
