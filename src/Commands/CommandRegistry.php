<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Commands;

use InvalidArgumentException;

class CommandRegistry
{
    private array $commands = [];

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

    public function override(string $class): void
    {
        $this->assertIsCommand($class);
        $this->commands[$this->extractName($class)] = $class;
    }

    
    public function registerDefault(string $class): void
    {
        $this->assertIsCommand($class);
        $name = $this->extractName($class);

        if (isset($this->commands[$name])) {
            return;
        }

        $this->commands[$name] = $class;
    }

    public function get(string $name): ?string
    {
        return $this->commands[strtolower($name)] ?? null;
    }

    public function unregister(string $name): void
    {
        unset($this->commands[strtolower($name)]);
    }

    public function all(): array
    {
        return $this->commands;
    }

    public function flush(): void
    {
        $this->commands = [];
    }

    private function assertIsCommand(string $class): void
    {
        if (! is_subclass_of($class, CommandInterface::class)) {
            throw new InvalidArgumentException(
                "Class `{$class}` must implement ".CommandInterface::class,
            );
        }
    }

    private function extractName(string $class): string
    {
        return strtolower($class::name());
    }
}
