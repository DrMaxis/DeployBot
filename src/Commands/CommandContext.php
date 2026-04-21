<?php

declare(strict_types=1);

namespace Afria\Deploybot\Commands;

use Illuminate\Http\Request;

/**
 * Immutable carrier for the context of a single slash-command invocation.
 *
 * Slack's slash-command payload has ~a dozen fields; this DTO surfaces
 * the ones our command handlers actually need, in a typed shape.
 * Additional raw fields are available via `raw()` for commands that want
 * to reach into the full payload.
 *
 * Instances are produced by `fromRequest()` and are cheap to clone with
 * modified args via `withArgs()` (used by the dispatcher when it parses
 * the command name off the front of the text and forwards the remainder
 * to the handler).
 */
final class CommandContext
{
    /**
     * @param  array<string, string>  $raw  Full Slack payload (preserved so
     *                                      handlers can reach into fields
     *                                      we don't type here yet).
     */
    public function __construct(
        public readonly string $teamId,
        public readonly string $teamDomain,
        public readonly string $channelId,
        public readonly string $channelName,
        public readonly string $userId,
        public readonly string $userName,
        public readonly string $commandName,
        public readonly string $text,
        public readonly string $responseUrl,
        public readonly string $triggerId,
        /** @var array<int, string> Space-split args from `$text`. */
        public readonly array $args,
        private readonly array $raw,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $get = fn (string $key): string => (string) $request->input($key, '');

        $text = $get('text');
        $args = $text === '' ? [] : (preg_split('/\s+/', trim($text)) ?: []);

        /** @var array<string, string> $raw */
        $raw = array_filter(
            $request->all(),
            static fn ($v) => is_string($v),
        );

        return new self(
            teamId: $get('team_id'),
            teamDomain: $get('team_domain'),
            channelId: $get('channel_id'),
            channelName: $get('channel_name'),
            userId: $get('user_id'),
            userName: $get('user_name'),
            commandName: ltrim($get('command'), '/'),
            text: $text,
            responseUrl: $get('response_url'),
            triggerId: $get('trigger_id'),
            args: array_values(array_filter($args, static fn (string $s) => $s !== '')),
            raw: $raw,
        );
    }

    /**
     * Return a copy with the args array replaced. Used by the dispatcher
     * to strip the first token (the command name) before passing to the
     * handler.
     *
     * @param  array<int, string>  $args
     */
    public function withArgs(array $args): self
    {
        return new self(
            teamId: $this->teamId,
            teamDomain: $this->teamDomain,
            channelId: $this->channelId,
            channelName: $this->channelName,
            userId: $this->userId,
            userName: $this->userName,
            commandName: $this->commandName,
            text: $this->text,
            responseUrl: $this->responseUrl,
            triggerId: $this->triggerId,
            args: $args,
            raw: $this->raw,
        );
    }

    /**
     * Raw payload map for handlers that need an un-typed field.
     *
     * @return array<string, string>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
