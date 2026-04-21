<?php

declare(strict_types=1);

namespace Afria\Deploybot;

use Afria\Deploybot\Commands\CommandDispatcher;
use Afria\Deploybot\Commands\CommandRegistry;
use Afria\Deploybot\Commands\HelpCommand;
use Afria\Deploybot\Discord\DiscordWebhookClient;
use Afria\Deploybot\Slack\SlackApi;
use Afria\Deploybot\Slack\SlackSignatureVerifier;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider that makes the deploybot library available inside a
 * Laravel host app once composer-required.
 *
 * Registration surface (what this provider does for the host app):
 *
 * - Merges `config/deploybot.php` into the host app's config under the
 *   `deploybot` key.
 * - Binds four singletons — `CommandRegistry`, `SlackSignatureVerifier`,
 *   `SlackApi`, `DiscordWebhookClient`, `CommandDispatcher` — so host
 *   code can type-hint them and get fully-configured instances.
 * - Registers the built-in `HelpCommand` in the registry. Host apps
 *   register their own commands via
 *   `app(CommandRegistry::class)->register(MyCommand::class)` from their
 *   own service provider's `boot()` method.
 * - Loads the package's webhook routes under the configurable prefix
 *   (`deploybot` by default) with the configured middleware stack plus
 *   the library's `VerifySlackSignature` middleware hard-wired on top.
 * - Loads package migrations for the `deploybot_channel_subscriptions`
 *   table. Host apps can either use these directly (run `migrate`) or
 *   publish + customise via `php artisan vendor:publish --tag=deploybot-migrations`.
 * - Exposes three publish tags: `deploybot-config`, `deploybot-migrations`,
 *   `deploybot-routes`.
 *
 * The provider is auto-discovered via the `extra.laravel.providers` entry
 * in `composer.json`; host apps do not need to register it manually.
 */
final class DeploybotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/deploybot.php', 'deploybot');

        $this->app->singleton(
            CommandRegistry::class,
            static fn (): CommandRegistry => new CommandRegistry
        );

        $this->app->singleton(SlackSignatureVerifier::class, static fn (): SlackSignatureVerifier => new SlackSignatureVerifier(
            signingSecret: (string) config('deploybot.slack.signing_secret', ''),
            maxSkewSeconds: (int) config('deploybot.slack.max_timestamp_skew_seconds', 300),
        ));

        $this->app->singleton(SlackApi::class, static fn (): SlackApi => new SlackApi(
            botToken: (string) config('deploybot.slack.bot_token', ''),
            apiBaseUrl: (string) config('deploybot.slack.api_base_url', 'https://slack.com/api'),
        ));

        $this->app->singleton(DiscordWebhookClient::class, static function (): DiscordWebhookClient {
            /** @var array<string, string|null> $webhooks */
            $webhooks = (array) config('deploybot.discord.webhooks', []);

            return new DiscordWebhookClient(webhooks: array_filter(
                $webhooks,
                static fn ($url): bool => is_string($url) && $url !== '',
            ));
        });

        $this->app->singleton(CommandDispatcher::class, fn (): CommandDispatcher => new CommandDispatcher(
            registry: $this->app->make(CommandRegistry::class),
            container: $this->app,
            defaultCommandName: (string) config('deploybot.commands.default_command', 'help'),
            adminUserIds: (array) config('deploybot.slack.admin_user_ids', []),
        ));
    }

    public function boot(): void
    {
        $this->app->make(CommandRegistry::class)->register(HelpCommand::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix((string) config('deploybot.routes.prefix', 'deploybot'))
            ->middleware((array) config('deploybot.routes.middleware', ['api']))
            ->group(__DIR__.'/../routes/webhooks.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/deploybot.php' => config_path('deploybot.php'),
            ], 'deploybot-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'deploybot-migrations');

            $this->publishes([
                __DIR__.'/../routes/webhooks.php' => base_path('routes/deploybot.php'),
            ], 'deploybot-routes');
        }
    }
}
