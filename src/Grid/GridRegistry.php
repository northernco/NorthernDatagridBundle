<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Exception\ColumnAlreadyExistsException;
use APY\DataGridBundle\Grid\Exception\ColumnNotFoundException;
use APY\DataGridBundle\Grid\Exception\TypeAlreadyExistsException;
use APY\DataGridBundle\Grid\Exception\TypeNotFoundException;

/**
 * The central registry of the Grid component.
 *
 * @author  Quentin Ferrer
 */
class GridRegistry implements GridRegistryInterface
{
    /**
     * @var GridTypeInterface[]
     */
    private array $types = [];

    /**
     * @var Column[]
     */
    private array $columns = [];

    public function addType(GridTypeInterface $type): self
    {
        $name = $type->getName();

        if ($this->hasType($name)) {
            throw new TypeAlreadyExistsException($name);
        }

        $this->types[$name] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(string $name): GridTypeInterface
    {
        if (!$this->hasType($name)) {
            throw new TypeNotFoundException($name);
        }

        $type = $this->types[$name];

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function hasType(string $name): bool
    {
        if (isset($this->types[$name])) {
            return true;
        }

        return false;
    }

    public function addColumn(Column $column): self
    {
        $type = $column->getType();

        if ($this->hasColumn($type)) {
            throw new ColumnAlreadyExistsException($type);
        }

        $this->columns[$type] = $column;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumn(string $type): Column
    {
        if (!$this->hasColumn($type)) {
            throw new ColumnNotFoundException($type);
        }

        $column = $this->columns[$type];

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function hasColumn(string $type): bool
    {
        if (isset($this->columns[$type])) {
            return true;
        }

        return false;
    }
}
