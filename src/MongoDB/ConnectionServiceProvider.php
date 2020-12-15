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
                $config['name'] = $name;
                return new Connection($config);
            });
        });

    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        \Illumniate\Database\Eloquent\Model::setConnectionResolver($this->app['db']);
    }
}