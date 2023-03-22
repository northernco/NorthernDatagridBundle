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

namespace APY\DataGridBundle\Grid\Action;

use APY\DataGridBundle\Grid\Row;

class RowAction implements RowActionInterface
{
    private string $title;

    private string $route;

    private bool $confirm;

    private string $confirmMessage;

    private string $target;

    private string $column = '__actions';

    private array $routeParameters = [];

    private array $routeParametersMapping = [];

    private array $attributes = [];

    private ?string $role;

    private array $callbacks = [];

    private bool $enabled = true;

    public function __construct(
        string $title,
        string $route,
        bool $confirm = false,
        string $target = '_self',
        array $attributes = [],
        ?string $role = null
    ) {
        $this->title          = $title;
        $this->route          = $route;
        $this->confirm        = $confirm;
        $this->confirmMessage = 'Do you want to ' . strtolower($title) . ' this row?';
        $this->target         = $target;
        $this->attributes     = $attributes;
        $this->role           = $role;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setConfirm(bool $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function getConfirm(): bool
    {
        return $this->confirm;
    }

    public function setConfirmMessage(string $confirmMessage): self
    {
        $this->confirmMessage = $confirmMessage;

        return $this;
    }

    public function getConfirmMessage(): string
    {
        return $this->confirmMessage;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function addRouteParameters(array|string $routeParameters): self
    {
        $routeParameters = (array)$routeParameters;

        foreach ($routeParameters as $key => $routeParameter) {
            if (is_int($key)) {
                $this->routeParameters[] = $routeParameter;
            } else {
                $this->routeParameters[$key] = $routeParameter;
            }
        }

        return $this;
    }

    public function setRouteParameters(array|string $routeParameters): self
    {
        $this->routeParameters = (array)$routeParameters;

        return $this;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setRouteParametersMapping(array|string $routeParametersMapping): self
    {
        $this->routeParametersMapping = (array)$routeParametersMapping;

        return $this;
    }

    public function getRouteParametersMapping(string $name): ?string
    {
        return isset($this->routeParametersMapping[$name]) ? $this->routeParametersMapping[$name] : null;
    }

    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function addAttribute(string $name, string $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function addManipulateRender(\Closure $callback): self
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    public function render(Row $row): ?self
    {
        foreach ($this->callbacks as $callback) {
            if (is_callable($callback)) {
                if (null === call_user_func($callback, $this, $row)) {
                    return null;
                }
            }
        }

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }
}
