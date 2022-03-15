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

use SplObjectStorage;

class Metadata
{
    protected $name;
    /**
     * @var array
     */
    protected $fields;
    /**
     * @var array
     */
    protected $fieldsMappings;
    protected $groupBy;

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFieldsMappings($fieldsMappings): Metadata
    {
        $this->fieldsMappings = $fieldsMappings;

        return $this;
    }

    public function hasFieldMapping($field): bool
    {
        return isset($this->fieldsMappings[$field]);
    }

    public function getFieldMapping($field)
    {
        return $this->fieldsMappings[$field];
    }

    public function getFieldMappings(): array
    {
        return $this->fieldsMappings;
    }

    public function getFieldMappingType($field)
    {
        return (isset($this->fieldsMappings[$field]['type'])) ? $this->fieldsMappings[$field]['type'] : 'text';
    }

    public function setGroupBy($groupBy): Metadata
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    public function getGroupBy()
    {
        return $this->groupBy;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @todo move to another place
     *
     * @param $columnExtensions
     *
     * @throws \Exception
     *
     * @return SplObjectStorage
     */
    public function getColumnsFromMapping($columnExtensions): SplObjectStorage
    {
        $columns = new SplObjectStorage();

        foreach ($this->getFields() as $value) {
            $params = $this->getFieldMapping($value);
            $type = $this->getFieldMappingType($value);

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
