<?php

declare(strict_types=1);

namespace Afria\Deploybot\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;

/**
 * One-way POST client for Discord incoming webhooks.
 *
 * Discord webhooks are outbound-only — we post messages; Discord doesn't
 * send anything back. No bot identity or OAuth needed; the webhook URL
 * itself is the credential.
 *
 * ## Purpose keys
 *
 * Rather than hardcode webhook URLs in callers, the client holds a map
 * of `purpose => url` supplied at construction by the service provider
 * from `config('deploybot.discord.webhooks')`. Callers post by purpose
 * key, e.g. `$client->post('release', ...)`, which makes it trivial to
 * re-route channels without grep'ing for URLs, and keeps the URL (a
 * secret) out of caller code.
 *
 * ## Missing purposes fail loudly
 *
 * Calling `post('nonexistent', ...)` throws rather than silently no-op-ing.
 * Silent broadcast failure is worse than a missing log line: if a release
 * is supposed to fire but doesn't, we want the exception in Sentry, not
 * radio silence.
 *
 * ## Why not `final`
 *
 * Omitted deliberately so host-app tests can spy the `post()` method
 * via Mockery. Subclassing in production is not the intent — the
 * configured instance from the service provider is the one you want.
 *
 * @see https://discord.com/developers/docs/resources/webhook#execute-webhook
 */
class DiscordWebhookClient
{
    private ClientInterface $http;

    /**
     * @param  array<string, string>  $webhooks  Map of purpose → URL. Purposes
     *                                           with null/empty URLs should be
     *                                           filtered out by the caller
     *                                           (the provider does this) so
     *                                           `$this->has()` stays honest.
     */
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

    /**
     * Whether a webhook URL is configured for the given purpose.
     *
     * Host apps can use this to silently skip broadcasts in envs where
     * the webhook isn't set — e.g. a local dev env without a Discord
     * server.
     */
    public function has(string $purpose): bool
    {
        return isset($this->webhooks[$purpose]) && $this->webhooks[$purpose] !== '';
    }

    /**
     * Post a message to the given purpose's webhook.
     *
     * @param  array<int, array<string, mixed>>  $embeds  Discord embed
     *                                                    objects; see the
     *                                                    Discord docs for
     *                                                    the full schema.
     *
     * @throws InvalidArgumentException When the purpose isn't configured.
     * @throws RuntimeException On network failure or non-2xx status.
     */
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
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Discord webhook `{$purpose}` network error: ".$e->getMessage(),
                previous: $e,
            );
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                "Discord webhook `{$purpose}` HTTP {$status}: ".
                (string) $response->getBody(),
            );
        }
    }
}
