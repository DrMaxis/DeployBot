<?php

declare(strict_types=1);

namespace Afria\Deploybot\Http\Middleware;

use Afria\Deploybot\Slack\SlackSignatureVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate Slack webhook routes on a valid HMAC signature.
 *
 * The actual cryptographic work is delegated to
 * `SlackSignatureVerifier`; this middleware is a thin adapter that
 * extracts the raw request body + headers, feeds them to the verifier,
 * and returns 401 when the check fails.
 *
 * Signed requests pass through with their Request unchanged. The route
 * handlers can then `->input()` parsed form data as usual (Slack posts
 * `application/x-www-form-urlencoded` for slash commands).
 *
 * ## Why we don't short-circuit on missing signing secret
 *
 * If `SLACK_SIGNING_SECRET` isn't configured, the verifier returns false
 * for every request — every webhook 401s. That's the safe behaviour:
 * fail-closed so a misconfigured env can never silently accept spoofed
 * traffic. The visible 401s + error logs make the misconfig loud.
 *
 * @see SlackSignatureVerifier
 */
final class VerifySlackSignature
{
    public function __construct(
        private readonly SlackSignatureVerifier $verifier,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $rawBody = (string) $request->getContent();
        $timestamp = (string) $request->header('X-Slack-Request-Timestamp', '');
        $signature = (string) $request->header('X-Slack-Signature', '');

        if (! $this->verifier->isValid($rawBody, $timestamp, $signature)) {
            return response()->json(
                ['error' => 'Invalid Slack signature'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return $next($request);
    }
}
