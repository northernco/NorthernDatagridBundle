<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Source\Source;

/**
 * The configuration of a {@link Grid} object.
 *
 * @author  Quentin Ferrer
 */
interface GridConfigInterface
{
    /**
     * Returns the name of the grid.
     *
     * @return string The grid name
     */
    public function getName(): string;

    /**
     * Returns the source of the grid.
     *
     * @return Source The source of the grid.
     */
    public function getSource(): Source;

    /**
     * Returns the grid type used to construct the grid.
     *
     * @return GridTypeInterface The grid's type.
     */
    public function getType(): GridTypeInterface;

    /**
     * Returns the route of the grid.
     *
     * @return string The route of the grid.
     */
    public function getRoute(): string;

    /**
     * Returns the route parameters of the grid.
     *
     * @return array The route parameters.
     */
    public function getRouteParameters(): array;

    /**
     * Returns whether the grid is persisted.
     *
     * @return bool Whether the grid is persisted.
     */
    public function isPersisted(): bool;

    /**
     * Returns the default page.
     *
     * @return int The default page.
     */
    public function getPage(): int;

    /**
     * Returns all options passed during the construction of grid.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Returns whether a specific option exists.
     *
     * @param string $name The option name.
     *
     * @return bool
     */
    public function hasOption(string $name): bool;

    /**
     * Returns the value of a specific option.
     *
     * @param string $name    The option name.
     * @param mixed  $default The value returned if the option does not exist.
     *
     * @return mixed The option value
     */
    public function getOption(string $name, mixed $default = null): mixed;

    /**
     * Returns whether the grid is filterable.
     *
     * @return bool Whether the grid is filterable.
     */
    public function isFilterable(): bool;

    /**
     * Returns whether the grid is sortable.
     *
     * @return bool Whether the grid is sortable.
     */
    public function isSortable(): bool;

    /**
     * Returns the maximum number of results of the grid.
     *
     * @return int The maximum number of results of the grid.
     */
    public function getMaxResults(): int;

    /**
     * Returns the maximum number of items per page.
     *
     * @return int The maximum number of items per page.
     */
    public function getMaxPerPage(): int;

    /**
     * Returns the default order.
     *
     * @return string The default order.
     */
    public function getOrder(): string;

    /**
     * Returns the default sort field.
     *
     * @return string The default sort field.
     */
    public function getSortBy(): string;

    /**
     * Returns the default group field.
     *
     * @return string|array
     */
    public function getGroupBy(): string|array;

    /**
     * Returns whether or not mass actions should be opened in a new tab.
     *
     * @return bool
     */
    public function getMassActionsInNewTab(): bool;
}
