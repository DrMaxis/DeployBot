# DeployBot

[![Packagist Version](https://img.shields.io/packagist/v/drmaxis/deploybot.svg?style=flat-square)](https://packagist.org/packages/drmaxis/deploybot)
[![Tests](https://img.shields.io/github/actions/workflow/status/DrMaxis/DeployBot/ci.yml?branch=develop&style=flat-square&label=tests)](https://github.com/DrMaxis/DeployBot/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/drmaxis/deploybot.svg?style=flat-square)](LICENSE)

A Slack + Discord bot framework for Laravel. Drop it in, register your slash commands, ship.

## Install

```bash
composer require drmaxis/deploybot
```

```bash
php artisan vendor:publish --tag=deploybot-config
php artisan vendor:publish --tag=deploybot-migrations
php artisan vendor:publish --tag=deploybot-routes
php artisan migrate
```

## Configure

```bash
# .env
DEPLOYBOT_SLACK_SIGNING_SECRET=
DEPLOYBOT_SLACK_BOT_TOKEN=

# optional — one per logical channel
DEPLOYBOT_DISCORD_WEBHOOK_RELEASES=
DEPLOYBOT_DISCORD_WEBHOOK_INCIDENTS=
```

## Register a command

```php
use DrMaxis\Deploybot\Commands\CommandContext;
use DrMaxis\Deploybot\Commands\CommandInterface;
use DrMaxis\Deploybot\Commands\CommandResponse;

class PingCommand implements CommandInterface
{
    public static function name(): string { return 'ping'; }
    public static function description(): string { return 'Reply with pong.'; }
    public static function requiresAdmin(): bool { return false; }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::message('pong');
    }
}
```

Register from a service provider:

```php
public function boot(CommandRegistry $registry): void
{
    $registry->register(PingCommand::class);
}
```

Point your Slack app's slash command at `https://your-app.example/deploybot/slack/command`. The built-in `help` command lists every registered command.

## What's in the box

- HMAC signature verification (constant-time, with timestamp-skew replay guard)
- Slash-command dispatcher with admin allowlisting
- Slack Web API client (`chat.postMessage`, `chat.postEphemeral`)
- Discord webhook client (multi-purpose routing)
- Channel-subscription model for broadcasting events
- Block Kit response envelope types
- Auto-discovered service provider · Laravel 12 / 13

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Report privately via [GitHub security advisories](https://github.com/DrMaxis/DeployBot/security/advisories/new). See [SECURITY.md](SECURITY.md).

## License

MIT
