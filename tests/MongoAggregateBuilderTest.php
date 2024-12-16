<?php

use MongoDB\Client;
use PHPUnit\Framework\Assert as A;

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
        try {
            $this->builder->addStages($stages);
        } catch (\MongoDB\Exception\InvalidArgumentException $iae) {
            $this->assertEquals("\$find is not a supported aggregate stage.", $iae->getMessage());
        }

        $this->assertEquals([], $this->builder->getPipeline());
    }

    public function testCanAddRawStages()
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
        $this->builder->addRawStages($stages);
        $this->assertEquals($stages, $this->builder->getPipeline());
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

    public function testChunksAsExpected(): void
    {
        /**
         * A dummy cursor returning a 3-item list.
         */
        $dummy_cursor = new class implements Iterator
        {
            function setTypeMap($map)
            {
            }

            public function __construct(array $data = [])
            {
                $this->data = [1, 2, 3];
                $this->position = 0;
            }

            public function current()
            {
                return $this->data[$this->position];
            }

            public function key()
            {
                return $this->position;
            }

            public function next()
            {
                ++$this->position;
            }

            public function rewind()
            {
                $this->position = 0;
            }

            public function valid()
            {
                return isset($this->data[$this->position]);
            }
        };

        // Return the dummy cursor from the Mongo search.
        $collection = $this->createMock(MongoDB\Collection::class);
        $collection->method('aggregate')->willReturnCallback(function ($params) use ($dummy_cursor) {
            return $dummy_cursor;
        });
        $client = $this->createMock(MongoDB\Client::class);
        $database = $this->createMock(MongoDB\Database::class);
        $database->method('selectCollection')->willReturn($collection);
        $client->method('selectDatabase')->willReturn($database);
        $connection = new Connection($client, "ahahahah", "nooooo");

        // Call chunk() on the search results with a callback we can spy on.
        $callback = Mockery::mock();
        $callback->shouldReceive('__invoke')
            ->twice();
        $callableMock = function (...$args) use ($callback) {
            A::assertInstanceOf(\Illuminate\Support\LazyCollection::class, $args[0]);
            A::assertLessThanOrEqual(2, $args[0]->count());
            return $callback->__invoke(...$args);
        };
        $builder = new Builder("breakfasts", $connection);
        $builder->chunk(2, $callableMock);
    }
}
