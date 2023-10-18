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

namespace APY\DataGridBundle\Grid\Mapping\Metadata;

use APY\DataGridBundle\Grid\Columns;

class Metadata
{
    private string $name;

    private array $fields;

    private array $fieldsMappings;

    private array|string $groupBy;

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFieldsMappings(array $fieldsMappings): self
    {
        $this->fieldsMappings = $fieldsMappings;

        return $this;
    }

    public function hasFieldMapping(string $field): bool
    {
        return isset($this->fieldsMappings[$field]);
    }

    public function getFieldMapping(string $field): mixed
    {
        return $this->fieldsMappings[$field];
    }

    public function getFieldsMappings(): array
    {
        return $this->fieldsMappings;
    }

    public function getFieldMappingType(string $field): string
    {
        return (isset($this->fieldsMappings[$field]['type'])) ? $this->fieldsMappings[$field]['type'] : 'text';
    }

    public function setGroupBy(array|string $groupBy): self
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    public function getGroupBy(): array|string
    {
        return $this->groupBy;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumnsFromMapping(Columns $columnExtensions): \SplObjectStorage
    {
        $columns = new \SplObjectStorage();

        foreach ($this->getFields() as $value) {
            $params = $this->getFieldMapping($value);
            $type   = $this->getFieldMappingType($value);

            /* todo move available extensions from columns */
            if ($columnExtensions->hasExtensionForColumnType($type)) {
                $column = clone $columnExtensions->getExtensionForColumnType($type);
                $column->__initialize($params);
                $columns->attach($column);
            } else {
                throw new \Exception(sprintf('No suitable Column Extension found for column type: %s', $type));
            }
        }

        return $columns;
    }
}
