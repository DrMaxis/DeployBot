# deploybot

Reusable Slack + Discord bot library for Afria products. Installable as a
composer package into any Laravel 11+ application.

## What's inside

- **HMAC signature verification** for incoming Slack webhooks
  (timestamp-skew replay guard, constant-time compare)
- **Pluggable slash-command dispatcher** — host apps register their
  commands; the library handles routing, admin allowlisting, and
  exception capture
- **Slack Web API client** wrapping the methods commands actually need
  (`chat.postMessage`, `chat.postEphemeral`)
- **Discord webhook client** (one-way outbound)
- **Channel-subscription plumbing** — a model + migration for tracking
  which Slack channels want which product events (e.g.
  `alverium.release.published`)
- **Block Kit response envelope** types so handler code doesn't hand-
  assemble JSON
- **A built-in `help` command** that lists whatever host commands are
  registered

The library does not ship any product-specific commands — those live in
the host app and register with the library.

## Install

```bash
composer require afria/deploybot
```

Laravel auto-discovers the service provider via `extra.laravel.providers`.
No manual registration needed.

## Configure

Publish the config file (optional — reasonable defaults work for most
cases; the env vars below are the only required config):

```bash
php artisan vendor:publish --tag=deploybot-config
php artisan vendor:publish --tag=deploybot-migrations
php artisan migrate
```

Set these in your host app's `.env`:

```ini
# Slack app signing secret (Basic Information → App Credentials).
# Required. Missing value → every webhook returns 401 (fail-closed).
SLACK_SIGNING_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Slack bot OAuth token (OAuth & Permissions → Bot User OAuth Token).
# Required for posting messages from commands.
SLACK_BOT_TOKEN=xoxb-...

# Slack user IDs permitted to run admin-gated commands.
# Comma-separated. User IDs, not usernames.
DEPLOYBOT_SLACK_ADMIN_USER_IDS=U01ABC123,U02DEF456

# Discord webhook URL(s), one per purpose key. Optional — commands
# that call `DiscordWebhookClient::has(...)` can gate on presence.
DISCORD_RELEASE_WEBHOOK=https://discord.com/api/webhooks/.../...
DISCORD_CI_WEBHOOK=https://discord.com/api/webhooks/.../...
```

## Register commands

In any of your host app's service providers, implement commands and
register them with the `CommandRegistry`:

```php
use Afria\Deploybot\Commands\CommandRegistry;

public function boot(): void
{
    app(CommandRegistry::class)
        ->register(\App\Slack\Commands\ReleasesCommand::class);
}
```

A command is any class implementing `Afria\Deploybot\Commands\CommandInterface`:

```php
use Afria\Deploybot\Commands\CommandContext;
use Afria\Deploybot\Commands\CommandInterface;
use Afria\Deploybot\Commands\CommandResponse;

final class ReleasesCommand implements CommandInterface
{
    public function __construct(
        private readonly ReleaseRepository $repo,
    ) {}

    public static function name(): string
    {
        return 'releases';
    }

    public static function description(): string
    {
        return 'List the last five releases.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        $releases = $this->repo->latest(5);

        return CommandResponse::forChannel(
            text: 'Recent releases',
            blocks: [ /* Block Kit */ ],
        );
    }
}
```

Constructor deps resolve through Laravel's container at invocation time
— inject whatever services your command needs.

## Point Slack at your app

The library mounts these routes:

- `POST /{prefix}/slack/command` — slash-command webhook
- `POST /{prefix}/slack/interaction` — interactive-component webhook
  (Block Kit action callbacks, modal submissions)

`{prefix}` is `deploybot` by default; override via
`DEPLOYBOT_ROUTE_PREFIX` env.

In your Slack app's config:

- **Slash Commands**: set the request URL to
  `https://yourhost.example/deploybot/slack/command`
- **Interactivity & Shortcuts**: set the request URL to
  `https://yourhost.example/deploybot/slack/interaction`

The library's `VerifySlackSignature` middleware is hard-wired onto both
routes, so unsigned + stale requests (> 5 min skew) are rejected with
HTTP 401 before reaching your commands.

## Broadcast to Discord

```php
use Afria\Deploybot\Discord\DiscordWebhookClient;

public function __invoke(DiscordWebhookClient $discord, Release $release): void
{
    if (! $discord->has('release')) {
        return; // not configured in this env — skip silently
    }

    $discord->post(
        purpose: 'release',
        content: "Alverium {$release->version} released",
        embeds: [ /* Discord embed objects */ ],
        username: 'Alverium Releases',
    );
}
```

## Broadcast to subscribed Slack channels

```php
use Afria\Deploybot\Models\ChannelSubscription;
use Afria\Deploybot\Slack\SlackApi;

public function __invoke(SlackApi $slack, Release $release): void
{
    ChannelSubscription::forEvent('alverium.release.published')
        ->get()
        ->each(function (ChannelSubscription $sub) use ($slack, $release): void {
            try {
                $slack->postMessage(
                    channel: $sub->channel_id,
                    text: "Alverium {$release->version} released",
                    blocks: [ /* Block Kit */ ],
                );
            } catch (\RuntimeException $e) {
                // log + continue — one bad channel shouldn't poison the fan-out
                report($e);
            }
        });
}
```

Channel subscriptions are populated via a host-registered `/follow`
command (coming in Alverium's Slice H3).

## Security posture

- **Signatures are always verified.** The middleware is hard-wired on
  the routes; there is no way to disable it from config. Missing
  `SLACK_SIGNING_SECRET` → every request 401s (fail-closed).
- **Replay attacks blocked** by 5-minute max skew on the Slack timestamp
  header. Tunable via `DEPLOYBOT_SLACK_MAX_SKEW_SECONDS` in edge cases.
- **Admin allowlist is ID-based**, not username-based — Slack usernames
  can be changed and are not stable identifiers.
- **No command runs with more privilege than the host app's container
  grants.** Commands are just container-resolved classes; inject
  what they need and nothing more.

## Development

```bash
composer install
./vendor/bin/pest           # run tests
./vendor/bin/phpstan        # static analysis (level 8)
./vendor/bin/pint           # code style (Laravel preset + project extras)
./vendor/bin/pint --test    # style check only, no writes
```

## License

Proprietary — Afria Technologies. Not for redistribution outside the
Afria product family.
