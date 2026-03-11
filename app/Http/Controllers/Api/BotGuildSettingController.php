<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscordGuildSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotGuildSettingController extends Controller
{
    public function show(Request $request, string $guildId): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $setting = DiscordGuildSetting::query()
            ->where('guild_id', $guildId)
            ->first();

        if (! $setting) {
            return response()->json([
                'guild_id' => $guildId,
                'exists' => false,
            ]);
        }

        return response()->json([
            'guild_id' => $setting->guild_id,
            'exists' => true,
            'verification_channel_id' => $setting->verification_channel_id,
            'verification_message_id' => $setting->verification_message_id,
            'verification_role_id' => $setting->verification_role_id,
            'ticket_panel_channel_id' => $setting->ticket_panel_channel_id,
            'ticket_panel_message_id' => $setting->ticket_panel_message_id,
            'ticket_support_role_id' => $setting->ticket_support_role_id,
            'ticket_category_id' => $setting->ticket_category_id,
            'ticket_log_channel_id' => $setting->ticket_log_channel_id,
            'spam_enabled' => $setting->spam_enabled,
            'spam_announcement_channel_id' => $setting->spam_announcement_channel_id,
            'spam_log_channel_id' => $setting->spam_log_channel_id,
            'spam_threshold' => $setting->spam_threshold,
            'spam_window_seconds' => $setting->spam_window_seconds,
        ]);
    }

    public function upsert(Request $request, string $guildId): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'verification_channel_id' => ['nullable', 'string', 'max:255'],
            'verification_message_id' => ['nullable', 'string', 'max:255'],
            'verification_role_id' => ['nullable', 'string', 'max:255'],
            'ticket_panel_channel_id' => ['nullable', 'string', 'max:255'],
            'ticket_panel_message_id' => ['nullable', 'string', 'max:255'],
            'ticket_support_role_id' => ['nullable', 'string', 'max:255'],
            'ticket_category_id' => ['nullable', 'string', 'max:255'],
            'ticket_log_channel_id' => ['nullable', 'string', 'max:255'],
            'spam_enabled' => ['nullable', 'boolean'],
            'spam_announcement_channel_id' => ['nullable', 'string', 'max:255'],
            'spam_log_channel_id' => ['nullable', 'string', 'max:255'],
            'spam_threshold' => ['nullable', 'integer', 'min:2', 'max:10'],
            'spam_window_seconds' => ['nullable', 'integer', 'min:10', 'max:300'],
        ]);

        $setting = DiscordGuildSetting::query()->updateOrCreate(
            ['guild_id' => $guildId],
            $validated,
        );

        return response()->json([
            'guild_id' => $setting->guild_id,
            'verification_channel_id' => $setting->verification_channel_id,
            'verification_message_id' => $setting->verification_message_id,
            'verification_role_id' => $setting->verification_role_id,
            'ticket_panel_channel_id' => $setting->ticket_panel_channel_id,
            'ticket_panel_message_id' => $setting->ticket_panel_message_id,
            'ticket_support_role_id' => $setting->ticket_support_role_id,
            'ticket_category_id' => $setting->ticket_category_id,
            'ticket_log_channel_id' => $setting->ticket_log_channel_id,
            'spam_enabled' => $setting->spam_enabled,
            'spam_announcement_channel_id' => $setting->spam_announcement_channel_id,
            'spam_log_channel_id' => $setting->spam_log_channel_id,
            'spam_threshold' => $setting->spam_threshold,
            'spam_window_seconds' => $setting->spam_window_seconds,
        ]);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }
}
