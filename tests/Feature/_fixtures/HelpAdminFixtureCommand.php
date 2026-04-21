<?php

declare(strict_types=1);

use Afria\Deploybot\Commands\CommandContext;
use Afria\Deploybot\Commands\CommandInterface;
use Afria\Deploybot\Commands\CommandResponse;

/**
 * Admin-only fixture for the help command's admin-flag rendering test.
 * Kept in a `_fixtures/` folder (prefix excludes it from the test loader)
 * so it's not picked up as a test case itself.
 */
final class HelpAdminFixtureCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'secret-admin-thing';
    }

    public static function description(): string
    {
        return 'Only admins should run this.';
    }

    public static function requiresAdmin(): bool
    {
        return true;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser('secret');
    }
}
