<?php

namespace PartechGSS\MongoDB;

use \MongoDB\Client;

class Connection extends \Illuminate\Database\Connection
{
    /**
     * @var \MongoDB\Client
     */
    protected $client;

    /**
     * @var \MongoDB\Collection
     */
    protected $collection;
    protected $collection_name;

    /**
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * @var array
     */
    protected $typeMap;

    /**
     * Create a new instance of a MongoDB\Client connection.
     * @param Client $client
     * @param $database
     * @param $collectionName
     * @param array $config
     */
    public function __construct(Client $client, $database, $collectionName = '', array $config = [])
    {
        $this->client = $client;
        $this->collection_name = $collectionName;
        $this->config = $config;
        $this->database = $database;
        $this->typeMap = [];

        // Select database
        $this->db = $this->getMongoClient()->selectDatabase($database);
    }

    /**
     * @param string $name
     * @return \MongoDB\Collection
     */
    public function selectCollection($name = '')
    {
        $this->collection_name = $name ?: $this->collection_name;
        $this->collection = $this->db->selectCollection($this->collection_name);
        return $this->collection;
    }

    /**
     * @param array $typeMap - ['root' => 'array', 'document' => 'array', 'array' => 'array']
     * @return Connection
     */
    public function setTypeMap(array $typeMap = [])
    {
        // Nulls translate to 'default value'
        $newTypeMap = array_merge(['root' => null, 'document' => null, 'array' => null], $typeMap);
        $this->typeMap = $newTypeMap;

        return $this;
    }

    /**
     * @return array
     */
    public function getTypeMap()
    {
        return $this->typeMap;
    }

    /**
     * @return \MongoDB\Client
     */
    public function getMongoClient()
    {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    /**
     * @param array $aggregate - array with 'pipeline' and 'options' keys
     * @param array $bindings
     * @param bool $useReadOnly
     * @return \Generator|\Traversable
     */
    public function cursor($aggregate, $bindings = [], $useReadOnly = true)
    {
        $cursor = $this->collection->aggregate($aggregate['pipeline'], $aggregate['options']);
        $cursor->setTypeMap($this->getTypeMap());
        return $cursor;
    }

    /**
     * @param string $aggregate - JSON string with 'pipeline' and 'options' keys
     * @param array $bindings
     * @param bool $useReadOnly
     * @return array
     */
    public function select($aggregate, $bindings = [], $useReadOnly = true)
    {
        $cursor = $this->cursor($aggregate, $bindings, $useReadOnly);
        return $cursor->toArray();
    }
}
