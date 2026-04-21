<?php

declare(strict_types=1);

namespace Afria\Deploybot\Slack;

/**
 * Verifies that an incoming HTTP request from Slack carries a valid HMAC
 * signature, per Slack's documented signing scheme.
 *
 * ## The scheme
 *
 * Slack sends two headers on every webhook request:
 *
 *   X-Slack-Request-Timestamp: <unix seconds>
 *   X-Slack-Signature:         v0=<hex-encoded HMAC-SHA256>
 *
 * The signature is computed over the concatenation:
 *
 *   sigBaseString = "v0:" + timestamp + ":" + rawRequestBody
 *
 * and HMAC-SHA256'd with the app's signing secret (from the Slack app's
 * "Basic Information" page). We recompute the same string server-side and
 * constant-time compare.
 *
 * ## Replay protection
 *
 * A valid signature from a recorded request is still valid forever unless
 * we reject stale timestamps. Slack recommends ~5 minute skew (300s); this
 * verifier takes the allowed skew as a constructor arg so tests can use
 * a tight window without a clock fake.
 *
 * ## Why a pure class (not a middleware)
 *
 * Middlewares need `Request` instances and a container to resolve. This
 * class is pure so unit tests can feed it raw strings, and the middleware
 * (`VerifySlackSignature`) is a tiny adapter on top.
 *
 * @see https://api.slack.com/authentication/verifying-requests-from-slack
 */
final class SlackSignatureVerifier
{
    public function __construct(
        private readonly string $signingSecret,
        private readonly int $maxSkewSeconds = 300,
    ) {}

    /**
     * Verify a request's signature + timestamp.
     *
     * @param  string  $rawBody  The exact unparsed request body. The
     *                           host must NOT trim / re-encode; Slack
     *                           signs the literal bytes.
     * @param  string  $timestampHeader  Value of `X-Slack-Request-Timestamp`.
     * @param  string  $signatureHeader  Value of `X-Slack-Signature`
     *                                   (includes the `v0=` prefix).
     * @param  int|null  $now  Override wallclock for deterministic
     *                         tests; defaults to `time()`.
     */
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
            // Replay / stale request.
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
     * Compute the signature for an outgoing request. Test-only; no runtime
     * caller needs to sign requests leaving the app.
     *
     * @internal
     */
    public function sign(string $rawBody, int $timestamp): string
    {
        $base = 'v0:'.$timestamp.':'.$rawBody;

        return 'v0='.hash_hmac('sha256', $base, $this->signingSecret);
    }
}
