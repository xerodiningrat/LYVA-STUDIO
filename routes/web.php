<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscordInteractionController;
use App\Http\Controllers\DiscordSetupController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\RobloxScriptController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::post('/discord/interactions', DiscordInteractionController::class)
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('discord.interactions');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('discord/setup', DiscordSetupController::class)->name('discord.setup');
    Route::get('roblox/scripts', [RobloxScriptController::class, 'index'])->name('roblox.scripts.index');
    Route::get('roblox/scripts/{slug}', [RobloxScriptController::class, 'show'])->name('roblox.scripts.show');
    Route::get('roblox/scripts/{slug}/download', [RobloxScriptController::class, 'download'])->name('roblox.scripts.download');
});

require __DIR__.'/settings.php';
