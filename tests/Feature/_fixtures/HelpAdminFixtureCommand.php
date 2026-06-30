<?php

declare(strict_types=1);

use DrMaxis\Deploybot\Commands\CommandContext;
use DrMaxis\Deploybot\Commands\CommandInterface;
use DrMaxis\Deploybot\Commands\CommandResponse;


class HelpAdminFixtureCommand implements CommandInterface
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
