<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Http\Middleware;

use Closure;
use DrMaxis\Deploybot\Slack\SlackSignatureVerifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySlackSignature
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
