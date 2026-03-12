<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipTitleClaim;
use App\Models\VipTitleMapSetting;
use App\Models\VipTitlePayment;
use App\Services\Payments\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VipTitleClaimController extends Controller
{
    private const RESERVED_TERMS = ['admin', 'administrator', 'dev', 'developer', 'owner', 'mod', 'moderator', 'staff'];
    private const PROFANITY_TERMS = ['anjing', 'babi', 'bangsat', 'kontol', 'memek', 'ngentot', 'goblok', 'tolol', 'jancok', 'fuck', 'bitch'];

    public function maps(Request $request): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $items = VipTitleMapSetting::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'map_key',
                'gamepass_id',
                'claim_mode',
                'api_key',
                'title_slot',
                'title_price_idr',
                'payment_expiry_minutes',
                'button_label',
                'place_ids',
                'script_access_role_ids',
                'is_active',
                'notes',
            ]);

        return response()->json([
            'items' => $items,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $validated = $request->validate([
            'map_key' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = VipTitleClaim::query()->latest('requested_at');

        if (! empty($validated['map_key'])) {
            $query->where('map_key', $validated['map_key']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $claims = $query->take($validated['limit'] ?? 10)->get();

        return response()->json([
            'items' => $claims,
        ]);
    }

    public function paymentStatus(Request $request, string $merchantOrderId): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $payment = VipTitlePayment::query()
            ->with('claim')
            ->where('merchant_order_id', $merchantOrderId)
            ->firstOrFail();

        return response()->json([
            'payment' => [
                'merchantOrderId' => $payment->merchant_order_id,
                'reference' => $payment->duitku_reference,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'paymentMethod' => $payment->payment_method,
                'paymentUrl' => $payment->payment_url,
                'expiresAt' => $payment->expires_at,
                'paidAt' => $payment->paid_at,
            ],
            'claim' => [
                'id' => $payment->claim?->id,
                'mapKey' => $payment->claim?->map_key,
                'robloxUsername' => $payment->claim?->roblox_username,
                'requestedTitle' => $payment->claim?->requested_title,
                'titleStyle' => $this->normalizeTitleStyle($payment->claim?->meta['title_style'] ?? null),
                'status' => $payment->claim?->status,
                'requestedAt' => $payment->claim?->requested_at,
                'consumedAt' => $payment->claim?->consumed_at,
            ],
        ]);
    }

    public function checkout(Request $request, DuitkuService $duitku): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $validated = $request->validate([
            'map_key' => ['required', 'string', 'max:64'],
            'roblox_user_id' => ['required', 'integer', 'min:0'],
            'roblox_username' => ['required', 'string', 'max:255'],
            'requested_title' => ['required', 'string', 'min:3', 'max:28'],
            'discord_user_id' => ['nullable', 'string', 'max:255'],
            'discord_tag' => ['nullable', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'payment_method' => ['required', 'string', 'max:32'],
            'meta' => ['nullable', 'array'],
        ]);

        $mapSetting = $this->resolveActiveMapSetting($validated['map_key']);
        if (! $mapSetting) {
            return response()->json([
                'message' => sprintf('Map key "%s" belum aktif di dashboard VIP Title.', $this->normalizeMapKey($validated['map_key'])),
            ], 422);
        }

        if (! $this->usesPaidCheckout($mapSetting)) {
            return response()->json([
                'message' => 'Map ini tidak memakai flow pembayaran Duitku.',
            ], 422);
        }

        $amount = (int) ($mapSetting->title_price_idr ?? 0);
        if ($amount <= 0) {
            return response()->json([
                'message' => 'Harga title untuk map ini belum diatur di dashboard.',
            ], 422);
        }

        $title = $this->sanitizeTitle($validated['requested_title']);
        $reason = $this->validateTitle($title);

        if ($reason !== null) {
            return response()->json([
                'message' => $reason,
            ], 422);
        }

        $claimMeta = $this->normalizeClaimMeta(array_filter([
            ...($validated['meta'] ?? []),
            'claim_mode' => 'duitku',
            'price_idr' => $amount,
        ], static fn ($value) => $value !== null));

        $claim = VipTitleClaim::query()->create([
            'map_key' => $mapSetting->map_key,
            'gamepass_id' => 0,
            'roblox_user_id' => $validated['roblox_user_id'],
            'roblox_username' => $validated['roblox_username'],
            'requested_title' => $title,
            'discord_user_id' => $validated['discord_user_id'] ?? null,
            'discord_tag' => $validated['discord_tag'] ?? null,
            'status' => 'awaiting_payment',
            'requested_at' => now(),
            'meta' => $claimMeta,
        ]);

        $merchantOrderId = sprintf('VIPTITLE-%s-%s', $claim->id, Str::upper(Str::random(8)));
        $buyerEmail = $validated['buyer_email']
            ?? $duitku->buildSyntheticEmail((string) ($validated['discord_user_id'] ?? $validated['roblox_user_id']));
        $expiryMinutes = max(5, (int) ($mapSetting->payment_expiry_minutes ?? 60));
        $baseUrl = rtrim((string) config('app.url'), '/');

        try {
            $checkout = $duitku->createTransaction([
                'paymentAmount' => $amount,
                'paymentMethod' => $validated['payment_method'],
                'merchantOrderId' => $merchantOrderId,
                'productDetails' => sprintf('VIP Title %s - %s', $mapSetting->name, $title),
                'merchantUserInfo' => $validated['discord_tag'] ?? $validated['roblox_username'],
                'customerVaName' => $validated['roblox_username'],
                'email' => $buyerEmail,
                'phoneNumber' => (string) config('services.duitku.default_phone_number', ''),
                'itemDetails' => [[
                    'name' => sprintf('VIP Title %s', $mapSetting->name),
                    'price' => $amount,
                    'quantity' => 1,
                ]],
                'callbackUrl' => $baseUrl.route('payments.duitku.callback', absolute: false),
                'returnUrl' => $baseUrl.route('payments.duitku.return', ['merchantOrderId' => $merchantOrderId], false),
                'expiryPeriod' => $expiryMinutes,
            ]);
        } catch (\Throwable $exception) {
            $claim->update([
                'status' => 'payment_failed',
                'meta' => array_filter([
                    ...($claim->meta ?? []),
                    'payment_error' => $exception->getMessage(),
                ], static fn ($value) => $value !== null),
            ]);

            throw $exception;
        }

        $payment = VipTitlePayment::query()->create([
            'vip_title_claim_id' => $claim->id,
            'map_key' => $mapSetting->map_key,
            'merchant_order_id' => $merchantOrderId,
            'duitku_reference' => $checkout['reference'] ?? null,
            'amount' => $amount,
            'status' => 'pending',
            'payment_url' => $checkout['paymentUrl'] ?? null,
            'payment_method' => $checkout['paymentMethod'] ?? config('services.duitku.payment_method'),
            'expires_at' => now()->addMinutes($expiryMinutes),
            'buyer_email' => $buyerEmail,
            'callback_payload' => [
                'create_transaction_response' => $checkout,
            ],
        ]);

        return response()->json([
            'flow' => 'duitku',
            'claim' => $claim,
            'payment' => [
                'id' => $payment->id,
                'merchantOrderId' => $merchantOrderId,
                'amount' => $payment->amount,
                'paymentUrl' => $payment->payment_url,
                'paymentMethod' => $payment->payment_method,
                'reference' => $payment->duitku_reference,
                'expiresAt' => $payment->expires_at,
            ],
        ], 201);
    }

    public function paymentMethods(Request $request, DuitkuService $duitku): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $validated = $request->validate([
            'map_key' => ['required', 'string', 'max:64'],
        ]);

        $mapSetting = $this->resolveActiveMapSetting($validated['map_key']);
        if (! $mapSetting) {
            return response()->json([
                'message' => sprintf('Map key "%s" belum aktif di dashboard VIP Title.', $this->normalizeMapKey($validated['map_key'])),
            ], 422);
        }

        if (! $this->usesPaidCheckout($mapSetting)) {
            return response()->json([
                'message' => 'Map ini belum mengaktifkan pembelian title via Duitku.',
            ], 422);
        }

        $amount = (int) ($mapSetting->title_price_idr ?? 0);
        $response = $duitku->getPaymentMethods($amount);

        return response()->json([
            'amount' => $amount,
            'items' => collect($response['paymentFee'])
                ->filter(fn ($item) => is_array($item) && ! empty($item['paymentMethod']))
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $validated = $request->validate([
            'map_key' => ['required', 'string', 'max:64'],
            'gamepass_id' => ['nullable', 'integer', 'min:0'],
            'roblox_user_id' => ['required', 'integer', 'min:1'],
            'roblox_username' => ['required', 'string', 'max:255'],
            'requested_title' => ['required', 'string', 'min:3', 'max:28'],
            'discord_user_id' => ['nullable', 'string', 'max:255'],
            'discord_tag' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $mapKey = $this->normalizeMapKey($validated['map_key']);
        $mapSetting = $this->resolveActiveMapSetting($mapKey);

        if (! $mapSetting) {
            return response()->json([
                'message' => sprintf('Map key "%s" belum aktif di dashboard VIP Title.', $mapKey),
            ], 422);
        }

        $title = $this->sanitizeTitle($validated['requested_title']);
        $reason = $this->validateTitle($title);

        if ($reason !== null) {
            return response()->json([
                'message' => $reason,
            ], 422);
        }

        $existingPending = VipTitleClaim::query()
            ->where('map_key', $mapKey)
            ->where(function ($query) use ($validated) {
                $query->where('roblox_user_id', $validated['roblox_user_id']);

                if ((int) $validated['roblox_user_id'] === 0) {
                    $query->orWhereRaw('LOWER(roblox_username) = ?', [strtolower($validated['roblox_username'])]);
                }
            })
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if ($existingPending) {
            $existingPending->update([
                'requested_title' => $title,
                'gamepass_id' => $mapSetting->gamepass_id,
                'discord_user_id' => $validated['discord_user_id'] ?? null,
                'discord_tag' => $validated['discord_tag'] ?? null,
                'meta' => $this->normalizeClaimMeta($validated['meta'] ?? null),
                'requested_at' => now(),
            ]);

            return response()->json([
                'claim' => $existingPending->fresh(),
                'updated' => true,
            ]);
        }

        $claim = VipTitleClaim::query()->create([
            'map_key' => $mapKey,
            'gamepass_id' => $mapSetting->gamepass_id,
            'roblox_user_id' => $validated['roblox_user_id'],
            'roblox_username' => $validated['roblox_username'],
            'requested_title' => $title,
            'discord_user_id' => $validated['discord_user_id'] ?? null,
            'discord_tag' => $validated['discord_tag'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
            'meta' => $this->normalizeClaimMeta($validated['meta'] ?? null),
        ]);

        return response()->json([
            'claim' => $claim,
            'created' => true,
        ], 201);
    }

    public function pull(Request $request): JsonResponse
    {
        abort_unless($this->hasRobloxApiKey($request), 401);

        $validated = $request->validate([
            'userId' => ['required', 'integer', 'min:1'],
            'username' => ['nullable', 'string', 'max:255'],
            'mapKey' => ['required', 'string', 'max:64'],
            'placeId' => ['nullable', 'string', 'max:255'],
            'universeId' => ['nullable', 'string', 'max:255'],
        ]);

        $claim = VipTitleClaim::query()
            ->where('map_key', $this->normalizeMapKey($validated['mapKey']))
            ->where('status', 'pending')
            ->where(function ($query) use ($validated) {
                $query->where('roblox_user_id', $validated['userId']);

                if (! empty($validated['username'])) {
                    $query->orWhere(function ($usernameQuery) use ($validated) {
                        $usernameQuery
                            ->where('roblox_user_id', 0)
                            ->whereRaw('LOWER(roblox_username) = ?', [strtolower((string) $validated['username'])]);
                    });
                }
            })
            ->oldest('requested_at')
            ->first();

        if (! $claim) {
            return response()->json([
                'claim' => null,
            ]);
        }

        return response()->json([
            'claim' => [
                'claimId' => $claim->id,
                'title' => $claim->requested_title,
                'titleMeta' => $this->normalizeTitleStyle($claim->meta['title_style'] ?? null),
                'mapKey' => $claim->map_key,
                'gamepassId' => $claim->gamepass_id,
                'requestedAt' => $claim->requested_at,
                'placeId' => $validated['placeId'] ?? null,
                'universeId' => $validated['universeId'] ?? null,
            ],
        ]);
    }

    public function consume(Request $request): JsonResponse
    {
        abort_unless($this->hasRobloxApiKey($request), 401);

        $validated = $request->validate([
            'claimId' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:255'],
            'mapKey' => ['nullable', 'string', 'max:64'],
            'placeId' => ['nullable', 'string', 'max:255'],
            'universeId' => ['nullable', 'string', 'max:255'],
        ]);

        $claim = VipTitleClaim::query()->findOrFail($validated['claimId']);

        if (! empty($validated['mapKey']) && $claim->map_key !== $this->normalizeMapKey($validated['mapKey'])) {
            return response()->json([
                'message' => 'Map key claim tidak cocok.',
            ], 422);
        }

        $claim->update([
            'status' => $validated['status'],
            'consumed_at' => now(),
            'consumed_place_id' => $validated['placeId'] ?? null,
            'consumed_universe_id' => $validated['universeId'] ?? null,
            'meta' => array_filter([
                ...($claim->meta ?? []),
                'consume_reason' => $validated['reason'] ?? null,
            ], static fn ($value) => $value !== null),
        ]);

        return response()->json([
            'ok' => true,
            'status' => $claim->status,
        ]);
    }

    private function hasBotToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }

    private function resolveActiveMapSetting(string $mapKey): ?VipTitleMapSetting
    {
        return VipTitleMapSetting::query()
            ->where('map_key', $this->normalizeMapKey($mapKey))
            ->where('is_active', true)
            ->first();
    }

    private function usesPaidCheckout(VipTitleMapSetting $setting): bool
    {
        return (int) ($setting->title_price_idr ?? 0) > 0;
    }

    private function hasRobloxApiKey(Request $request): bool
    {
        $token = config('services.discord.internal_token');
        $providedToken = (string) $request->header('X-Api-Key');

        if (is_string($token)
            && $token !== ''
            && hash_equals($token, $providedToken)) {
            return true;
        }

        return VipTitleMapSetting::query()
            ->where('is_active', true)
            ->get(['api_key'])
            ->contains(fn (VipTitleMapSetting $setting) => is_string($setting->api_key) && $setting->api_key !== '' && hash_equals($setting->api_key, $providedToken));
    }

    private function normalizeMapKey(string $value): string
    {
        return strtolower(trim($value));
    }

    private function sanitizeTitle(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $value)) ?? '');
    }

    private function normalizeText(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $value) ?? '');
    }

    private function validateTitle(string $title): ?string
    {
        $normalized = $this->normalizeText($title);

        foreach (self::RESERVED_TERMS as $term) {
            if (str_contains($normalized, $this->normalizeText($term))) {
                return sprintf('Title tidak boleh memakai kata seperti "%s".', $term);
            }
        }

        foreach (self::PROFANITY_TERMS as $term) {
            if (str_contains($normalized, $this->normalizeText($term))) {
                return 'Title mengandung kata yang tidak diperbolehkan.';
            }
        }

        return null;
    }

    private function normalizeClaimMeta(?array $meta): ?array
    {
        $normalized = is_array($meta) ? $meta : [];
        $titleStyle = $this->normalizeTitleStyle($normalized['title_style'] ?? null);

        if ($titleStyle !== null) {
            $normalized['title_style'] = $titleStyle;
        } else {
            unset($normalized['title_style']);
        }

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeTitleStyle(mixed $titleStyle): ?array
    {
        if (! is_array($titleStyle)) {
            return null;
        }

        $mode = strtoupper(trim((string) ($titleStyle['mode'] ?? 'SOLID')));
        if (! in_array($mode, ['SOLID', 'RGB'], true)) {
            $mode = 'SOLID';
        }

        $preset = strtoupper(trim((string) ($titleStyle['preset'] ?? 'VIP')));
        if ($preset === '') {
            $preset = 'VIP';
        }

        $color = is_array($titleStyle['color'] ?? null) ? $titleStyle['color'] : [];
        $label = trim((string) ($titleStyle['label'] ?? ''));

        return [
            'mode' => $mode,
            'preset' => Str::upper(Str::limit($preset, 40, '')),
            'color' => [
                'r' => max(0, min(255, (int) ($color['r'] ?? 255))),
                'g' => max(0, min(255, (int) ($color['g'] ?? 255))),
                'b' => max(0, min(255, (int) ($color['b'] ?? 255))),
            ],
            'label' => $label !== '' ? Str::limit($label, 60, '') : null,
        ];
    }
}
