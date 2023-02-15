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
    protected string $name;

    protected GridTypeInterface $type;

    protected ?Source $source = null;

    protected ?string $route = null;

    protected array $routeParameters = [];

    protected ?bool $persistence = null;

    protected int $page = 0;

    protected ?int $limit = null;

    protected ?int $maxResults = null;

    protected bool $filterable = true;

    protected bool $sortable = true;

    protected ?string $sortBy = null;

    protected string $order = 'asc';

    protected string|array|null $groupBy = null;

    protected bool $massActionsInNewTab = false;

    protected array $actions;

    protected array $options;

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
    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setSource(?Source $source): self
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
    public function getRoute(): ?string
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
    public function isPersisted(): ?bool
    {
        return $this->persistence;
    }

    public function setPersistence(?bool $persistence): self
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
    public function getMaxPerPage(): ?int
    {
        return $this->limit;
    }

    public function setMaxPerPage(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function setMaxResults(?int $maxResults): self
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
    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function setSortBy(?string $sortBy): self
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupBy(): array|string|null
    {
        return $this->groupBy;
    }

    public function setGroupBy(array|string|null $groupBy): self
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
