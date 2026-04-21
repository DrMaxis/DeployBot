<?php

declare(strict_types=1);

namespace Afria\Deploybot\Commands;

/**
 * The shape a command handler returns to the dispatcher.
 *
 * Mirrors Slack's slash-command response envelope:
 * https://api.slack.com/interactivity/slash-commands#responding_to_commands
 *
 * The two most common cases are:
 *
 *   - `forUser()` — visible only to the invoker (`response_type: ephemeral`).
 *     Default for errors + confirmations.
 *
 *   - `forChannel()` — visible to everyone in the channel
 *     (`response_type: in_channel`). Use for commands that are meant to
 *     share information (e.g. `/alverium releases`).
 *
 * Both accept either a plain-text `text` or an array of Block Kit
 * `blocks`. Slack displays whichever is provided; if both, blocks win
 * and `text` serves as the fallback for notifications.
 */
final class CommandResponse
{
    /**
     * @param  'ephemeral'|'in_channel'  $responseType
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function __construct(
        public readonly string $responseType,
        public readonly string $text,
        public readonly array $blocks = [],
    ) {}

    /**
     * Ephemeral response — only the user who ran the command sees it.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public static function forUser(string $text, array $blocks = []): self
    {
        return new self('ephemeral', $text, $blocks);
    }

    /**
     * In-channel response — visible to everyone in the channel where
     * the command was invoked.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public static function forChannel(string $text, array $blocks = []): self
    {
        return new self('in_channel', $text, $blocks);
    }

    /**
     * Serialise into the exact JSON shape Slack expects.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'response_type' => $this->responseType,
            'text' => $this->text,
        ];

        if ($this->blocks !== []) {
            $payload['blocks'] = $this->blocks;
        }

        return $payload;
    }
}
