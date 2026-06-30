<?php

declare(strict_types=1);

use DrMaxis\Deploybot\Commands\CommandContext;
use DrMaxis\Deploybot\Commands\CommandInterface;
use DrMaxis\Deploybot\Commands\CommandRegistry;
use DrMaxis\Deploybot\Commands\CommandResponse;
use DrMaxis\Deploybot\Commands\HelpCommand;

class HelpProbeCommand implements CommandInterface
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
    $registry = resolve(CommandRegistry::class);
    $registry->register(HelpProbeCommand::class);

    $help = resolve(HelpCommand::class);
    $response = $help->handle(new CommandContext(
        teamId: 'T1',
        teamDomain: 'afriatech',
        channelId: 'C1',
        channelName: 'general',
        userId: 'U1',
        userName: 'antwi',
        commandName: 'mybot',
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
    $registry = resolve(CommandRegistry::class);
    $registry->flush();
    $registry->register(HelpCommand::class);

    require_once __DIR__.'/_fixtures/HelpAdminFixtureCommand.php';
    $registry->register(HelpAdminFixtureCommand::class);

    $response = resolve(HelpCommand::class)->handle(new CommandContext(
        teamId: 'T',
        teamDomain: 'x',
        channelId: 'C',
        channelName: 'x',
        userId: 'U',
        userName: 'x',
        commandName: 'mybot',
        text: '',
        responseUrl: '',
        triggerId: '',
        args: [],
        raw: [],
    ));

    expect($response->blocks[1]['text']['text'])->toContain('_(admin)_');
});
