<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Stop the suite early when it is pointed at anything other than the
     * dedicated "_test" database, so RefreshDatabase can never wipe
     * development data.
     */
    protected function setUp(): void
    {
        $database = getenv('DB_DATABASE');

        if (is_string($database) && $database !== '' && !str_ends_with($database, '_test')) {
            $this->fail(sprintf(
                'Test stopped: DB_DATABASE is "%s", which does not end with "_test". Tests must run against the dedicated test database (see phpunit.xml), otherwise RefreshDatabase would destroy your development data. Check for DB_DATABASE in your shell environment or a cached configuration (php artisan config:clear).',
                $database
            ));
        }

        parent::setUp();
    }
}
