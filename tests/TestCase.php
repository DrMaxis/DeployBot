<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Tests;

use DrMaxis\Deploybot\DeploybotServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DeploybotServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('deploybot.slack.signing_secret', 'test-signing-secret');
        $app['config']->set('deploybot.slack.bot_token', 'xoxb-fake-token-for-tests');
        $app['config']->set('deploybot.slack.admin_user_ids', ['UADMIN1', 'UADMIN2']);
    }
}
