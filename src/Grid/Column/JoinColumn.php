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

class JoinColumn extends TextColumn
{
    private array $joinColumns = [];

    protected int $dataJunction = self::DATA_DISJUNCTION;

    public function __initialize(array $params): void
    {
        parent::__initialize($params);

        $this->setJoinColumns($this->getParam('columns', []));
        $this->setSeparator($this->getParam('separator', '&nbsp;'));

        $this->setVisibleForSource(true);
        $this->setIsManualField(true);
    }

    public function setJoinColumns(array $columns): void
    {
        $this->joinColumns = $columns;
    }

    public function getJoinColumns(): array
    {
        return $this->joinColumns;
    }

    public function getFilters(string $source): array
    {
        $filters = [];

        // Apply same filters on each column
        foreach ($this->joinColumns as $columnName) {
            $tempFilters = parent::getFilters($source);

            foreach ($tempFilters as $filter) {
                $filter->setColumnName($columnName);
            }

            $filters = array_merge($filters, $tempFilters);
        }

        return $filters;
    }

    public function getType(): string
    {
        return 'join';
    }
}
