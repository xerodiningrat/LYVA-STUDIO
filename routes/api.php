<?php

use App\Http\Controllers\Api\BotReportController;
use App\Http\Controllers\Api\BotRaceEventController;
use App\Http\Controllers\Api\BotRaceRegistrationController;
use App\Http\Controllers\Api\BotRulesAcknowledgementController;
use App\Http\Controllers\Api\BotSalesController;
use App\Http\Controllers\Api\BotStatusController;
use App\Http\Controllers\Api\BotGuildSettingController;
use App\Http\Controllers\Api\BotVerificationController;
use App\Http\Controllers\Api\RobloxSalesEventController;
use App\Http\Controllers\Api\VipTitleClaimController;
use Illuminate\Support\Facades\Route;

Route::get('/bot/status', BotStatusController::class)->name('api.bot.status');
Route::get('/bot/sales', BotSalesController::class)->name('api.bot.sales');
Route::post('/bot/reports', BotReportController::class)->name('api.bot.reports');
Route::post('/bot/rules/acknowledgements', [BotRulesAcknowledgementController::class, 'store'])->name('api.bot.rules.acknowledgements.store');
Route::get('/bot/guild-settings/{guildId}', [BotGuildSettingController::class, 'show'])->name('api.bot.guild-settings.show');
Route::put('/bot/guild-settings/{guildId}', [BotGuildSettingController::class, 'upsert'])->name('api.bot.guild-settings.upsert');
Route::get('/bot/verifications/{discordUserId}', [BotVerificationController::class, 'show'])->name('api.bot.verifications.show');
Route::post('/bot/verifications', [BotVerificationController::class, 'store'])->name('api.bot.verifications.store');
Route::delete('/bot/verifications/{discordUserId}', [BotVerificationController::class, 'destroy'])->name('api.bot.verifications.destroy');
Route::get('/bot/races', [BotRaceEventController::class, 'index'])->name('api.bot.races.index');
Route::post('/bot/races', [BotRaceEventController::class, 'store'])->name('api.bot.races.store');
Route::get('/bot/races/{event}', [BotRaceEventController::class, 'show'])->name('api.bot.races.show');
Route::patch('/bot/races/{event}', [BotRaceEventController::class, 'update'])->name('api.bot.races.update');
Route::post('/bot/races/{event}/join', [BotRaceRegistrationController::class, 'store'])->name('api.bot.races.join');
Route::get('/bot/vip-title-maps', [VipTitleClaimController::class, 'maps'])->name('api.bot.vip-title-maps.index');
Route::get('/bot/vip-title-claims', [VipTitleClaimController::class, 'index'])->name('api.bot.vip-title-claims.index');
Route::post('/bot/vip-title-claims', [VipTitleClaimController::class, 'store'])->name('api.bot.vip-title-claims.store');
Route::post('/roblox/vip-title-claims/pull', [VipTitleClaimController::class, 'pull'])->name('api.roblox.vip-title-claims.pull');
Route::post('/roblox/vip-title-claims/consume', [VipTitleClaimController::class, 'consume'])->name('api.roblox.vip-title-claims.consume');
Route::post('/roblox/sales-events', RobloxSalesEventController::class)->name('api.roblox.sales-events');
