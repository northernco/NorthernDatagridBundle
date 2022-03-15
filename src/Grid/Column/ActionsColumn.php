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

namespace APY\DataGridBundle\Grid\Column;

class ActionsColumn extends Column
{
    /**
     * @var array
     */
    protected array $rowActions;

     /**
     * ActionsColumn constructor.
     *
     * @param string $column     Identifier of the column
     * @param string $title      Title of the column
     * @param array  $rowActions Array of rowAction
     */
    public function __construct(string $column, string $title, array $rowActions = [])
    {
        $this->rowActions = $rowActions;

        parent::__construct([
            'id'         => $column,
            'title'      => $title,
            'sortable'   => false,
            'source'     => false,
            'filterable' => true, // Show a reset link instead of a filter
        ]);
    }

    public function getRouteParameters($row, $action): array
    {
        $actionParameters = $action->getRouteParameters();

        if (!empty($actionParameters)) {
            $routeParameters = [];

            foreach ($actionParameters as $name => $parameter) {
                if (is_int($name)) {
                    if (($name = $action->getRouteParametersMapping($parameter)) === null) {
                        $name = $this->getValidRouteParameters($parameter);
                    }
                    $routeParameters[$name] = $row->getField($parameter);
                } else {
                    $routeParameters[$this->getValidRouteParameters($name)] = $parameter;
                }
            }

            return $routeParameters;
        }

        return [$row->getPrimaryField() => $row->getPrimaryFieldValue()];
    }

    protected function getValidRouteParameters($name)
    {
        $pos = 0;
        while (($pos = strpos($name, '.', ++$pos)) !== false) {
            $name = substr($name, 0, $pos) . strtoupper(substr($name, $pos + 1, 1)) . substr($name, $pos + 2);
        }

        return $name;
    }

    public function getRowActions(): array
    {
        return $this->rowActions;
    }

    public function setRowActions(array $rowActions): ActionsColumn
    {
        $this->rowActions = $rowActions;

        return $this;
    }

    public function isVisible($isExported = false): bool
    {
        if ($isExported) {
            return false;
        }

        return parent::isVisible();
    }

    public function getFilterType(): string
    {
        return $this->getType();
    }

    /**
     * Get the list of actions to render.
     *
     * @param $row
     *
     * @return array
     */
    public function getActionsToRender($row): array
    {
        $list = $this->rowActions;
        foreach ($list as $i => $a) {
            $action = clone $a;
            $list[$i] = $action->render($row);
            if (null === $list[$i]) {
                unset($list[$i]);
            }
        }

        return $list;
    }

    public function getType(): string
    {
        return 'actions';
    }
}
