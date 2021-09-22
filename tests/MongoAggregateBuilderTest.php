<?php

use MongoDB\Client;
use PartechGSS\MongoDB\Connection;
use PartechGSS\MongoDB\Aggregate\Builder;

class MongoAggregateBuilderTest extends TestCase
{
    public $builder;
    protected $client;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = config('database.connections.mongodb');
        $this->client = new Client($this->config['dsn'], (array)@$this->config['options'], (array)@$this->config['driver_options']);
        $this->builder = new Builder('device_events', new Connection($this->client, $this->config['database'], '', $this->config));
    }

    public function testCanUseModelConnection()
    {
        $bfast = PartechGSS\Tests\Models\Breakfast::make();
        $builder = new Builder($bfast->getTable(), new Connection($bfast->getConnection()->getMongoClient(), $this->config['database'], null, []));
        $this->assertNotEmpty($builder->getCollection());
    }

    public function testCanAddMatchAndProject()
    {
        $this->builder->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]]);
        $this->assertEquals(
            [
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
        $this->assertEquals(
            [
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

    public function testCanAddUnwindWithString()
    {
        $unwind = '$keys';
        $this->builder->unwind($unwind);
        $this->assertEquals(
            [['$unwind' => $unwind]],
            $this->builder->getPipeline()
        );
    }

    public function testCanAddUnwindWithArray()
    {
        $unwind = ['path' => '$keys', 'includeArrayIndex' => 'whatever_index', 'preserveNullAndEmptyArrays' => false];
        $this->builder->unwind($unwind);
        $this->assertEquals(
            [['$unwind' => $unwind]],
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
        $this->assertEquals($stages, $this->builder->getPipeline()
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

    public function testCanAddStage(): void
    {
        $stage = ["\$replaceRoot" => ["newRoot" => "\$doc"]];
        $this->builder->addStage($stage);
        $this->assertEquals($stage, $this->builder->getPipeline()[0]);
    }

    public function testToArray(): void
    {
        $this->builder
            ->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]])
            ->project(['_id' => false, 'asset_id' => true]);
        $this->assertIsArray($this->builder->toArray());
    }

    public function testCanFirstResultsCollection(): void
    {
        $this->builder->match(['organization_id' => 1, 'asset_id' => ['$in' => [4, 5, 6]]])->first();
        $pipeline = $this->builder->getPipeline();

        $this->assertEquals(
            ['$limit' => 1],
            $pipeline[count($pipeline) - 1]
        );
    }
}
