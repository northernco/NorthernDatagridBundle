<?php

namespace APY\DataGridBundle\Tests\Grid;

use APY\DataGridBundle\Grid\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function testCreateFilters(): void
    {
        $filter1 = new Filter('like', 'foo', 'column1');

        $this->assertSame('like', $filter1->getOperator());
        $this->assertSame('foo', $filter1->getValue());
        $this->assertSame('column1', $filter1->getColumnName());
    }

    public function testGetOperator(): void
    {
        $filter = new Filter('like');

        $this->assertEquals('like', $filter->getOperator());
    }

    public function testGetValue(): void
    {
        $filter = new Filter('like', 'foo');

        $this->assertEquals('foo', $filter->getValue());
    }

    public function testGetColumnName(): void
    {
        $filter = new Filter('like', null, 'col1');

        $this->assertEquals('col1', $filter->getColumnName());
    }

    public function testHasColumnName(): void
    {
        $filter1 = new Filter('like', 'foo', 'col1');
        $filter2 = new Filter('like');

        $this->assertTrue($filter1->hasColumnName());
        $this->assertFalse($filter2->hasColumnName());
    }
}
