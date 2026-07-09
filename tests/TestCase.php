<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * HARD SAFETY GUARD — do not remove.
     *
     * The desktop launcher (cli.js) runs `php artisan optimize`, which writes
     * bootstrap/cache/config.php with the REAL environment (env=local, the
     * real MySQL/SQLite connection). A cached config makes Laravel SKIP
     * phpunit.xml's <env> overrides, so tests silently run against the real
     * database — and RefreshDatabase then WIPES it (this happened once:
     * migrate:fresh emptied the production MySQL DB).
     *
     * This override detects the leak, purges the bootstrap caches, reboots the
     * app, and refuses to run tests at all if the environment still isn't
     * 'testing' or the database isn't the throwaway one from phpunit.xml.
     */
    public function createApplication(): Application
    {
        $app = $this->bootFreshApplication();

        if (!$app->environment('testing')) {
            // Cached config leaked in — purge every bootstrap cache and reboot.
            foreach (glob($app->bootstrapPath('cache') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $cached) {
                @unlink($cached);
            }
            $app = $this->bootFreshApplication();
        }

        if (!$app->environment('testing')) {
            throw new \RuntimeException(
                'REFUSING TO RUN TESTS: environment is "' . $app->environment() . '", not "testing". '
                . 'Run `php artisan optimize:clear` first — a cached config would let tests wipe the REAL database.'
            );
        }

        $dbName = (string) $app['config']->get('database.connections.' . $app['config']->get('database.default') . '.database');
        if ($dbName !== ':memory:' && !str_contains($dbName, 'test')) {
            throw new \RuntimeException(
                "REFUSING TO RUN TESTS: database \"{$dbName}\" does not look like a test database. "
                . 'Tests must run on :memory: (see phpunit.xml) so they can never destroy real data.'
            );
        }

        return $app;
    }

    private function bootFreshApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
