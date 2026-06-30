<?php

declare(strict_types=1);

use DrMaxis\Deploybot\Commands\CommandContext;
use DrMaxis\Deploybot\Commands\CommandInterface;
use DrMaxis\Deploybot\Commands\CommandRegistry;
use DrMaxis\Deploybot\Commands\CommandResponse;

class FakeAlphaCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'alpha';
    }

    public static function description(): string
    {
        return 'Alpha command.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser('alpha ran');
    }
}

class FakeAlphaReplacementCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'alpha';
    }

    public static function description(): string
    {
        return 'Alpha replacement.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser('replacement alpha ran');
    }
}

class NotACommand {}

it('registers a command and looks it up by name', function (): void {
    $registry = new CommandRegistry;
    $registry->register(FakeAlphaCommand::class);

    expect($registry->get('alpha'))->toBe(FakeAlphaCommand::class);
});

it('is case-insensitive on lookup', function (): void {
    $registry = new CommandRegistry;
    $registry->register(FakeAlphaCommand::class);

    expect($registry->get('ALPHA'))->toBe(FakeAlphaCommand::class);
    expect($registry->get('Alpha'))->toBe(FakeAlphaCommand::class);
});

it('returns null for unregistered commands', function (): void {
    $registry = new CommandRegistry;

    expect($registry->get('nope'))->toBeNull();
});

it('rejects double registration — collisions are explicit', function (): void {
    $registry = new CommandRegistry;
    $registry->register(FakeAlphaCommand::class);

    expect(fn () => $registry->register(FakeAlphaReplacementCommand::class))
        ->toThrow(InvalidArgumentException::class, 'already registered');
});

it('allows explicit override to replace a registration', function (): void {
    $registry = new CommandRegistry;
    $registry->register(FakeAlphaCommand::class);
    $registry->override(FakeAlphaReplacementCommand::class);

    expect($registry->get('alpha'))->toBe(FakeAlphaReplacementCommand::class);
});

it('rejects registering a class that does not implement CommandInterface', function (): void {
    $registry = new CommandRegistry;

    expect(fn () => $registry->register(NotACommand::class))
        ->toThrow(InvalidArgumentException::class, 'must implement');
});
