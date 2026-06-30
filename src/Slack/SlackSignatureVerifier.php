<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Slack;

/**
 * @see https://api.slack.com/authentication/verifying-requests-from-slack
 */
class SlackSignatureVerifier
{
    public function __construct(
        private readonly string $signingSecret,
        private readonly int $maxSkewSeconds = 300,
    ) {}

    public function isValid(
        string $rawBody,
        string $timestampHeader,
        string $signatureHeader,
        ?int $now = null,
    ): bool {
        if ($this->signingSecret === '' || $timestampHeader === '' || $signatureHeader === '') {
            return false;
        }

        if (! ctype_digit($timestampHeader)) {
            return false;
        }

        $ts = (int) $timestampHeader;
        $now ??= time();

        if (abs($now - $ts) > $this->maxSkewSeconds) {
            return false;
        }

        if (! str_starts_with($signatureHeader, 'v0=')) {
            return false;
        }

        $received = substr($signatureHeader, 3);
        $base = 'v0:'.$timestampHeader.':'.$rawBody;
        $expected = hash_hmac('sha256', $base, $this->signingSecret);

        return hash_equals($expected, $received);
    }

    /**
     * @internal
     */
    public function sign(string $rawBody, int $timestamp): string
    {
        $base = 'v0:'.$timestamp.':'.$rawBody;

        return 'v0='.hash_hmac('sha256', $base, $this->signingSecret);
    }
}
