<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Commands;

interface CommandInterface
{
    public static function name(): string;

    public static function description(): string;

    public static function requiresAdmin(): bool;

    public function handle(CommandContext $ctx): CommandResponse;
}
