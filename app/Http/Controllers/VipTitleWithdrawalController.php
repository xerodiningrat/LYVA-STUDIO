<?php

namespace App\Http\Controllers;

use App\Models\VipTitleWithdrawal;
use App\Services\VipTitleWalletService;
use App\Support\WithdrawalBankCatalog;
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
            'bank_code' => ['required', 'string', 'in:'.implode(',', WithdrawalBankCatalog::codes())],
            'account_number' => ['required', 'string', 'max:64'],
            'account_holder_name' => ['required', 'string', 'max:120'],
        ], [
            'amount.max' => 'Jumlah penarikan melebihi saldo yang sudah siap ditarik.',
            'amount.min' => 'Jumlah penarikan harus lebih besar dari biaya tarik Rp2.500.',
            'bank_code.required' => 'Nama bank wajib diisi sebelum mengajukan penarikan.',
            'account_number.required' => 'Nomor rekening wajib diisi sebelum mengajukan penarikan.',
            'account_holder_name.required' => 'Atas nama rekening wajib diisi sebelum mengajukan penarikan.',
        ]);

        $selectedBank = WithdrawalBankCatalog::find($validated['bank_code']);
        $normalizedAccountNumber = WithdrawalBankCatalog::normalizeAccountNumber($validated['account_number']);

        if ($selectedBank === null) {
            return back()
                ->withInput()
                ->withErrors(['bank_code' => 'Bank tujuan tidak dikenali.']);
        }

        $accountLength = strlen($normalizedAccountNumber);
        if ($accountLength < $selectedBank['min_digits'] || $accountLength > $selectedBank['max_digits']) {
            $expectedLengthLabel = $selectedBank['min_digits'] === $selectedBank['max_digits']
                ? (string) $selectedBank['min_digits']
                : $selectedBank['min_digits'].'-'.$selectedBank['max_digits'];

            return back()
                ->withInput()
                ->withErrors([
                    'account_number' => sprintf(
                        'Nomor rekening %s harus terdiri dari %s digit angka.',
                        $selectedBank['label'],
                        $expectedLengthLabel,
                    ),
                ]);
        }

        $grossAmount = (int) $validated['amount'];
        $withdrawalFee = VipTitleWalletService::WITHDRAWAL_FEE_IDR;
        $netAmount = max(0, $grossAmount - $withdrawalFee);

        VipTitleWithdrawal::query()->create([
            'guild_id' => $guildId,
            'guild_name' => $managedGuild['name'] ?? null,
            'user_id' => $request->user()->id,
            'requester_discord_user_id' => $request->user()?->discord_user_id,
            'requester_name' => $request->user()?->name,
            'bank_name' => $selectedBank['label'],
            'account_number' => $normalizedAccountNumber,
            'account_holder_name' => $validated['account_holder_name'],
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
            'Request penarikan Rp%s untuk server %s sudah dibuat ke rekening %s (%s). Dana akan masuk status siap tarik setelah 1 hari proses.',
            number_format($grossAmount, 0, ',', '.'),
            $managedGuild['name'] ?? $guildId,
            $normalizedAccountNumber,
            $selectedBank['label'],
        ));
    }

    public function complete(Request $request, VipTitleWithdrawal $withdrawal): RedirectResponse
    {
        $managedGuild = $request->session()->get('managed_guild');
        $guildId = trim((string) ($managedGuild['id'] ?? ''));

        abort_unless($guildId !== '' && $withdrawal->guild_id === $guildId, 403);

        if ($withdrawal->status !== 'ready') {
            return back()->withErrors([
                'withdrawal' => 'Hanya penarikan dengan status ready yang bisa ditandai sudah dibayar.',
            ]);
        }

        $withdrawal->update([
            'status' => 'completed',
            'completed_at' => now(),
            'meta' => array_filter([
                ...($withdrawal->meta ?? []),
                'completed_by_user_id' => $request->user()?->id,
                'completed_by_name' => $request->user()?->name,
                'completed_source' => 'dashboard',
            ]),
        ]);

        return back()->with('wallet_status', sprintf(
            'Penarikan %s ke rekening %s (%s) sudah ditandai sebagai dibayar.',
            'Rp'.number_format((int) $withdrawal->gross_amount, 0, ',', '.'),
            $withdrawal->account_number,
            $withdrawal->bank_name,
        ));
    }
}
