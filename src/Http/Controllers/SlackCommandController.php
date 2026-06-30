<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Http\Controllers;

use DrMaxis\Deploybot\Commands\CommandContext;
use DrMaxis\Deploybot\Commands\CommandDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlackCommandController
{
    public function __invoke(Request $request, CommandDispatcher $dispatcher): JsonResponse
    {
        $ctx = CommandContext::fromRequest($request);
        $response = $dispatcher->dispatch($ctx);

        return response()->json($response->toArray());
    }
}
