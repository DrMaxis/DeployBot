<?php

declare(strict_types=1);

use DrMaxis\Deploybot\Http\Controllers\SlackCommandController;
use DrMaxis\Deploybot\Http\Controllers\SlackInteractionController;
use DrMaxis\Deploybot\Http\Middleware\VerifySlackSignature;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifySlackSignature::class)->group(function (): void {
    Route::post('slack/command', SlackCommandController::class)
        ->name('deploybot.slack.command');

    Route::post('slack/interaction', SlackInteractionController::class)
        ->name('deploybot.slack.interaction');
});
