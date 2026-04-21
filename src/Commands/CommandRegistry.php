<?php

declare(strict_types=1);

namespace Afria\Deploybot\Commands;

use InvalidArgumentException;

/**
 * Host-app-visible registry of `CommandInterface` implementations.
 *
 * Registry is a singleton (bound by the service provider). Host apps
 * register their commands from their own service provider's `boot()`:
 *
 * ```php
 * public function boot(): void
 * {
 *     app(CommandRegistry::class)->register(ReleasesCommand::class);
 *     app(CommandRegistry::class)->register(StatusCommand::class);
 * }
 * ```
 *
 * Registration by class name (not instance) lets the Laravel container
 * resolve constructor dependencies at invocation time — handlers can
 * inject repositories, clients, etc. via standard constructor
 * injection.
 *
 * Name collisions are a hard error. Overriding a command — e.g. replacing
 * the built-in `help` with a host-app-specific variant — is explicit via
 * `override()`.
 */
final class CommandRegistry
{
    /** @var array<string, class-string<CommandInterface>> */
    private array $commands = [];

    /**
     * Register a new command class.
     *
     * @param  class-string<CommandInterface>  $class
     *
     * @throws InvalidArgumentException When a command with the same name
     *                                  is already registered. Use
     *                                  `override()` to replace.
     */
    public function register(string $class): void
    {
        $this->assertIsCommand($class);
        $name = $this->extractName($class);

        if (isset($this->commands[$name])) {
            throw new InvalidArgumentException(
                "Command `{$name}` is already registered (".$this->commands[$name].
                '). Use `override()` to replace it.',
            );
        }

        $this->commands[$name] = $class;
    }

    /**
     * Replace an existing command registration. No-ops if nothing was
     * registered under the name.
     *
     * @param  class-string<CommandInterface>  $class
     */
    public function override(string $class): void
    {
        $this->assertIsCommand($class);
        $this->commands[$this->extractName($class)] = $class;
    }

    /**
     * Look up a command class by name.
     *
     * @return class-string<CommandInterface>|null
     */
    public function get(string $name): ?string
    {
        return $this->commands[strtolower($name)] ?? null;
    }

    /**
     * Remove a command. Primarily for tests.
     */
    public function unregister(string $name): void
    {
        unset($this->commands[strtolower($name)]);
    }

    /**
     * All registered commands, keyed by name.
     *
     * @return array<string, class-string<CommandInterface>>
     */
    public function all(): array
    {
        return $this->commands;
    }

    /**
     * Reset the registry — tests only.
     */
    public function flush(): void
    {
        $this->commands = [];
    }

    /** @param  class-string<CommandInterface>  $class */
    private function assertIsCommand(string $class): void
    {
        if (! is_subclass_of($class, CommandInterface::class)) {
            throw new InvalidArgumentException(
                "Class `{$class}` must implement ".CommandInterface::class,
            );
        }
    }

    /** @param  class-string<CommandInterface>  $class */
    private function extractName(string $class): string
    {
        return strtolower($class::name());
    }
}
