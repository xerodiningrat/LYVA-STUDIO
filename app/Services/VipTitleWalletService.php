<?php

namespace App\Services;

use App\Models\VipTitlePayment;
use App\Models\VipTitleWithdrawal;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class VipTitleWalletService
{
    public const ADMIN_FEE_IDR = 5000;
    public const WITHDRAWAL_FEE_IDR = 2500;
    public const BALANCE_FREEZE_DAYS = 2;
    public const WITHDRAWAL_PROCESSING_DAYS = 1;

    public function summarizeForGuild(?string $guildId, ?string $guildName = null): array
    {
        $normalizedGuildId = trim((string) $guildId);
        if ($normalizedGuildId === '') {
            return $this->emptySummary($guildName);
        }

        $this->syncReadyWithdrawals($normalizedGuildId);

        $payments = VipTitlePayment::query()
            ->with('claim:id,discord_user_id,roblox_username')
            ->where('guild_id', $normalizedGuildId)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->latest('id')
            ->get();

        $withdrawals = VipTitleWithdrawal::query()
            ->where('guild_id', $normalizedGuildId)
            ->latest('requested_at')
            ->latest('id')
            ->get();

        $grossSalesTotal = (int) $payments->sum(fn (VipTitlePayment $payment) => (int) $payment->amount);
        $adminFeeTotal = (int) $payments->sum(fn (VipTitlePayment $payment) => $this->resolveAdminFeeAmount($payment));
        $netSalesTotal = (int) $payments->sum(fn (VipTitlePayment $payment) => $this->resolveSellerNetAmount($payment));
        $frozenBalance = (int) $payments->sum(fn (VipTitlePayment $payment) => $this->isFrozen($payment) ? $this->resolveSellerNetAmount($payment) : 0);
        $maturedBalance = max(0, $netSalesTotal - $frozenBalance);

        $processingWithdrawalBalance = (int) $withdrawals
            ->where('status', 'processing')
            ->sum(fn (VipTitleWithdrawal $withdrawal) => (int) $withdrawal->gross_amount);
        $readyWithdrawalBalance = (int) $withdrawals
            ->where('status', 'ready')
            ->sum(fn (VipTitleWithdrawal $withdrawal) => (int) $withdrawal->gross_amount);
        $completedWithdrawalBalance = (int) $withdrawals
            ->where('status', 'completed')
            ->sum(fn (VipTitleWithdrawal $withdrawal) => (int) $withdrawal->gross_amount);

        $availableBalance = max(0, $maturedBalance - $processingWithdrawalBalance - $readyWithdrawalBalance - $completedWithdrawalBalance);

        return [
            'guildId' => $normalizedGuildId,
            'guildName' => $guildName,
            'adminFeePerSale' => self::ADMIN_FEE_IDR,
            'withdrawalFee' => self::WITHDRAWAL_FEE_IDR,
            'freezeDays' => self::BALANCE_FREEZE_DAYS,
            'withdrawalProcessingDays' => self::WITHDRAWAL_PROCESSING_DAYS,
            'paidTransactionsCount' => $payments->count(),
            'buyersCount' => $this->countDistinctBuyers($payments),
            'grossSalesTotal' => $grossSalesTotal,
            'adminFeeTotal' => $adminFeeTotal,
            'netSalesTotal' => $netSalesTotal,
            'frozenBalance' => $frozenBalance,
            'maturedBalance' => $maturedBalance,
            'availableBalance' => $availableBalance,
            'processingWithdrawalBalance' => $processingWithdrawalBalance,
            'readyWithdrawalBalance' => $readyWithdrawalBalance,
            'completedWithdrawalBalance' => $completedWithdrawalBalance,
            'recentPayments' => $payments
                ->take(6)
                ->map(fn (VipTitlePayment $payment) => [
                    'merchantOrderId' => $payment->merchant_order_id,
                    'mapKey' => $payment->map_key,
                    'guildName' => $payment->guild_name,
                    'buyer' => $this->resolveBuyerLabel($payment),
                    'amount' => (int) $payment->amount,
                    'adminFeeAmount' => $this->resolveAdminFeeAmount($payment),
                    'sellerNetAmount' => $this->resolveSellerNetAmount($payment),
                    'paidAt' => $payment->paid_at,
                    'frozenUntil' => $this->resolveFrozenUntil($payment),
                ])
                ->values(),
            'recentWithdrawals' => $withdrawals
                ->take(6)
                ->map(fn (VipTitleWithdrawal $withdrawal) => [
                    'id' => $withdrawal->id,
                    'grossAmount' => (int) $withdrawal->gross_amount,
                    'withdrawalFeeAmount' => (int) $withdrawal->withdrawal_fee_amount,
                    'netAmount' => (int) $withdrawal->net_amount,
                    'status' => $withdrawal->status,
                    'requestedAt' => $withdrawal->requested_at,
                    'readyAt' => $withdrawal->ready_at,
                    'completedAt' => $withdrawal->completed_at,
                    'requesterName' => $withdrawal->requester_name,
                ])
                ->values(),
        ];
    }

    public function determineFeeBreakdown(int $amount): array
    {
        $normalizedAmount = max(0, $amount);
        $adminFee = min(self::ADMIN_FEE_IDR, $normalizedAmount);

        return [
            'admin_fee_amount' => $adminFee,
            'seller_net_amount' => max(0, $normalizedAmount - $adminFee),
        ];
    }

    public function emptySummary(?string $guildName = null): array
    {
        return [
            'guildId' => null,
            'guildName' => $guildName,
            'adminFeePerSale' => self::ADMIN_FEE_IDR,
            'withdrawalFee' => self::WITHDRAWAL_FEE_IDR,
            'freezeDays' => self::BALANCE_FREEZE_DAYS,
            'withdrawalProcessingDays' => self::WITHDRAWAL_PROCESSING_DAYS,
            'paidTransactionsCount' => 0,
            'buyersCount' => 0,
            'grossSalesTotal' => 0,
            'adminFeeTotal' => 0,
            'netSalesTotal' => 0,
            'frozenBalance' => 0,
            'maturedBalance' => 0,
            'availableBalance' => 0,
            'processingWithdrawalBalance' => 0,
            'readyWithdrawalBalance' => 0,
            'completedWithdrawalBalance' => 0,
            'recentPayments' => collect(),
            'recentWithdrawals' => collect(),
        ];
    }

    private function syncReadyWithdrawals(string $guildId): void
    {
        VipTitleWithdrawal::query()
            ->where('guild_id', $guildId)
            ->where('status', 'processing')
            ->whereNotNull('ready_at')
            ->where('ready_at', '<=', now())
            ->update(['status' => 'ready']);
    }

    private function countDistinctBuyers(Collection $payments): int
    {
        return $payments
            ->map(fn (VipTitlePayment $payment) => $this->resolveBuyerKey($payment))
            ->filter()
            ->unique()
            ->count();
    }

    private function resolveBuyerKey(VipTitlePayment $payment): ?string
    {
        $discordUserId = trim((string) ($payment->buyer_discord_user_id ?: $payment->claim?->discord_user_id));
        if ($discordUserId !== '') {
            return 'discord:'.$discordUserId;
        }

        $email = trim((string) $payment->buyer_email);
        if ($email !== '') {
            return 'email:'.strtolower($email);
        }

        $username = trim((string) $payment->claim?->roblox_username);
        if ($username !== '') {
            return 'roblox:'.strtolower($username);
        }

        return null;
    }

    private function resolveBuyerLabel(VipTitlePayment $payment): string
    {
        $discordUserId = trim((string) ($payment->buyer_discord_user_id ?: $payment->claim?->discord_user_id));
        if ($discordUserId !== '') {
            return '@'.$discordUserId;
        }

        $username = trim((string) $payment->claim?->roblox_username);
        if ($username !== '') {
            return '@'.$username;
        }

        return trim((string) $payment->buyer_email) ?: 'Buyer tidak diketahui';
    }

    private function resolveAdminFeeAmount(VipTitlePayment $payment): int
    {
        $storedAmount = (int) $payment->admin_fee_amount;
        if ($storedAmount > 0) {
            return $storedAmount;
        }

        return min(self::ADMIN_FEE_IDR, max(0, (int) $payment->amount));
    }

    private function resolveSellerNetAmount(VipTitlePayment $payment): int
    {
        $storedAmount = (int) $payment->seller_net_amount;
        if ($storedAmount > 0) {
            return $storedAmount;
        }

        return max(0, ((int) $payment->amount) - $this->resolveAdminFeeAmount($payment));
    }

    private function resolveFrozenUntil(VipTitlePayment $payment): ?CarbonInterface
    {
        if ($payment->frozen_until instanceof CarbonInterface) {
            return $payment->frozen_until;
        }

        return $payment->paid_at?->copy()->addDays(self::BALANCE_FREEZE_DAYS);
    }

    private function isFrozen(VipTitlePayment $payment): bool
    {
        $frozenUntil = $this->resolveFrozenUntil($payment);

        return ! $frozenUntil || now()->lt($frozenUntil);
    }
}
