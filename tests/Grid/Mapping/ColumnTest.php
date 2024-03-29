<?php

namespace APY\DataGridBundle\Tests\Grid\Mapping;

use APY\DataGridBundle\Grid\Mapping\Column;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function setUp(): void
    {
        $this->stringMetadata = 'foo';
        $this->arrayMetadata  = ['foo' => 'bar', 'groups' => 'baz'];
    }

    public function testColumnMetadataCanBeEmpty(): void
    {
        $column = new Column([]);
        $this->assertEmpty($column->getMetadata());
        $this->assertSame(['default'], $column->getGroups());
    }

    public function testColumnStringMetadataInjectedInConstructor(): void
    {
        $column = new Column($this->stringMetadata);
        $this->assertSame($this->stringMetadata, $column->getMetadata());
    }

    public function testColumnArrayMetadataInjectedInConstructor(): void
    {
        $column = new Column($this->arrayMetadata);
        $this->assertSame($this->arrayMetadata, $column->getMetadata());
    }
}
