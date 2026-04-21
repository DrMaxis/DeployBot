<?php

declare(strict_types=1);

use Afria\Deploybot\Http\Controllers\SlackCommandController;
use Afria\Deploybot\Http\Controllers\SlackInteractionController;
use Afria\Deploybot\Http\Middleware\VerifySlackSignature;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------------
| Deploybot webhook routes
|-------------------------------------------------------------------------------
|
| Mounted by `DeploybotServiceProvider::boot()` under
| `config('deploybot.routes.prefix')` (default: `deploybot`) with
| `config('deploybot.routes.middleware')` applied.
|
| Every route in this group also runs the library's
| `VerifySlackSignature` middleware, which rejects unsigned/expired
| requests with a 401 — that's hard-wired below rather than in config so
| a well-meaning host app can't accidentally drop it.
|
| Host apps that want their own URL layout can publish this file with
| `php artisan vendor:publish --tag=deploybot-routes` and customise.
| The controllers + middleware are public API.
*/

Route::middleware(VerifySlackSignature::class)->group(function (): void {
    Route::post('slack/command', SlackCommandController::class)
        ->name('deploybot.slack.command');

    Route::post('slack/interaction', SlackInteractionController::class)
        ->name('deploybot.slack.interaction');
});
