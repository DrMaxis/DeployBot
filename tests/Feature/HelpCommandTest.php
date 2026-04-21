<?php

declare(strict_types=1);

use Afria\Deploybot\Commands\CommandContext;
use Afria\Deploybot\Commands\CommandInterface;
use Afria\Deploybot\Commands\CommandRegistry;
use Afria\Deploybot\Commands\CommandResponse;
use Afria\Deploybot\Commands\HelpCommand;

/**
 * HelpCommand uses the container-resolved CommandRegistry, so these
 * tests need a booted Laravel app (Orchestra) rather than a bare
 * Container. Still fast — no DB hit.
 */
final class HelpProbeCommand implements CommandInterface
{
    public static function name(): string
    {
        return 'probe';
    }

    public static function description(): string
    {
        return 'A test command that exists to be listed.';
    }

    public static function requiresAdmin(): bool
    {
        return false;
    }

    public function handle(CommandContext $ctx): CommandResponse
    {
        return CommandResponse::forUser('probed');
    }
}

it('renders a Block Kit list of registered commands', function (): void {
    /** @var CommandRegistry $registry */
    $registry = app(CommandRegistry::class);
    $registry->register(HelpProbeCommand::class);

    $help = app(HelpCommand::class);
    $response = $help->handle(new CommandContext(
        teamId: 'T1',
        teamDomain: 'afriatech',
        channelId: 'C1',
        channelName: 'general',
        userId: 'U1',
        userName: 'antwi',
        commandName: 'alverium',
        text: 'help',
        responseUrl: '',
        triggerId: '',
        args: [],
        raw: [],
    ));

    expect($response->responseType)->toBe('ephemeral');
    expect($response->text)->toContain('Available commands');
    expect($response->text)->toContain('help');
    expect($response->text)->toContain('probe');

    $blocks = $response->blocks;
    expect($blocks[0]['type'])->toBe('header');
    expect($blocks[1]['type'])->toBe('section');
    expect($blocks[1]['text']['text'])->toContain('`help`');
    expect($blocks[1]['text']['text'])->toContain('`probe`');
});

it('flags admin-only commands in the listing', function (): void {
    /** @var CommandRegistry $registry */
    $registry = app(CommandRegistry::class);
    // Clean any prior state from other tests.
    $registry->flush();
    $registry->register(HelpCommand::class);

    // Anonymous admin-only stub via an attribute-free anonymous class
    // doesn't play well with static interface methods, so use a simple
    // named class fixture.
    require_once __DIR__.'/_fixtures/HelpAdminFixtureCommand.php';
    $registry->register(HelpAdminFixtureCommand::class);

    $response = app(HelpCommand::class)->handle(new CommandContext(
        teamId: 'T',
        teamDomain: 'x',
        channelId: 'C',
        channelName: 'x',
        userId: 'U',
        userName: 'x',
        commandName: 'alverium',
        text: '',
        responseUrl: '',
        triggerId: '',
        args: [],
        raw: [],
    ));

    expect($response->blocks[1]['text']['text'])->toContain('_(admin)_');
});
