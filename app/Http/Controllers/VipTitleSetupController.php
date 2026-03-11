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
    public function index(): View
    {
        $settings = VipTitleMapSetting::query()->latest('updated_at')->get();
        $claims = VipTitleClaim::query()->latest('requested_at')->take(10)->get();

        return view('vip-title.setup', [
            'settings' => $settings,
            'claims' => $claims,
            'appUrl' => rtrim((string) config('app.url'), '/'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        VipTitleMapSetting::query()->create([
            'name' => $validated['name'],
            'map_key' => $this->normalizeMapKey($validated['map_key']),
            'gamepass_id' => $validated['gamepass_id'],
            'api_key' => $this->generateApiKey(),
            'title_slot' => $validated['title_slot'],
            'place_ids' => $this->parsePlaceIds($validated['place_ids'] ?? ''),
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Map VIP Title berhasil ditambahkan.');
    }

    public function update(Request $request, VipTitleMapSetting $setting): RedirectResponse
    {
        $validated = $this->validatePayload($request, $setting->id);

        $setting->update([
            'name' => $validated['name'],
            'map_key' => $this->normalizeMapKey($validated['map_key']),
            'gamepass_id' => $validated['gamepass_id'],
            'title_slot' => $validated['title_slot'],
            'place_ids' => $this->parsePlaceIds($validated['place_ids'] ?? ''),
            'is_active' => $request->boolean('is_active', false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', "Map {$setting->map_key} berhasil diperbarui.");
    }

    public function regenerateKey(VipTitleMapSetting $setting): RedirectResponse
    {
        $setting->update([
            'api_key' => $this->generateApiKey(),
        ]);

        return back()->with('status', "API key untuk {$setting->map_key} berhasil diganti.");
    }

    public function destroy(VipTitleMapSetting $setting): RedirectResponse
    {
        $mapKey = $setting->map_key;
        $setting->delete();

        return back()->with('status', "Map {$mapKey} berhasil dihapus.");
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
            'place_ids' => ['nullable', 'string', 'max:2000'],
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

    private function normalizeMapKey(string $value): string
    {
        return strtolower(trim($value));
    }

    private function generateApiKey(): string
    {
        return 'lyva_'.Str::random(40);
    }
}
