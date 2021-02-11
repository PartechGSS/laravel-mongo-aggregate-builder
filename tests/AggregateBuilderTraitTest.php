<?php

use PartechGSS\MongoDB\Aggregate\Builder;
use PartechGSS\Tests\Models\Breakfast;

class AggregateBuilderTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCanGetBuilderStatically(): void
    {
        $builder = Breakfast::builder();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function testStaticBuilderInheritsModel(): void
    {
        $builder = Breakfast::builder();
        $collection = $builder->getCollection();
        $this->assertEquals("breakfasts", $collection->getCollectionName());
    }
}
