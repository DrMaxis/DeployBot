<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Commands;

/**
 * @see https://api.slack.com/interactivity/slash-commands#responding_to_commands
 */
class CommandResponse
{
    private function __construct(
        public readonly string $responseType,
        public readonly string $text,
        public readonly array $blocks = [],
    ) {}

    public static function forUser(string $text, array $blocks = []): self
    {
        return new self('ephemeral', $text, $blocks);
    }

    public static function forChannel(string $text, array $blocks = []): self
    {
        return new self('in_channel', $text, $blocks);
    }

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
