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
use Closure;

class RowAction implements RowActionInterface
{
    /** @var string */
    protected string $title;

    /** @var string */
    protected string $route;

    /** @var bool */
    protected bool $confirm;

    /** @var string */
    protected string $confirmMessage;

    /** @var string */
    protected string $target;

    /** @var string */
    protected string $column = '__actions';

    /** @var array */
    protected array $routeParameters = [];

    /** @var array */
    protected array $routeParametersMapping = [];

    /** @var array */
    protected array $attributes = [];

    /** @var string|null */
    protected ?string $role;

    /** @var array */
    protected array $callbacks = [];

    /** @var bool */
    protected bool $enabled = true;

    /**
     * Default RowAction constructor.
     *
     * @param string $title      Title of the row action
     * @param string $route      Route to the row action
     * @param bool   $confirm    Show confirm message if true
     * @param string $target     Set the target of this action (_self,_blank,_parent,_top)
     * @param array $attributes Attributes of the anchor tag
     * @param string|null $role       Security role
     *
     */
    public function __construct(string $title, string $route, bool $confirm = false, string $target = '_self', array $attributes = [], string $role = null)
    {
        $this->title = $title;
        $this->route = $route;
        $this->confirm = $confirm;
        $this->confirmMessage = 'Do you want to ' . strtolower($title) . ' this row?';
        $this->target = $target;
        $this->attributes = $attributes;
        $this->role = $role;
    }

    // @todo: has this setter real sense? we passed this value from constructor
    /**
     * Set action title.
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title): RowAction
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    // @todo: has this setter real sense? we passed this value from constructor
    /**
     * Set action route.
     *
     * @param string $route
     *
     * @return self
     */
    public function setRoute(string $route): RowAction
    {
        $this->route = $route;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    // @todo: we should change this to something like "enableConfirm" as "false" is the default value and has pretty much
    // nosense to use setConfirm with false parameter.
    /**
     * Set action confirm.
     *
     * @param bool $confirm
     *
     * @return self
     */
    public function setConfirm(bool $confirm): RowAction
    {
        $this->confirm = $confirm;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirm(): bool
    {
        return $this->confirm;
    }

    /**
     * Set action confirmMessage.
     *
     * @param string $confirmMessage
     *
     * @return self
     */
    public function setConfirmMessage(string $confirmMessage): RowAction
    {
        $this->confirmMessage = $confirmMessage;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmMessage(): string
    {
        return $this->confirmMessage;
    }

    /**
     * Set action target.
     *
     * @param string $target
     *
     * @return self
     */
    public function setTarget(string $target): RowAction
    {
        $this->target = $target;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Set action column.
     *
     * @param string $column Identifier of the action column
     *
     * @return self
     */
    public function setColumn(string $column): RowAction
    {
        $this->column = $column;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Add route parameter.
     *
     * @param array|string $routeParameters
     *
     * @return self
     */
    public function addRouteParameters($routeParameters): RowAction
    {
        $routeParameters = (array) $routeParameters;

        foreach ($routeParameters as $key => $routeParameter) {
            if (is_int($key)) {
                $this->routeParameters[] = $routeParameter;
            } else {
                $this->routeParameters[$key] = $routeParameter;
            }
        }

        return $this;
    }

    /**
     * Set route parameters.
     *
     * @param array|string $routeParameters
     *
     * @return self
     */
    public function setRouteParameters($routeParameters): RowAction
    {
        $this->routeParameters = (array) $routeParameters;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    // @todo: why is this accepting string? it seems pretty useless, isn't it?
    /**
     * Set route parameters mapping.
     *
     * @param array|string $routeParametersMapping
     *
     * @return self
     */
    public function setRouteParametersMapping($routeParametersMapping): RowAction
    {
        $this->routeParametersMapping = (array) $routeParametersMapping;

        return $this;
    }

    /**
     * Map the parameter.
     *
     * @param string $name parameter
     *
     * @return null|string
     */
    public function getRouteParametersMapping(string $name): ?string
    {
        return $this->routeParametersMapping[$name] ?? null;
    }

    public function getRouteParametersMappings(): array
    {
        return $this->routeParametersMapping;
    }

    /**
     * Set attributes.
     *
     * @param array $attributes
     *
     * @return self
     */
    public function setAttributes(array $attributes): RowAction
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Add attribute.
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function addAttribute(string $name, string $value): RowAction
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * set role.
     *
     * @param string $role
     *
     * @return self
     */
    public function setRole(string $role): RowAction
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return string
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Set render callback.
     *
     * @deprecated This is deprecated and will be removed in 3.0; use addManipulateRender instead.
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function manipulateRender(Closure $callback): RowAction
    {
        return $this->addManipulateRender($callback);
    }

    /**
     * Add a callback to render callback stack.
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function addManipulateRender(Closure $callback): RowAction
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    public function getManipulateRenders(): array
    {
        return $this->callbacks;
    }

    /**
     * Render action for row.
     *
     * @param Row $row
     *
     * @return RowAction|void
     */
    public function render(Row $row)
    {
        foreach ($this->callbacks as $callback) {
            if (is_callable($callback)) {
                if (null === call_user_func($callback, $this, $row)) {
                    return;
                }
            }
        }

        return $this;
    }

    // @todo: should not this be "isEnabled"?
    /**
     * {@inheritdoc}
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    // @todo: should not this be "enable" as default value is false?
    /**
     * Set the enabled state of this action.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnabled(bool $enabled): RowAction
    {
        $this->enabled = $enabled;

        return $this;
    }
}
