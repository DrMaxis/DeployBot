<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * @see https://api.slack.com/methods
 */
class SlackApi
{
    private readonly ClientInterface $http;

    public function __construct(
        private readonly string $botToken,
        private readonly string $apiBaseUrl = 'https://slack.com/api',
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client([
            'base_uri' => rtrim($this->apiBaseUrl, '/').'/',
            'timeout' => 10.0,
            'connect_timeout' => 5.0,
            'http_errors' => false,
        ]);
    }

    public function postMessage(string $channel, string $text, array $blocks = []): array
    {
        $payload = [
            'channel' => $channel,
            'text' => $text,
        ];
        if ($blocks !== []) {
            $payload['blocks'] = $blocks;
        }

        return $this->post('chat.postMessage', $payload);
    }

    public function postEphemeral(string $channel, string $user, string $text, array $blocks = []): array
    {
        $payload = [
            'channel' => $channel,
            'user' => $user,
            'text' => $text,
        ];
        if ($blocks !== []) {
            $payload['blocks'] = $blocks;
        }

        return $this->post('chat.postEphemeral', $payload);
    }

    private function post(string $method, array $payload): array
    {
        if ($this->botToken === '') {
            throw new RuntimeException(
                'SLACK_BOT_TOKEN is not configured. Set it in the host app env ',
            );
        }

        try {
            $response = $this->http->request('POST', $method, [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$this->botToken,
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                ],
                RequestOptions::JSON => $payload,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException("Slack API `{$method}` network error: ".$e->getMessage(), $e->getCode(), previous: $e);
        }

        return $this->decode($method, $response);
    }

    private function decode(string $method, ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                "Slack API `{$method}` HTTP {$status}: {$body}",
            );
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException(
                "Slack API `{$method}` returned unparseable body: {$body}",
            );
        }

        if (($decoded['ok'] ?? false) !== true) {
            $error = (string) ($decoded['error'] ?? 'unknown_error');
            throw new RuntimeException(
                "Slack API `{$method}` responded ok=false, error=`{$error}`",
            );
        }

        return $decoded;
    }
}
