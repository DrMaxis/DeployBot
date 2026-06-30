<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Commands;

use Illuminate\Contracts\Container\Container;
use Throwable;

class CommandDispatcher
{
    public function __construct(
        private readonly CommandRegistry $registry,
        private readonly Container $container,
        private readonly string $defaultCommandName,
        private readonly array $adminUserIds,
    ) {}

    public function dispatch(CommandContext $ctx): CommandResponse
    {
        $args = $ctx->args;
        $name = array_shift($args);

        $class = $name !== null ? $this->registry->get($name) : null;
        $class ??= $this->registry->get($this->defaultCommandName);

        if ($class === null) {
            return CommandResponse::forUser(
                "Sorry, no command matched `{$name}` and the default ".
                "command `{$this->defaultCommandName}` is not registered. ".
                'This is a misconfiguration — please contact an admin.',
            );
        }

        if ($class::requiresAdmin() && ! $this->isAdmin($ctx->userId)) {
            return CommandResponse::forUser(
                "`/{$ctx->commandName} {$class::name()}` is admin-only.",
            );
        }

        $handler = $this->container->make($class);

        try {
            return $handler->handle($ctx->withArgs($args));
        } catch (Throwable $e) {
            return CommandResponse::forUser(
                "Something went wrong running that command: `{$e->getMessage()}`. ".
                'An admin can check the application logs for the full trace.',
            );
        }
    }

    private function isAdmin(string $userId): bool
    {
        return $userId !== '' && in_array($userId, $this->adminUserIds, true);
    }
}
