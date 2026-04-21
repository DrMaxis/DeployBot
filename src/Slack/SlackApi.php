<?php

declare(strict_types=1);

namespace Afria\Deploybot\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Minimal Guzzle-backed wrapper around the Slack Web API.
 *
 * The scope today is deliberately small: what deploybot's first wave
 * of commands actually needs. Expand method-by-method as new host-app
 * commands require more of Slack's surface.
 *
 * ## Why a thin wrapper (not JoliCode/slack-php-api or similar)
 *
 * The full Slack OpenAPI client pulls in megabytes of generated code
 * for methods we don't use. A hand-written wrapper keeps our dep graph
 * light, makes it trivial to mock in tests (just stub the Guzzle client
 * via the constructor), and documents the exact contract the rest of
 * the library assumes.
 *
 * ## Bot-token scope
 *
 * The configured `SLACK_BOT_TOKEN` must carry the scopes each method
 * below uses:
 *
 *   - `chat.postMessage`       → `chat:write`
 *   - `chat.postEphemeral`     → `chat:write`
 *   - `conversations.list`     → `channels:read`, `groups:read`, etc.
 *
 * Scope mismatches come back as Slack `error: not_allowed_token_type` /
 * `error: missing_scope` — this class turns both into a `RuntimeException`
 * so callers don't have to parse the JSON envelope themselves.
 *
 * @see https://api.slack.com/methods
 */
final class SlackApi
{
    private ClientInterface $http;

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

    /**
     * Post a message to a Slack channel (public or DM, as authorised).
     *
     * @param  string  $channel  Channel id (`C0...`) OR user id (`U0...`)
     *                           for DMs.
     * @param  array<int, array<string, mixed>>  $blocks  Block Kit blocks.
     *                                                    When non-empty,
     *                                                    `$text` is still
     *                                                    required — Slack
     *                                                    uses it as the
     *                                                    notification
     *                                                    preview.
     *
     * @return array<string, mixed> Parsed response body. `ok === true`
     *                              on success.
     *
     * @throws RuntimeException On network failure, non-2xx status, or
     *                          `ok: false` response.
     */
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

    /**
     * Post an ephemeral message — visible only to a single user.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
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

    /**
     * @param  array<string, mixed>  $payload
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
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
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Slack API `{$method}` network error: ".$e->getMessage(),
                previous: $e,
            );
        }

        return $this->decode($method, $response);
    }

    /** @return array<string, mixed> */
    private function decode(string $method, ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                "Slack API `{$method}` HTTP {$status}: {$body}",
            );
        }

        /** @var array<string, mixed>|null $decoded */
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
