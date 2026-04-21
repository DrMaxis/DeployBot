<?php

declare(strict_types=1);

namespace Afria\Deploybot\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Entrypoint for Slack interactive-message webhooks (button clicks,
 * select-menu changes, modal submissions).
 *
 * Slack POSTs interactions as `application/x-www-form-urlencoded` with
 * a single `payload` field containing a JSON string. This controller
 * parses + validates the envelope, then returns 200 OK for now —
 * command-specific interaction handling arrives in H2/H4 alongside the
 * commands that need it.
 *
 * Having the route + controller scaffolded now means Slack app config
 * can set the interaction URL today; no re-approval round-trip once
 * interactive handlers start landing.
 */
final class SlackInteractionController
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string) $request->input('payload', '');
        if ($raw === '') {
            return response()->json(['error' => 'missing payload'], 400);
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        // Interaction dispatch (Block Kit actions, modal submits, etc.)
        // will be wired in Slice H2+. For now ack the receipt so Slack
        // doesn't flag the endpoint as broken during app setup.
        return response()->json([
            'ok' => true,
            'type' => (string) ($decoded['type'] ?? 'unknown'),
        ]);
    }
}
