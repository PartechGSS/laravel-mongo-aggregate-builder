<?php

namespace PartechGSS\Tests;

use \MongoDB\Client;
use \PartechGSS\Mongo\Connection;
use \PartechGSS\Mongo\Aggregate\Builder;

class MongoAggregateBuilderTest extends TestCase
{
    public $builder;
    protected $client;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = config('database.connections.mongodb');
        $this->client = new Client($this->config['dsn'], (array)@$this->config['options'],(array)@$this->config['driver_options']);
        $this->builder = new Builder('device_events', new Connection($this->client, $this->config['database'], '', $this->config));
    }

    public function testCanAddMatchAndProject()
    {
        $this->builder->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]]);
        $this->assertEquals([
            [
                '$match' => [
                    'organization_id' => 1,
                    'asset_id' => [
                        '$in' => [
                            4,
                            5,
                            6,
                        ],
                    ],
                ]
            ]
        ],
            $this->builder->getPipeline()
        );

        $this->builder->project(['_id' => false, 'asset_id' => true]);
        $this->assertEquals([
            [
                '$match' => [
                    'organization_id' => 1,
                    'asset_id' => [
                        '$in' => [
                            4,
                            5,
                            6,
                        ],
                    ],
                ]
            ],
            [
                '$project' => [
                    '_id' => false,
                    'asset_id' => true,
                ]
            ]
        ],
            $this->builder->getPipeline()
        );
    }

    public function testCanAddStages()
    {
        $stages = [
            [
                '$match' => [
                    'organization_id' => 1,
                    'asset_id' => [
                        '$in' => [
                            4,
                            5,
                            6,
                        ],
                    ],
                ]
            ],
            ['$find' => 'is not a legal aggregate pipeline stage'],
            [
                '$project' => [
                    '_id' => false,
                    'asset_id' => true,
                ]
            ]
        ];
        $this->builder->addStages($stages);
        $this->assertEquals([
            [
                '$match' => [
                    'organization_id' => 1,
                    'asset_id' => [
                        '$in' => [
                            4,
                            5,
                            6,
                        ],
                    ],
                ]
            ],
            [
                '$project' => [
                    '_id' => false,
                    'asset_id' => true,
                ]
            ]
        ],
            $this->builder->getPipeline()
        );
    }

    public function testCanSetAndGetOptions()
    {
        $options = ['waffles' => 'blueberry', 'pancakes' => 'buttermilk'];
        $this->assertEmpty($this->builder->getOptions());
        $this->builder->setOptions($options);
        $this->assertEquals($options, $this->builder->getOptions());
    }

    public function testToJson()
    {
        $this->builder
            ->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]])
            ->project(['_id' => false, 'asset_id' => true]);
        $this->assertJson($this->builder->toJson());
    }

    // tests ->get()
    public function testCanGetResultsCollection()
    {
        $this->builder->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $this->builder->get());
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $this->builder->get(['hats', 'coats']));
        $this->assertEquals(['$project' => ['_id' => false, 'hats' => true, 'coats' => true]], $this->builder->getPipeline()[1]);
    }

    // tests ->cursor()
    public function testCanGetResultsLazyCollection()
    {
        $this->builder->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]])
            ->project(['_id' => false, 'hats']);

        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $this->builder->cursor());
    }
}
