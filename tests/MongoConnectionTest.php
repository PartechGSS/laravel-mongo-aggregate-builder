<?php

use MongoDB\Client;
use PartechGSS\MongoDB\Aggregate\Builder;
use PHPUnit\Framework\Assert as A;
use PartechGSS\MongoDB\Connection;

class MongoConnectionTest extends TestCase
{
    private $dummy_cursor;
    private $client;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = config('database.connections.mongodb');
        $this->client = new Client($this->config['dsn'], (array)@$this->config['options'], (array)@$this->config['driver_options']);
        $this->dummy_cursor = new class
        {
            function setTypeMap($map)
            {
            }
            function toArray()
            {
                return [];
            }
        };
    }

    public function testCanGetCursorForAggregateJson()
    {
        $connection = new Connection($this->client, $this->config['database']);
        $connection->selectCollection('snapshots');
        $aggregate = ['pipeline' => [['$match' => ['asset_id' => 1]]], 'options' => []];
        $cursor = $connection->cursor($aggregate);
        $this->assertInstanceOf(\MongoDB\Driver\Cursor::class, $cursor);
    }

    public function testCanGetSelectForAggregateJson()
    {
        $connection = new Connection($this->client, $this->config['database']);
        $connection->selectCollection('device_events');
        $aggregate = ['pipeline' => [['$match' => ['asset_id' => 1]]], 'options' => []];
        $array = $connection->select($aggregate);
        $this->assertIsArray($array);
    }

    public function testMongoTypesArePreserved(): void
    {
        $collection = $this->createMock(MongoDB\Collection::class);
        $collection->method('aggregate')->willReturnCallback(function ($params) {
            A::assertInstanceOf(
                MongoDB\BSON\UTCDateTime::class,
                $params[0]['$match']['date']
            );

            return $this->dummy_cursor;
        });
        $client = $this->createMock(MongoDB\Client::class);
        $database = $this->createMock(MongoDB\Database::class);
        $database->method('selectCollection')->willReturn($collection);
        $client->method('selectDatabase')->willReturn($database);
        $connection = new Connection($client, "ahahahah", "nooooo");
        $builder = new Builder("breakfasts", $connection);
        $builder->match([
            'date' => new MongoDB\BSON\UTCDateTime(strtotime("now") * 1000)
        ])->get();
    }
}
