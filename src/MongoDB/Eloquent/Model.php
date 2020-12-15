<?php
namespace PartechGSS\MongoDB\Eloquent;

use PartechGSS\Traits\AggregateBuilder;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use AggregateBuilder;

    protected $connection = 'mongodb';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
}