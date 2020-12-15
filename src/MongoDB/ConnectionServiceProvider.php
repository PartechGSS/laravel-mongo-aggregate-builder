<?php

namespace PartechGSS\MongoDB;

class ConnectionServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('mongodb', function ($config, $name) {
                return new Connection(new \MongoDB\Client($config['dsn'], (array)@$config['options'],(array)@$config['driver_options']), $config['database'], @$config['collection'], $config);
            });
        });

    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app['db']);
    }
}