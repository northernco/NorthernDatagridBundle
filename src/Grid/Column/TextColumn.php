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

class TextColumn extends Column
{
    public function isQueryValid(mixed $query): bool
    {
        $result = array_filter((array) $query, 'is_string');

        return !empty($result);
    }

    public function getFilters(string $source): array
    {
        $parentFilters = parent::getFilters($source);

        $filters = [];
        foreach ($parentFilters as $filter) {
            switch ($filter->getOperator()) {
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

    public function getType(): string
    {
        return 'text';
    }
}
