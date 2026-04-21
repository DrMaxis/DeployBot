<?php

declare(strict_types=1);

namespace Afria\Deploybot\Commands;

/**
 * Built-in help command — lists every registered command with its
 * one-line description.
 *
 * Registered by `DeploybotServiceProvider::boot()`. Host apps can
 * `override()` with their own variant if they want custom formatting.
 *
 * When the dispatcher can't match a user's input to any registered
 * command name, it falls back to the `default_command` config value
 * (which defaults to `help`) — so typos get a friendly "here's what
 * I can do" instead of silent failure.
 */
final class HelpCommand implements CommandInterface
{
    public function __construct(
        private readonly CommandRegistry $registry,
    ) {}

    public static function name(): string
    {
        return 'help';
    }

    public static function description(): string
    {
        return 'List available commands.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        $commands = $this->registry->all();
        ksort($commands);

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "/{$ctx->commandName} — available commands",
                ],
            ],
        ];

        if ($commands === []) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '_No commands are registered yet._',
                ],
            ];

            return CommandResponse::forUser('No commands registered.', $blocks);
        }

        $lines = [];
        foreach ($commands as $name => $class) {
            $adminMark = $class::requiresAdmin() ? ' _(admin)_' : '';
            $lines[] = "• `{$name}` — {$class::description()}{$adminMark}";
        }

        $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => implode("\n", $lines),
            ],
        ];

        $text = 'Available commands: '.implode(', ', array_keys($commands));

        return CommandResponse::forUser($text, $blocks);
    }
}
