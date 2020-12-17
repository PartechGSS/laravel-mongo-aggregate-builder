<?php

namespace PartechGSS\MongoDB;

class ConnectionServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        // Add database driver
        $this->app->resolving('db', function ($db) {
            $db->extend('mongodb', function ($config) {
                if (empty($config['database']) && !empty($config['dsn'])) {
                    if (preg_match('/^mongodb(?:[+]srv)?:\\/\\/.+\\/([^?&]+)/s', $config['dsn'], $matches)) {
                        $config['database'] = $matches[1];
                    } else {
                        throw new \MongoDB\Driver\Exception\InvalidArgumentException("database.mongodb is not properly configured.");
                    }
                }
                return new Connection(new \MongoDB\Client($config['dsn'], (array)@$config['options'], (array)@$config['driver_options']), @$config['database'], @$config['collection'], $config);
            });
        });

    }

    public function boot()
    {
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app['db']);
    }
}