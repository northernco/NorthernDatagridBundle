<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Action\RowActionInterface;
use APY\DataGridBundle\Grid\Source\Source;

/**
 * A basic grid configuration.
 *
 * @author  Quentin Ferrer
 */
class GridConfigBuilder implements GridConfigBuilderInterface
{
    private string $name;

    private GridTypeInterface $type;

    private Source $source;

    private string $route;

    private array $routeParameters = [];

    private bool $persistence;

    private int $page = 0;

    private int $limit;

    private int $maxResults;

    private bool $filterable = true;

    private bool $sortable = true;

    private string $sortBy;

    private string $order = 'asc';

    private string|array $groupBy;

    private bool $massActionsInNewTab;

    private array $actions;

    private array $options;

    public function __construct(string $name, array $options = [])
    {
        $this->name    = $name;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource(): Source
    {
        return $this->source;
    }

    public function setSource(Source $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): GridTypeInterface
    {
        return $this->type;
    }

    public function setType(GridTypeInterface $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setRouteParameters(array $routeParameters): self
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isPersisted(): bool
    {
        return $this->persistence;
    }

    public function setPersistence(bool $persistence): self
    {
        $this->persistence = $persistence;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPerPage(): int
    {
        return $this->limit;
    }

    public function setMaxPerPage(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    public function setMaxResults(int $maxResults): self
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function setFilterable(bool $filterable): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortBy(string $sortBy): self
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupBy(): array|string
    {
        return $this->groupBy;
    }

    public function setGroupBy(array|string $groupBy): self
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    public function setMassActionsInNewTab(bool $massActionsInNewTab): self
    {
        $this->massActionsInNewTab = $massActionsInNewTab;

        return $this;
    }

    public function getMassActionsInNewTab(): bool
    {
        return $this->massActionsInNewTab;
    }

    public function addAction(RowActionInterface $action): self
    {
        $this->actions[$action->getColumn()][] = $action;

        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * {@inheritdoc}
     */
    public function getGridConfig(): GridConfigInterface
    {
        $config = clone $this;

        return $config;
    }
}
