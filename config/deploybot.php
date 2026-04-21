<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Slack configuration
    |---------------------------------------------------------------------------
    |
    | `signing_secret` is the HMAC secret for verifying incoming Slack webhooks
    | (slash commands + interactions). Obtained from the Slack app's "Basic
    | Information" page → App Credentials. Required for the bot to accept
    | requests from Slack — mis-set values will cause every webhook to 401.
    |
    | `bot_token` is the `xoxb-…` OAuth token used to post messages back
    | through the Slack Web API. Obtained from the same app's "OAuth &
    | Permissions" page after installing to a workspace.
    |
    | `admin_user_ids` is the allowlist of Slack user IDs permitted to invoke
    | commands whose `requiresAdmin()` returns true. Use Slack user IDs
    | (`U01ABC…`), NOT usernames, since usernames can be changed. Comma-
    | separated in env, parsed into an array below.
    |
    | `max_timestamp_skew_seconds` guards against replay attacks on slash
    | commands: requests whose `X-Slack-Request-Timestamp` differs from
    | wallclock by more than this are rejected. Slack's own recommendation
    | is 5 minutes (300 seconds).
    */
    'slack' => [
        'signing_secret' => env('SLACK_SIGNING_SECRET'),
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'admin_user_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('DEPLOYBOT_SLACK_ADMIN_USER_IDS', '')),
        ))),
        'max_timestamp_skew_seconds' => (int) env('DEPLOYBOT_SLACK_MAX_SKEW_SECONDS', 300),
        'api_base_url' => (string) env('SLACK_API_BASE_URL', 'https://slack.com/api'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Discord configuration
    |---------------------------------------------------------------------------
    |
    | Discord webhook URLs per "purpose". Keys are arbitrary — host apps
    | reference them by key when posting, e.g.
    | `DiscordWebhookClient::forPurpose('release')`. Add more entries by
    | extending this array; no code change required to surface a new
    | destination.
    */
    'discord' => [
        'webhooks' => [
            'release' => env('DISCORD_RELEASE_WEBHOOK'),
            'ci' => env('DISCORD_CI_WEBHOOK'),
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Route mounting
    |---------------------------------------------------------------------------
    |
    | The library's webhook routes are mounted under `prefix` by the service
    | provider. Host apps can override to avoid collisions — e.g. set
    | `DEPLOYBOT_ROUTE_PREFIX=ops/bot` to move the Slack endpoints to
    | `https://host.example/ops/bot/slack/command`.
    |
    | `middleware` is applied to all deploybot routes in addition to the
    | library's own signature-verification middleware (which always runs).
    */
    'routes' => [
        'prefix' => (string) env('DEPLOYBOT_ROUTE_PREFIX', 'deploybot'),
        'middleware' => ['api'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Command dispatcher
    |---------------------------------------------------------------------------
    |
    | `default_command` is invoked when a slash-command's text doesn't match
    | any registered handler. The built-in HelpCommand lists all registered
    | commands, which is a sensible default; host apps can override.
    */
    'commands' => [
        'default_command' => 'help',
    ],
];
