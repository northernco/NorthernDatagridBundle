<?php

namespace APY\DataGridBundle\Tests\Grid\Column;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Column\DateTimeColumn;
use APY\DataGridBundle\Grid\Filter;
use APY\DataGridBundle\Grid\Row;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class DateTimeColumnTest extends TestCase
{
    public function testGetType(): void
    {
        $column = new DateTimeColumn();
        $this->assertEquals('datetime', $column->getType());
    }

    public function testSetFormat(): void
    {
        $format = 'Y-m-d';

        $column = new DateTimeColumn();
        $column->setFormat($format);

        $this->assertEquals($format, $column->getFormat());
    }

    public function testSetInputFormat(): void
    {
        $inputFormat = 'Y-m-d';

        $column = new DateTimeColumn();
        $column->setInputFormat($inputFormat);

        $this->assertEquals($inputFormat, $column->getInputFormat());
    }

    public function testSetTimezone(): void
    {
        $timezone = 'UTC';

        $column = new DateTimeColumn();
        $column->setTimezone($timezone);

        $this->assertEquals($timezone, $column->getTimezone());
    }

    public function testRenderCellWithoutCallback(): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');

        $dateTime = '2000-01-01 01:00:00';
        $now = new \DateTime($dateTime);

        $this->assertEquals(
            $dateTime,
            $column->renderCell(
                $now,
                $this->createMock(Row::class),
                $this->createMock(RouterInterface::class)
            )
        );
    }

    public function testRenderCellWithCallback(): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');
        $column->manipulateRenderCell(fn($value, $row, $router) => '01:00:00');

        $dateTime = '2000-01-01 01:00:00';
        $now = new \DateTime($dateTime);

        $this->assertEquals(
            '01:00:00',
            $column->renderCell(
                $now,
                $this->createMock(Row::class),
                $this->createMock(RouterInterface::class)
            )
        );
    }

    public function testFilterWithValue(): void
    {
        $column = new DateTimeColumn();
        $column->setData(['operator' => Column::OPERATOR_BTW, 'from' => '2017-03-22 01:30:00', 'to' => '2017-03-23 19:00:00']);

        $this->assertEquals(
            [
                new Filter(Column::OPERATOR_GT, new \DateTime('2017-03-22 01:30:00')),
                new Filter(Column::OPERATOR_LT, new \DateTime('2017-03-23 19:00:00')),
            ],
            $column->getFilters('asource')
        );
    }

    public function testFilterWithFormattedValue(): void
    {
        $column = new DateTimeColumn();
        $column->setInputFormat('m/d/Y H-i-s');
        $column->setData(['operator' => Column::OPERATOR_BTW, 'from' => '03/22/2017 01-30-00', 'to' => '03/23/2017 19-00-00']);

        $this->assertEquals(
            [
                new Filter(Column::OPERATOR_GT, new \DateTime('2017-03-22 01:30:00')),
                new Filter(Column::OPERATOR_LT, new \DateTime('2017-03-23 19:00:00')),
            ],
            $column->getFilters('asource')
        );
    }

    public function testFilterWithoutValue(): void
    {
        $column = new DateTimeColumn();
        $column->setData(['operator' => Column::OPERATOR_ISNULL]);

        $this->assertEquals([new Filter(Column::OPERATOR_ISNULL)], $column->getFilters('asource'));
    }

    public function testQueryIsValid(): void
    {
        $column = new DateTimeColumn();

        $this->assertTrue($column->isQueryValid('2017-03-22 23:00:00'));
    }

    public function testQueryIsInvalid(): void
    {
        $column = new DateTimeColumn();

        $this->assertFalse($column->isQueryValid('foo'));
    }

    public function testInputFormattedQueryIsValid(): void
    {
        $column = new DateTimeColumn();
        $column->setInputFormat('m/d/Y H-i-s');

        $this->assertTrue($column->isQueryValid('03/22/2017 23-00-00'));
    }

    public function testInputFormattedQueryIsInvalid(): void
    {
        $column = new DateTimeColumn();
        $column->setInputFormat('m/d/Y H-i-s');

        $this->assertFalse($column->isQueryValid('2017-03-22 23:00:00'));
    }

    public function testInitializeDefaultParams(): void
    {
        $column = new DateTimeColumn();

        $this->assertEquals(null, $column->getFormat());
        $this->assertEquals('Y-m-d H:i:s', $column->getInputFormat());
        $this->assertEquals(
            [
                Column::OPERATOR_EQ,
                Column::OPERATOR_NEQ,
                Column::OPERATOR_LT,
                Column::OPERATOR_LTE,
                Column::OPERATOR_GT,
                Column::OPERATOR_GTE,
                Column::OPERATOR_BTW,
                Column::OPERATOR_BTWE,
                Column::OPERATOR_ISNULL,
                Column::OPERATOR_ISNOTNULL,
            ],
            $column->getOperators()
        );
        $this->assertEquals(Column::OPERATOR_EQ, $column->getDefaultOperator());
        $this->assertEquals(date_default_timezone_get(), $column->getTimezone());
    }

    public function testInitialize(): void
    {
        $format = 'Y-m-d H:i:s';
        $inputFormat = 'Y-m-d H:i:s';
        $timezone = 'UTC';

        $params = [
            'format'          => $format,
            'inputFormat'     => $inputFormat,
            'operators'       => [Column::OPERATOR_LT, Column::OPERATOR_LTE],
            'defaultOperator' => Column::OPERATOR_LT,
            'timezone'        => $timezone,
        ];

        $column = new DateTimeColumn($params);

        $this->assertEquals($format, $column->getFormat());
        $this->assertEquals($inputFormat, $column->getInputFormat());
        $this->assertEquals(
            [
                Column::OPERATOR_LT,
                Column::OPERATOR_LTE,
            ],
            $column->getOperators()
        );
        $this->assertEquals(Column::OPERATOR_LT, $column->getDefaultOperator());
        $this->assertEquals($timezone, $column->getTimezone());
    }

    /**
     * @dataProvider provideDisplayInput
     */
    public function testCorrectDisplayOut(mixed $value, mixed $expectedOutput, ?string $timezone = null): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');

        if ($timezone !== null) {
            $column->setTimezone($timezone);
        }

        $this->assertEquals($expectedOutput, $column->getDisplayedValue($value));
    }

    public function testDisplayValueForDateTimeImmutable(): void
    {
        $now = new \DateTimeImmutable();

        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');
        $this->assertEquals($now->format('Y-m-d H:i:s'), $column->getDisplayedValue($now));
    }

    public function testDateTimeZoneForDisplayValueIsTheSameAsTheColumn(): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');
        $column->setTimezone('UTC');

        $now = new \DateTime('2000-01-01 01:00:00', new \DateTimeZone('Europe/Amsterdam'));

        $this->assertEquals('2000-01-01 00:00:00', $column->getDisplayedValue($now));
    }

    public function provideDisplayInput(): array
    {
        $now = new \DateTime();

        return [
            [$now, $now->format('Y-m-d H:i:s')],
            ['2016/01/01 12:13:14', '2016-01-01 12:13:14'],
            [1, '1970-01-01 00:00:01', 'UTC'],
            ['', ''],
        ];
    }
}
