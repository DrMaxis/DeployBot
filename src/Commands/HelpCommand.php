<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Commands;

class HelpCommand implements CommandInterface
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
