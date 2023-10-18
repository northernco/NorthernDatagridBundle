<?php

namespace APY\DataGridBundle\Tests\Grid;

use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Rows;
use PHPUnit\Framework\TestCase;

class RowsTest extends TestCase
{
    private Rows $rowsSUT;

    private array $rows;

    public function setUp(): void
    {
        $this->rows = [$this->createMock(Row::class), $this->createMock(Row::class), $this->createMock(Row::class)];
        $this->rowsSUT = new Rows($this->rows);
    }

    public function testAddRowsOnConstruct(): void
    {
        $this->assertEquals(3, $this->rowsSUT->count());
    }

    public function testGetIterator(): void
    {
        $this->assertInstanceOf(\SplObjectStorage::class, $this->rowsSUT->getIterator());
    }

    public function testAddRow(): void
    {
        $this->rowsSUT->addRow($this->createMock(Row::class));
        $this->assertEquals(4, $this->rowsSUT->count());
    }

    public function testToArray(): void
    {
        $this->assertEquals($this->rows, $this->rowsSUT->toArray());
    }
}
