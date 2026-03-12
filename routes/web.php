<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscordAuthController;
use App\Http\Controllers\DiscordInteractionController;
use App\Http\Controllers\DiscordSetupController;
use App\Http\Controllers\DuitkuPaymentController;
use App\Http\Controllers\GuildSelectionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\RobloxScriptController;
use App\Http\Controllers\VipTitleWalletController;
use App\Http\Controllers\VipTitleWithdrawalController;
use App\Http\Controllers\VipTitleSetupController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirect'])->name('auth.discord.redirect');
Route::get('/auth/discord/callback', [DiscordAuthController::class, 'callback'])->name('auth.discord.callback');
Route::post('/discord/interactions', DiscordInteractionController::class)
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('discord.interactions');
Route::get('/payments/duitku/return', [DuitkuPaymentController::class, 'return'])->name('payments.duitku.return');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('guilds/select', [GuildSelectionController::class, 'index'])->name('guilds.select');
    Route::post('guilds/select/{guildId}', [GuildSelectionController::class, 'select'])->name('guilds.select.store');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/wallet/earnings', [VipTitleWalletController::class, 'earnings'])->name('dashboard.wallet.earnings');
    Route::get('dashboard/wallet/withdrawals', [VipTitleWalletController::class, 'withdrawals'])->name('dashboard.wallet.withdrawals.index');
    Route::post('dashboard/wallet/withdrawals', [VipTitleWithdrawalController::class, 'store'])->name('dashboard.wallet.withdrawals.store');
    Route::get('discord/setup', DiscordSetupController::class)->name('discord.setup');
    Route::get('vip-title/setup', [VipTitleSetupController::class, 'index'])->name('vip-title.setup');
    Route::post('vip-title/setup/maps', [VipTitleSetupController::class, 'store'])->name('vip-title.setup.store');
    Route::put('vip-title/setup/maps/{setting}', [VipTitleSetupController::class, 'update'])->name('vip-title.setup.update');
    Route::post('vip-title/setup/maps/{setting}/regenerate-key', [VipTitleSetupController::class, 'regenerateKey'])->name('vip-title.setup.regenerate-key');
    Route::delete('vip-title/setup/maps/{setting}', [VipTitleSetupController::class, 'destroy'])->name('vip-title.setup.destroy');
    Route::get('roblox/scripts', [RobloxScriptController::class, 'index'])->name('roblox.scripts.index');
    Route::get('roblox/scripts/{slug}', [RobloxScriptController::class, 'show'])->name('roblox.scripts.show');
    Route::get('roblox/scripts/{slug}/download', [RobloxScriptController::class, 'download'])->name('roblox.scripts.download');
});

require __DIR__.'/settings.php';
