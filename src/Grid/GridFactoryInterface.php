<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Source\Source;

/**
 * Interface GridFactoryInterface.
 *
 * @author  Quentin Ferrer
 */
interface GridFactoryInterface
{
    /**
     * Returns a grid.
     *
     * @param string|GridTypeInterface $type    The built type of the grid
     * @param Source                   $source  The initial source for the grid
     * @param array                    $options Options for the grid
     *
     * @return Grid
     */
    public function create(string|GridTypeInterface|null $type = null, ?Source $source = null, array $options = []): Grid;

    /**
     * Returns a grid builder.
     *
     * @param string|GridTypeInterface $type    The built type of the grid
     * @param Source                   $source  The initial source for the grid
     * @param array                    $options Options for the grid
     *
     * @return GridBuilder
     */
    public function createBuilder(string|GridTypeInterface|null $type = null, ?Source $source = null, array $options = []): GridBuilder;

    /**
     * Returns a column.
     *
     * @param string $name    The name of column
     * @param string $type    The type of column
     * @param array  $options The options of column
     *
     * @return Column
     */
    public function createColumn(string $name, string|Column $type, array $options = []): Column;
}
