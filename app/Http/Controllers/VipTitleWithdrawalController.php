<?php

namespace App\Http\Controllers;

use App\Models\VipTitleWithdrawal;
use App\Services\VipTitleWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VipTitleWithdrawalController extends Controller
{
    public function store(Request $request, VipTitleWalletService $walletService): RedirectResponse
    {
        $managedGuild = $request->session()->get('managed_guild');
        $guildId = trim((string) ($managedGuild['id'] ?? ''));

        if ($guildId === '') {
            return back()->withErrors([
                'amount' => 'Pilih server dulu sebelum membuat request penarikan.',
            ]);
        }

        $summary = $walletService->summarizeForGuild($guildId, (string) ($managedGuild['name'] ?? ''));

        $validated = $request->validate([
            'amount' => [
                'required',
                'integer',
                'min:'.(VipTitleWalletService::WITHDRAWAL_FEE_IDR + 1),
                'max:'.$summary['availableBalance'],
            ],
        ], [
            'amount.max' => 'Jumlah penarikan melebihi saldo yang sudah siap ditarik.',
            'amount.min' => 'Jumlah penarikan harus lebih besar dari biaya tarik Rp2.500.',
        ]);

        $grossAmount = (int) $validated['amount'];
        $withdrawalFee = VipTitleWalletService::WITHDRAWAL_FEE_IDR;
        $netAmount = max(0, $grossAmount - $withdrawalFee);

        VipTitleWithdrawal::query()->create([
            'guild_id' => $guildId,
            'guild_name' => $managedGuild['name'] ?? null,
            'user_id' => $request->user()->id,
            'requester_discord_user_id' => $request->user()?->discord_user_id,
            'requester_name' => $request->user()?->name,
            'gross_amount' => $grossAmount,
            'withdrawal_fee_amount' => $withdrawalFee,
            'net_amount' => $netAmount,
            'status' => 'processing',
            'requested_at' => now(),
            'ready_at' => now()->addDays(VipTitleWalletService::WITHDRAWAL_PROCESSING_DAYS),
            'meta' => [
                'source' => 'dashboard',
            ],
        ]);

        return back()->with('wallet_status', sprintf(
            'Request penarikan Rp%s untuk server %s sudah dibuat. Dana akan masuk status siap tarik setelah 1 hari proses.',
            number_format($grossAmount, 0, ',', '.'),
            $managedGuild['name'] ?? $guildId,
        ));
    }
}
