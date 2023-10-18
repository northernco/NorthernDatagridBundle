<?php

namespace APY\DataGridBundle\Tests\Grid\Column;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Column\RankColumn;
use APY\DataGridBundle\Grid\Row;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class RankColumnTest extends TestCase
{
    private RankColumn $column;

    public function setUp(): void
    {
        $this->column = new RankColumn();
    }

    public function testGetType(): void
    {
        $this->assertEquals('rank', $this->column->getType());
    }

    public function testInitialize(): void
    {
        $params = [
            'foo'        => 'foo',
            'bar'        => 'bar',
            'title'      => 'title',
            'filterable' => true,
            'source'     => true,
        ];

        $column = new RankColumn($params);

        $this->assertEquals(
            [
                'foo'        => 'foo',
                'bar'        => 'bar',
                'title'      => 'title',
                'filterable' => false,
                'sortable'   => false,
                'source'     => false,
            ],
            $column->getParams()
        );
    }

    public function testSetId(): void
    {
        $this->assertEquals('rank', $this->column->getId());

        $column = new RankColumn(['id' => 'foo']);
        $this->assertEquals('foo', $column->getId());
    }

    public function testSetTitle(): void
    {
        $this->assertEquals('rank', $this->column->getTitle());

        $column = new RankColumn(['title' => 'foo']);
        $this->assertEquals('foo', $column->getTitle());
    }

    public function testSetSize(): void
    {
        $this->assertEquals('30', $this->column->getSize());

        $column = new RankColumn(['size' => '20']);
        $this->assertEquals('20', $column->getSize());
    }

    public function testSetAlign(): void
    {
        $this->assertEquals(Column::ALIGN_CENTER, $this->column->getAlign());

        $column = new RankColumn(['align' => Column::ALIGN_RIGHT]);
        $this->assertEquals(Column::ALIGN_RIGHT, $column->getAlign());
    }

    public function testRenderCell(): void
    {
        $this->assertEquals(1, $this->column->renderCell(true, $this->createMock(Row::class), $this->createMock(RouterInterface::class)));
        $this->assertEquals(2, $this->column->renderCell(true, $this->createMock(Row::class), $this->createMock(RouterInterface::class)));
        $this->assertEquals(3, $this->column->renderCell(true, $this->createMock(Row::class), $this->createMock(RouterInterface::class)));
        $this->assertEquals(4, $this->column->renderCell(true, $this->createMock(Row::class), $this->createMock(RouterInterface::class)));
    }
}
