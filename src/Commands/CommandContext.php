<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Commands;

use Illuminate\Http\Request;

class CommandContext
{
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
        public readonly array $args,
        private readonly array $raw,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $get = fn (string $key): string => (string) $request->input($key, '');

        $text = $get('text');
        $args = $text === '' ? [] : (preg_split('/\s+/', trim($text)) ?: []);

        $raw = array_filter(
            $request->all(),
            is_string(...),
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
            args: array_values(array_filter($args)),
            raw: $raw,
        );
    }

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

    public function raw(): array
    {
        return $this->raw;
    }
}
