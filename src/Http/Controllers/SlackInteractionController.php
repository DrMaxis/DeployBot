<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlackInteractionController
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string) $request->input('payload', '');
        if ($raw === '') {
            return response()->json(['error' => 'missing payload'], 400);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        return response()->json([
            'ok' => true,
            'type' => (string) ($decoded['type'] ?? 'unknown'),
        ]);
    }
}
