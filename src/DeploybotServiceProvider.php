<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot;

use DrMaxis\Deploybot\Commands\CommandDispatcher;
use DrMaxis\Deploybot\Commands\CommandRegistry;
use DrMaxis\Deploybot\Commands\HelpCommand;
use DrMaxis\Deploybot\Discord\DiscordWebhookClient;
use DrMaxis\Deploybot\Slack\SlackApi;
use DrMaxis\Deploybot\Slack\SlackSignatureVerifier;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Override;

class DeploybotServiceProvider extends ServiceProvider
{
    #[Override]
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
                static fn (?string $url): bool => is_string($url) && $url !== '',
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
        $this->app->make(CommandRegistry::class)->registerDefault(HelpCommand::class);

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
