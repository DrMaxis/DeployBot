<?php

declare(strict_types=1);

namespace Afria\Deploybot\Tests;

use Afria\Deploybot\DeploybotServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Base test case for feature tests that need a booted Laravel app +
 * the deploybot service provider loaded.
 *
 * Uses orchestra/testbench to spin up an in-memory Laravel instance.
 * SQLite `:memory:` for migrations; every test gets a fresh schema.
 */
abstract class TestCase extends OrchestraTestCase
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            DeploybotServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // SQLite in-memory for future DB-touching tests. Requires
        // `pdo_sqlite`; tests that skip DB work (the current batch)
        // never open a connection.
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Stable fake signing secret so signature tests are
        // deterministic. Tests that exercise the verifier with other
        // values can override via `config()` at the top of the test.
        $app['config']->set('deploybot.slack.signing_secret', 'test-signing-secret');
        $app['config']->set('deploybot.slack.bot_token', 'xoxb-fake-token-for-tests');
        $app['config']->set('deploybot.slack.admin_user_ids', ['UADMIN1', 'UADMIN2']);
    }

    // Migrations are NOT auto-run: tests that need the
    // `deploybot_channel_subscriptions` table should `use RefreshDatabase`
    // and add `$this->loadMigrationsFrom(...)` explicitly, which keeps
    // the happy-path tests free of a PDO dependency.
}
