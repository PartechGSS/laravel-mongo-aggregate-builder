<?php


namespace PartechGSS\Traits;

use \Illuminate\Support\Arr;
use \MongoDB\Client;
use \PartechGSS\Mongo\Aggregate\Builder as MongoAggregateBuilder;
use \PartechGSS\Mongo\Connection;
use \InvalidArgumentException;

trait AggregateBuilder
{
    abstract function getTable();

    abstract function getConnectionName();

    abstract function getConnection();

    /**
     * @return MongoAggregateBuilder
     */
    public function newAggregateBuilder(): MongoAggregateBuilder
    {
        $config = config('database.connections.' . $this->getConnectionName());

        // Build the connection string
        $dsn = Arr::get($config, 'dsn', $this->getDsn($config));

        // You can pass options directly to the MongoDB constructor
        $options = Arr::get($config, 'options', []);

        $database = Arr::get($config, 'database', '');
        if (!$database) {
            $dsn = isset($config['dsn']) ? $config['dsn'] : '';
            if (preg_match('/^mongodb(?:[+]srv)?:\\/\\/.+\\/([^?&]+)/s', $dsn, $matches)) {
                $database = $matches[1];
            } else {
                throw new InvalidArgumentException("Database connection is not properly configured.");
            }
        }

        $connection = method_exists($this->getConnection(), 'getMongoClient') ? $this->getConnection()->getMongoClient() : $this->connectToMongoDb($dsn, $config, $options);

        return new MongoAggregateBuilder($this->getTable(), new Connection($connection, $database, $this->getTable(), $config));
    }

    /**
     * @param $config
     * @return string
     */
    protected function getDsn($config)
    {
        if (!empty($config['dsn'])) {
            $dsn = $config['dsn'];
        } else { // Generate it
            $hosts = empty($config['host']) ? [] : (is_array($config['host']) ? $config['host'] : [$config['host']]);
            // Add configured port if host doesn't have one specified
            if (!empty($config['port'])) {
                foreach ($hosts as &$host) {
                    if (strpos($host, ':') === false) {
                        $host = $host . ':' . $config['port'];
                    }
                }
            }

            // Authenticate?
            $auth = !empty($config['options']['database']) ? $config['options']['database'] : null;
            $dsn = 'mongodb://' . implode(',', $hosts) . ($auth ? '/' . $auth : '');
        }

        return $dsn;
    }

    /**
     * Create a new MongoDB connection.
     * @param string $dsn
     * @param array $config
     * @param array $options
     *
     * @return \MongoDB\Client
     */
    protected function connectToMongoDb($dsn, array $config = [], array $options = [])
    {
        $driverOptions = [];

        if (isset($config['driver_options']) && is_array($config['driver_options'])) {
            $driverOptions = $config['driver_options'];
        }

        // Check if the credentials are not already set in the options
        if (!isset($options['username']) && !empty($config['username'])) {
            $options['username'] = $config['username'];
        }
        if (!isset($options['password']) && !empty($config['password'])) {
            $options['password'] = $config['password'];
        }

        return new Client($dsn, $options, $driverOptions);
    }
}
