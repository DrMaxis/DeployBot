<?php

declare(strict_types=1);

namespace Afria\Deploybot\Commands;

use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Routes a parsed `CommandContext` to the registered handler that matches
 * its first-token command name.
 *
 * Responsibilities:
 *
 *   1. **Token split.** `$ctx->args[0]` is the command name (e.g.
 *      `releases`); the remainder is forwarded as the handler's args.
 *
 *   2. **Name resolution.** Look up the name in `CommandRegistry`. If no
 *      match, fall back to the configured `default_command` (the
 *      built-in `help` ships with the package) so users always see
 *      something actionable rather than silence.
 *
 *   3. **Admin gate.** If the resolved command's `requiresAdmin()` is
 *      true, check `$ctx->userId` against the injected allowlist. A
 *      non-admin invoker gets an ephemeral "admin-only" response
 *      without the handler running at all.
 *
 *   4. **Container resolution + invocation.** `handle()` is called on a
 *      fresh container-resolved instance, so handlers can inject
 *      whatever the host app's container can provide.
 *
 *   5. **Failure capture.** Exceptions from `handle()` are caught and
 *      turned into an ephemeral error response so a buggy handler
 *      can't 500 the whole webhook + trigger Slack's retry storm.
 *
 * All of this is pure except container calls + `CommandRegistry`
 * lookups, so unit tests can exercise it with an in-memory registry and
 * a stub container.
 */
final class CommandDispatcher
{
    /**
     * @param  array<int, string>  $adminUserIds
     */
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

        /** @var CommandInterface $handler */
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
