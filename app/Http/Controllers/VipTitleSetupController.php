<?php

namespace App\Http\Controllers;

use App\Models\VipTitleClaim;
use App\Models\VipTitleMapSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VipTitleSetupController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->workspaceContext($request);
        $this->claimLegacySettings($request, $workspace);

        $settings = $this->workspaceSettingsQuery($request, $workspace)
            ->latest('updated_at')
            ->get();

        $claims = VipTitleClaim::query()
            ->when(
                $settings->isNotEmpty(),
                fn ($query) => $query->whereIn('map_key', $settings->pluck('map_key')->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->latest('requested_at')
            ->take(10)
            ->get();

        return view('vip-title.setup', [
            'settings' => $settings,
            'claims' => $claims,
            'appUrl' => rtrim((string) config('app.url'), '/'),
            'managedGuild' => $workspace,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $this->workspaceContext($request);
        $validated = $this->validatePayload($request);
        $price = $this->normalizePrice($validated['title_price_idr'] ?? null);

        VipTitleMapSetting::query()->create([
            'guild_id' => $workspace['id'],
            'guild_name' => $workspace['name'],
            'owner_user_id' => $request->user()?->id,
            'owner_discord_user_id' => (string) ($request->user()?->discord_user_id ?? ''),
            'name' => $validated['name'],
            'map_key' => $this->normalizeMapKey($validated['map_key']),
            'gamepass_id' => $validated['gamepass_id'],
            'claim_mode' => $this->resolveClaimMode($price),
            'api_key' => $this->generateApiKey(),
            'title_slot' => $validated['title_slot'],
            'title_price_idr' => $price,
            'payment_expiry_minutes' => $validated['payment_expiry_minutes'] ?? 60,
            'button_label' => $validated['button_label'] ?? null,
            'place_ids' => $this->parsePlaceIds($validated['place_ids'] ?? ''),
            'script_access_role_ids' => $this->parseRoleIds($validated['script_access_role_ids'] ?? ''),
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Map VIP Title berhasil ditambahkan.');
    }

    public function update(Request $request, VipTitleMapSetting $setting): RedirectResponse
    {
        $workspace = $this->workspaceContext($request);
        $this->authorizeSettingAccess($request, $setting, $workspace);

        $validated = $this->validatePayload($request, $setting->id);
        $price = $this->normalizePrice($validated['title_price_idr'] ?? null);

        $setting->update([
            'guild_id' => $workspace['id'],
            'guild_name' => $workspace['name'],
            'owner_user_id' => $request->user()?->id,
            'owner_discord_user_id' => (string) ($request->user()?->discord_user_id ?? ''),
            'name' => $validated['name'],
            'map_key' => $this->normalizeMapKey($validated['map_key']),
            'gamepass_id' => $validated['gamepass_id'],
            'claim_mode' => $this->resolveClaimMode($price),
            'title_slot' => $validated['title_slot'],
            'title_price_idr' => $price,
            'payment_expiry_minutes' => $validated['payment_expiry_minutes'] ?? 60,
            'button_label' => $validated['button_label'] ?? null,
            'place_ids' => $this->parsePlaceIds($validated['place_ids'] ?? ''),
            'script_access_role_ids' => $this->parseRoleIds($validated['script_access_role_ids'] ?? ''),
            'is_active' => $request->boolean('is_active', false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', "Map {$setting->map_key} berhasil diperbarui.");
    }

    public function regenerateKey(Request $request, VipTitleMapSetting $setting): RedirectResponse
    {
        $workspace = $this->workspaceContext($request);
        $this->authorizeSettingAccess($request, $setting, $workspace);

        $setting->update([
            'api_key' => $this->generateApiKey(),
        ]);

        return back()->with('status', "API key untuk {$setting->map_key} berhasil diganti.");
    }

    public function destroy(Request $request, VipTitleMapSetting $setting): RedirectResponse
    {
        $workspace = $this->workspaceContext($request);
        $this->authorizeSettingAccess($request, $setting, $workspace);

        $mapKey = $setting->map_key;
        $setting->delete();

        return back()->with('status', "Map {$mapKey} berhasil dihapus.");
    }

    private function workspaceContext(Request $request): array
    {
        $guild = $request->session()->get('managed_guild');
        $guildId = trim((string) data_get($guild, 'id'));
        $guildName = trim((string) data_get($guild, 'name', 'Server aktif'));
        $discordUserId = trim((string) ($request->user()?->discord_user_id ?? ''));

        abort_if($guildId === '', 403, 'Pilih server Discord dulu sebelum membuka config VIP Title.');
        abort_if($discordUserId === '', 403, 'Login lewat Discord dulu sebelum membuka config VIP Title.');

        return [
            'id' => $guildId,
            'name' => $guildName !== '' ? $guildName : 'Server aktif',
        ];
    }

    private function workspaceSettingsQuery(Request $request, array $workspace)
    {
        $discordUserId = trim((string) ($request->user()?->discord_user_id ?? ''));

        return VipTitleMapSetting::query()
            ->where('guild_id', $workspace['id'])
            ->where('owner_discord_user_id', $discordUserId);
    }

    private function claimLegacySettings(Request $request, array $workspace): void
    {
        VipTitleMapSetting::query()
            ->whereNull('guild_id')
            ->whereNull('owner_discord_user_id')
            ->update([
                'guild_id' => $workspace['id'],
                'guild_name' => $workspace['name'],
                'owner_user_id' => $request->user()?->id,
                'owner_discord_user_id' => (string) ($request->user()?->discord_user_id ?? ''),
            ]);
    }

    private function authorizeSettingAccess(Request $request, VipTitleMapSetting $setting, array $workspace): void
    {
        $discordUserId = trim((string) ($request->user()?->discord_user_id ?? ''));

        abort_unless(
            (string) ($setting->guild_id ?? '') === $workspace['id']
                && (string) ($setting->owner_discord_user_id ?? '') === $discordUserId,
            404
        );
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'map_key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_-]+$/i',
                Rule::unique('vip_title_map_settings', 'map_key')->ignore($ignoreId),
            ],
            'gamepass_id' => ['required', 'integer', 'min:0'],
            'title_slot' => ['required', 'integer', 'min:1', 'max:10'],
            'title_price_idr' => ['nullable', 'integer', 'min:1000', 'max:100000000'],
            'payment_expiry_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'button_label' => ['nullable', 'string', 'max:100'],
            'place_ids' => ['nullable', 'string', 'max:2000'],
            'script_access_role_ids' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function parsePlaceIds(string $raw): array
    {
        return collect(preg_split('/[\s,]+/', trim($raw)) ?: [])
            ->filter()
            ->map(fn (string $value) => trim($value))
            ->values()
            ->all();
    }

    private function parseRoleIds(string $raw): array
    {
        return collect(preg_split('/[\s,]+/', trim($raw)) ?: [])
            ->filter()
            ->map(fn (string $value) => trim($value))
            ->filter(fn (string $value) => preg_match('/^\d+$/', $value) === 1)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeMapKey(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizePrice(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function resolveClaimMode(?int $price): string
    {
        return $price !== null && $price > 0
            ? 'duitku'
            : 'vip_gamepass';
    }

    private function generateApiKey(): string
    {
        return 'lyva_'.Str::random(40);
    }
}
