<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    'slack' => [
        'signing_secret' => env('SLACK_SIGNING_SECRET'),
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'admin_user_ids' => Str::of((string) env('DEPLOYBOT_SLACK_ADMIN_USER_IDS', ''))
            ->explode(',')
            ->map(fn (string $s): string => trim($s))
            ->filter()
            ->values()
            ->all(),
        'max_timestamp_skew_seconds' => (int) env('DEPLOYBOT_SLACK_MAX_SKEW_SECONDS', 300),
        'api_base_url' => (string) env('SLACK_API_BASE_URL', 'https://slack.com/api'),
    ],

    'discord' => [
        'webhooks' => [
            'release' => env('DISCORD_RELEASE_WEBHOOK'),
            'ci' => env('DISCORD_CI_WEBHOOK'),
        ],
    ],

    'routes' => [
        'prefix' => (string) env('DEPLOYBOT_ROUTE_PREFIX', 'deploybot'),
        'middleware' => ['api'],
    ],

    'commands' => [
        'default_command' => 'help',
    ],
];
