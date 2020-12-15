<?php

namespace PartechGSS\Tests;

use \MongoDB\Client;
use \PartechGSS\MongoDB\Connection;

class MongoConnectionTest extends TestCase
{
    private $client;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = config('database.connections.mongodb');
        $this->client = new Client($this->config['dsn'], (array)@$this->config['options'],(array)@$this->config['driver_options']);
    }

    public function testCanGetCursorForAggregateJson()
    {
        $connection = new Connection($this->client, $this->config['database']);
        $connection->selectCollection('snapshots');
        $aggregate = ['pipeline' => [['$match' => ['asset_id' => 1]]]];
        $cursor = $connection->cursor(json_encode($aggregate));
        $this->assertInstanceOf(\MongoDB\Driver\Cursor::class, $cursor);
    }

    public function testCanGetSelectForAggregateJson()
    {
        $connection = new Connection($this->client, $this->config['database']);
        $connection->selectCollection('device_events');
        $aggregate = ['pipeline' => [['$match' => ['asset_id' => 1]]]];
        $array = $connection->select(json_encode($aggregate));
        $this->assertIsArray($array);
    }
}
