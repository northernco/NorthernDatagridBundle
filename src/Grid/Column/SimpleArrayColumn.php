<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\DataGridBundle\Grid\Column;

use APY\DataGridBundle\Grid\Filter;
use APY\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\RouterInterface;

class SimpleArrayColumn extends Column
{
    public function __initialize(array $params): void
    {
        parent::__initialize($params);

        $this->setOperators($this->getParam('operators', [
            self::OPERATOR_LIKE,
            self::OPERATOR_NLIKE,
            self::OPERATOR_EQ,
            self::OPERATOR_NEQ,
            self::OPERATOR_ISNULL,
            self::OPERATOR_ISNOTNULL,
        ]));
        $this->setDefaultOperator($this->getParam('defaultOperator', self::OPERATOR_LIKE));
    }

    public function getFilters(string $source): array
    {
        $parentFilters = parent::getFilters($source);

        $filters = [];
        foreach ($parentFilters as $filter) {
            switch ($filter->getOperator()) {
                case self::OPERATOR_EQ:
                case self::OPERATOR_NEQ:
                    $value = $filter->getValue();
                    $filters[] = new Filter($filter->getOperator(), $value);
                    break;
                case self::OPERATOR_LIKE:
                case self::OPERATOR_NLIKE:
                    $value = $filter->getValue();
                    $filters[] = new Filter($filter->getOperator(), $value);
                    break;
                case self::OPERATOR_ISNULL:
                    $filters[] = new Filter(self::OPERATOR_ISNULL);
                    $filters[] = new Filter(self::OPERATOR_EQ, '');
                    $this->setDataJunction(self::DATA_DISJUNCTION);
                    break;
                case self::OPERATOR_ISNOTNULL:
                    $filters[] = new Filter(self::OPERATOR_ISNOTNULL);
                    $filters[] = new Filter(self::OPERATOR_NEQ, '');
                    break;
                default:
                    $filters[] = $filter;
            }
        }

        return $filters;
    }

    public function renderCell(mixed $value, Row $row, RouterInterface $router): mixed
    {
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $value, $row, $router);
        }

        // @todo: when it has an array as value?
        $return = [];
        if (is_array($value) || $value instanceof \Traversable) {
            foreach ($value as $key => $itemValue) {
                if (!is_array($itemValue) && isset($this->values[(string) $itemValue])) {
                    $itemValue = $this->values[$itemValue];
                }

                $return[$key] = $itemValue;
            }
        }

        return $return;
    }

    public function getType(): string
    {
        return 'simple_array';
    }
}
