<?php

namespace PartechGSS\Tests\Models;

use PartechGSS\Traits\AggregateBuilder;

class Breakfast extends \Illuminate\Database\Eloquent\Model
{
    use AggregateBuilder;

    protected $connection = 'mongodb';
}