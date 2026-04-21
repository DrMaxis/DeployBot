<?php

declare(strict_types=1);

namespace Afria\Deploybot\Commands;

/**
 * Contract for a slash-command handler.
 *
 * Host apps implement this once per command they expose. Implementations
 * are registered with `CommandRegistry::register(MyCommand::class)`,
 * resolved via the Laravel container on dispatch, and called with a
 * ready-to-use `CommandContext`.
 *
 * ## Static metadata, instance `handle()`
 *
 * `name()`, `description()`, and `requiresAdmin()` are static because the
 * registry needs to introspect them without instantiating (handlers may
 * have constructor dependencies that the container resolves only when
 * the command actually runs). `handle()` is an instance method so it
 * can use those dependencies.
 *
 * ## Runtime budget
 *
 * Handlers should be fast (< 3 seconds — Slack's HTTP timeout). Anything
 * slower should respond immediately with an ephemeral "working on it"
 * message and dispatch a Laravel queued job that posts the real result
 * back via the `response_url` carried in `CommandContext`.
 *
 * @see CommandContext
 * @see CommandResponse
 */
interface CommandInterface
{
    /**
     * The subcommand identifier — the FIRST token of the user's text.
     *
     * Example: for `/alverium releases staging`, `name()` returns
     * `'releases'`. Names must be lowercase; the dispatcher lowercases
     * input before matching.
     */
    public static function name(): string;

    /**
     * One-line human description shown by the built-in `help` command.
     */
    public static function description(): string;

    /**
     * Whether the command is restricted to the configured admin
     * allowlist (`deploybot.slack.admin_user_ids`).
     *
     * When true and the invoker is not on the list, the dispatcher
     * short-circuits and returns an ephemeral "command is admin-only"
     * response without invoking `handle()`.
     */
    public static function requiresAdmin(): bool;

    /**
     * Run the command and return the Slack response envelope.
     *
     * `$ctx->args` excludes the command name itself — a handler for the
     * `releases` command invoked as `/alverium releases staging` receives
     * `['staging']` as `$ctx->args`.
     */
    public function handle(CommandContext $ctx): CommandResponse;
}
