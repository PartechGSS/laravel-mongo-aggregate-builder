<?php

namespace PartechGSS\MongoDB\Aggregate;

use \Illuminate\Support\LazyCollection;
use \PartechGSS\MongoDB\Connection;
use \MongoDB\Collection;

class Builder
{
    const TYPE_MAP = ['root' => 'array', 'document' => 'array', 'array' => 'array'];
    protected Collection $collection;
    protected Connection $connection;
    protected array $pipeline;
    protected array $options;

    public function __construct(string $collection, Connection $connection)
    {
        $this->connection = $connection;
        $this->collection = $this->connection->selectCollection($collection);
        $this->pipeline = [];
        $this->options = [];
    }

    /**
     * @param $stage array
     * @return bool|string
     */
    private function validateStage(array $stage)
    {
        $name = trim(array_key_first($stage), "$");
        return !method_exists($this, $name) ? false : $name;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Export current builder aggregate stages
     * @return array
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * Return currently configured options that will be used during pipeline execution
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set option configuration to be used during pipeline execution
     * @param array $options - <code>['allowDiskUse' = boolean]<code>
     */
    public function setOptions($options = [])
    {
        $this->options = $options;
    }

    /**
     * @param array $stage - a valid PHP mongo pipeline stage
     * @return Builder
     */
    public function addStage(array $stage)
    {
        $this->pipeline[] = $stage;
        return $this;
    }

    /**
     * Concat an array of stages to the end of the existing pipeline,
     * will ignore any stages not supported by the builder
     * @param array $stages
     * @return Builder
     */
    public function addStages(Iterable $stages, bool $strict = true)
    {
        foreach ($stages as $stage) {
            if (false === $strict) {
                $this->addStage($stage);
                continue;
            }

            if (true === $strict && $name = $this->validateStage($stage)) {
                $this->{$name}($stage["$$name"]);
            }
        }

        return $this;
    }

    /**
     * @param array $stage - <code>[ $query_array ]</code>
     * @return Builder
     */
    public function match(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$match' => $stage];
        return $this;
    }

    /**
     * @param array $stage - <code>
     *  [
     *      $id => $expression, // Group By Expression
     *      $field1 => [ $accumulator1 => $expression ],
     *      ...
     *   ]
     * </code>
     * @return Builder
     */
    public function group(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$group' => $stage];
        return $this;
    }

    /**
     * @param array $stage - <code>[ $projection_specifications ]</code>
     * @return Builder
     */
    public function project(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$project' => $stage];
        return $this;
    }

    /**
     * @param array $stage - <code>
     *  [
     *      $newField> => $expression,
     *      ...
     *  ]
     * </code>
     * @return Builder
     */
    public function set(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$set' => $stage];
        return $this;
    }

    /**
     * @param string|array $stage
     *  as string - <code>
     *          $field_path</code>
     *  as array - <code>
     *      [
     *          'path' => $field_path,
     *          'includeArrayIndex' => $index_string,
     *          'preserveNullAndEmptyArrays' => boolean
     *      ]
     * </code>
     * @return Builder
     */
    public function unwind($stage): Builder
    {
        $this->pipeline[] = ['$unwind' => $stage];
        return $this;
    }

    // TODO: Other aggregate pipeline stages

    /**
     * @return LazyCollection
     */
    public function cursor()
    {
        return new LazyCollection(function () {
            yield from $this->connection
                ->setTypeMap(self::TYPE_MAP)
                ->cursor($this->toArray());
        });
    }

    /**
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = [])
    {
        if (!empty($columns)) {
            $projection = array_merge(['_id' => false], array_fill_keys($columns, true));
            $this->project($projection);
        }
        return collect(
            $this->connection
                ->setTypeMap(self::TYPE_MAP)
                ->select($this->toArray())
        );
    }

    /**
     * @param int $amount
     * @return Builder
     */
    public function limit(int $amount)
    {
        $this->pipeline[] = ["\$limit" => $amount];
        return $this;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function first($columns = [])
    {
        return $this->limit(1)->get($columns)->first();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return ['pipeline' => $this->getPipeline(), 'options' => $this->getOptions()];
    }

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
