<?php

namespace PartechGSS\MongoDB\Aggregate;

use \Illuminate\Support\LazyCollection;
use \PartechGSS\MongoDB\Connection;
use \MongoDB\Collection;

class Builder
{
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
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
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
     * @param array $options - ['allowDiskUse' = true]
     */
    public function setOptions($options = [])
    {
        $this->options = $options;
    }

    /**
     * Concat an array of stages to the end of the existing pipeline,
     * will ignore any stages not supported by the builder
     * @param array $stages
     */
    public function addStages(Iterable $stages)
    {
        // We don't want to array_merge, we want to go through and add each stage per our builder. We don't know where these came from.
        foreach ($stages as $stage) {
            if (!empty($stage)) {
                $stageName = str_replace('$', '', array_keys($stage)[0]);
                if (method_exists($this, $stageName)) {
                    $this->{$stageName}($stage["\${$stageName}"]);
                }
            }
        }
    }

    /**
     * @param array $stage - [ $queryArray ]
     * @return Builder
     */
    public function match(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$match' => $stage];
        return $this;
    }

    /**
     * @param array $stage -
     *  [
     *      $id => $expression, // Group By Expression
     *      $field1 => [ $accumulator1 => $expression ],
     *      ...
     *   ]
     * @return Builder
     */
    public function group(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$group' => $stage];
        return $this;
    }

    /**
     * @param array $stage - [ $specifications ]
     * @return Builder
     */
    public function project(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$project' => $stage];
        return $this;
    }

    /**
     * @param array $stage -
     *  [
     *      $newField> => $expression,
     *      ...
     *  ]
     * @return Builder
     */
    public function set(Iterable $stage): Builder
    {
        $this->pipeline[] = ['$set' => $stage];
        return $this;
    }

    /**
     * @param string|array $stage
     *  string -
     *      $field_path
     *  array -
     *      [
     *          'path' => $field_path,
     *          'includeArrayIndex' => $index_string,
     *          'preserveNullAndEmptyArrays' => boolean
     *      ]
     *
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
                ->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array'])
                ->cursor($this->toJson());
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
        return collect($this->connection
            ->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array'])
            ->select($this->toJson())
        );
    }

    // todo: public function first() to skip ->get()->first()

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode(['pipeline' => $this->getPipeline(), 'options' => $this->getOptions()]);
    }
}
