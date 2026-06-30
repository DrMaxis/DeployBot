<?php

declare(strict_types=1);

use DrMaxis\Deploybot\Commands\CommandContext;
use DrMaxis\Deploybot\Commands\CommandDispatcher;
use DrMaxis\Deploybot\Commands\CommandInterface;
use DrMaxis\Deploybot\Commands\CommandRegistry;
use DrMaxis\Deploybot\Commands\CommandResponse;
use Illuminate\Container\Container;

class DispatchEchoCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'echo';
    }

    public static function description(): string
    {
        return 'Echoes its args.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser(implode(' ', $ctx->args));
    }
}

class DispatchAdminOnlyCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'locked';
    }

    public static function description(): string
    {
        return 'Admin-only.';
    }

    public static function requiresAdmin(): bool
    {
        return true;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser('unlocked');
    }
}

class DispatchBoomCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'boom';
    }

    public static function description(): string
    {
        return 'Always throws.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        throw new RuntimeException('kaboom');
    }
}

class DispatchHelpStubCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'help';
    }

    public static function description(): string
    {
        return 'Help stub for tests.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser('help!');
    }
}

function makeCtx(array $args = [], string $userId = 'UREGULAR'): CommandContext
{
    return new CommandContext(
        teamId: 'T1',
        teamDomain: 'afriatech',
        channelId: 'C1',
        channelName: 'general',
        userId: $userId,
        userName: 'antwi',
        commandName: 'mybot',
        text: implode(' ', $args),
        responseUrl: 'https://hooks.slack.com/…',
        triggerId: 'trig1',
        args: $args,
        raw: [],
    );
}

function makeDispatcher(array $adminIds = ['UADMIN1']): CommandDispatcher
{
    $registry = new CommandRegistry;
    $registry->register(DispatchEchoCommand::class);
    $registry->register(DispatchAdminOnlyCommand::class);
    $registry->register(DispatchBoomCommand::class);
    $registry->register(DispatchHelpStubCommand::class);

    return new CommandDispatcher(
        registry: $registry,
        container: new Container,
        defaultCommandName: 'help',
        adminUserIds: $adminIds,
    );
}

it('routes the first arg token to the matching command', function (): void {
    $dispatcher = makeDispatcher();
    $response = $dispatcher->dispatch(makeCtx(['echo', 'hello', 'world']));

    expect($response->text)->toBe('hello world');
});

it('falls back to the default command when no match', function (): void {
    $dispatcher = makeDispatcher();
    $response = $dispatcher->dispatch(makeCtx(['nosuch', 'nope']));

    expect($response->text)->toBe('help!');
});

it('falls back to the default command on empty input', function (): void {
    $dispatcher = makeDispatcher();
    $response = $dispatcher->dispatch(makeCtx([]));

    expect($response->text)->toBe('help!');
});

it('denies an admin-only command for a non-admin user', function (): void {
    $dispatcher = makeDispatcher(adminIds: ['UADMIN1']);
    $response = $dispatcher->dispatch(makeCtx(['locked'], userId: 'UREGULAR'));

    expect($response->responseType)->toBe('ephemeral');
    expect($response->text)->toContain('admin-only');
});

it('allows an admin-only command for an allowlisted user', function (): void {
    $dispatcher = makeDispatcher(adminIds: ['UADMIN1']);
    $response = $dispatcher->dispatch(makeCtx(['locked'], userId: 'UADMIN1'));

    expect($response->text)->toBe('unlocked');
});

it('captures handler exceptions instead of 500-ing the webhook', function (): void {
    $dispatcher = makeDispatcher();
    $response = $dispatcher->dispatch(makeCtx(['boom']));

    expect($response->responseType)->toBe('ephemeral');
    expect($response->text)->toContain('kaboom');
    expect($response->text)->toContain('Something went wrong');
});

it('strips the command name from args passed to the handler', function (): void {
    $dispatcher = makeDispatcher();
    $response = $dispatcher->dispatch(makeCtx(['echo', 'left', 'right']));

    expect($response->text)->toBe('left right');
});
