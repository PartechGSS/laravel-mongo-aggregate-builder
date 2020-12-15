<?php

class TestCase extends Orchestra\Testbench\TestCase
{
    /**
     * @inheritDoc
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = require 'config/database.php';

        $app['config']->set('app.key', 'NaRflEyUJ5FsKp9lMwv4tC4Nn0NQilM7');
        $app['config']->set('database.default', 'mongodb');
        $app['config']->set('database.connections.mongodb', $config['connections']['mongodb']);
    }

    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app)
    {
        return [\PartechGSS\MongoDB\ConnectionServiceProvider::class];
    }
}