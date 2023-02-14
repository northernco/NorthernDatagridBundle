<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Column\Column;

/**
 * Interface GridBuilderInterface.
 *
 * @author  Quentin Ferrer
 */
interface GridBuilderInterface
{
    /**
     * Adds a column.
     *
     * @param string        $name
     * @param string|Column $type
     * @param array         $options
     *
     * @return GridBuilderInterface
     */
    public function add(string $name, string|Column $type, array $options = []): self;

    /**
     * Returns a column.
     *
     * @param string $name The name of column
     *
     * @return Column
     */
    public function get(string $name): Column;

    /**
     * Removes the column with the given name.
     *
     * @param string $name The name of column
     *
     * @return GridBuilderInterface
     */
    public function remove(string $name): self;

    /**
     * Returns whether a column with the given name exists.
     *
     * @param string $name The name of column
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Creates the grid.
     *
     * @return Grid The grid
     */
    public function getGrid(): Grid;
}
