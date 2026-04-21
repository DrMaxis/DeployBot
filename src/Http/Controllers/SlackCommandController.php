<?php

declare(strict_types=1);

namespace Afria\Deploybot\Http\Controllers;

use Afria\Deploybot\Commands\CommandContext;
use Afria\Deploybot\Commands\CommandDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Entrypoint for Slack slash-command webhooks.
 *
 * Wired by the service provider at `POST /{prefix}/slack/command`. The
 * preceding `VerifySlackSignature` middleware has already confirmed
 * the request is authentic — this controller can treat the payload as
 * trusted.
 *
 * Slack expects a response within ~3 seconds. For anything slower,
 * commands should return immediately with a "working on it" ephemeral
 * response and dispatch a queued job that posts the real result via
 * the `response_url` from `CommandContext`.
 */
final class SlackCommandController
{
    public function __invoke(Request $request, CommandDispatcher $dispatcher): JsonResponse
    {
        $ctx = CommandContext::fromRequest($request);
        $response = $dispatcher->dispatch($ctx);

        return response()->json($response->toArray());
    }
}
