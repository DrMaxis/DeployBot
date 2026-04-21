<?php

declare(strict_types=1);

use Afria\Deploybot\Tests\TestCase;

/*
|-------------------------------------------------------------------------------
| Pest bootstrap
|-------------------------------------------------------------------------------
|
| Pest picks up `TestCase` as the base for every test under `tests/Feature`
| (which is where container + migration access matters). `tests/Unit`
| stays on plain PHPUnit TestCase — pure-function tests don't need a
| Laravel app booted.
*/

uses(TestCase::class)->in('Feature');
