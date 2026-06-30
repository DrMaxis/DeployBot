<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * @see https://discord.com/developers/docs/resources/webhook#execute-webhook
 */
class DiscordWebhookClient
{
    private readonly ClientInterface $http;

    public function __construct(
        private readonly array $webhooks,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client([
            'timeout' => 10.0,
            'connect_timeout' => 5.0,
            'http_errors' => false,
        ]);
    }

    public function has(string $purpose): bool
    {
        return isset($this->webhooks[$purpose]) && $this->webhooks[$purpose] !== '';
    }

    public function post(
        string $purpose,
        string $content,
        array $embeds = [],
        ?string $username = null,
    ): void {
        if (! $this->has($purpose)) {
            throw new InvalidArgumentException(
                "Discord webhook for purpose `{$purpose}` is not configured. ".
                'Set the corresponding env var referenced in `config/deploybot.php`.',
            );
        }

        $payload = ['content' => $content];
        if ($embeds !== []) {
            $payload['embeds'] = $embeds;
        }
        if ($username !== null && $username !== '') {
            $payload['username'] = $username;
        }

        try {
            $response = $this->http->request('POST', $this->webhooks[$purpose], [
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                ],
                RequestOptions::JSON => $payload,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException("Discord webhook `{$purpose}` network error: ".$e->getMessage(), $e->getCode(), previous: $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                "Discord webhook `{$purpose}` HTTP {$status}: ".
                $response->getBody(),
            );
        }
    }
}
