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

namespace APY\DataGridBundle\Grid\Mapping;

/**
 * @Annotation
 */
class Source
{
    private array $columns;

    private bool $filterable;

    private bool $sortable;

    private array $groups;

    private array $groupBy;

    public function __construct(
        array $metadata = []
    ) {
        $this->columns    = (isset($metadata['columns']) && $metadata['columns'] != '') ? array_map('trim', explode(',', $metadata['columns'])) : [];
        $this->filterable = isset($metadata['filterable']) ? $metadata['filterable'] : true;
        $this->sortable   = isset($metadata['sortable']) ? $metadata['sortable'] : true;
        $this->groups     = (isset($metadata['groups']) && $metadata['groups'] != '') ? (array)$metadata['groups'] : ['default'];
        $this->groupBy    = (isset($metadata['groupBy']) && $metadata['groupBy'] != '') ? (array)$metadata['groupBy'] : [];
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function hasColumns(): bool
    {
        return !empty($this->columns);
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }
}
