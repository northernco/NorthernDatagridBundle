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

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Helper\ColumnsIterator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Columns implements \IteratorAggregate, \Countable
{
    private array $columns = [];

    private array $extensions = [];

    public const MISSING_COLUMN_EX_MSG = 'Column with id "%s" doesn\'t exists';

    protected AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getIterator(bool $showOnlySourceColumns = false): \Traversable
    {
        return new ColumnsIterator(new \ArrayIterator($this->columns), $showOnlySourceColumns);
    }

    public function addColumn(Column $column, int $position = 0): self
    {
        $position = $position;
        $column->setAuthorizationChecker($this->authorizationChecker);

        if ($position == 0) {
            $this->columns[] = $column;
        } else {
            if ($position > 0) {
                --$position;
            } else {
                $position = max(0, count($this->columns) + $position);
            }

            $head          = array_slice($this->columns, 0, $position);
            $tail          = array_slice($this->columns, $position);
            $this->columns = array_merge($head, [$column], $tail);
        }

        return $this;
    }

    public function getColumnById(int|string $columnId): Column
    {
        if (($column = $this->hasColumnById($columnId, true)) === false) {
            throw new \InvalidArgumentException(sprintf(self::MISSING_COLUMN_EX_MSG, $columnId));
        }

        return $column;
    }

    public function hasColumnById(int|string $columnId, bool $returnColumn = false): bool|Column
    {
        foreach ($this->columns as $column) {
            if ($column->getId() == $columnId) {
                return $returnColumn ? $column : true;
            }
        }

        return false;
    }

    public function getPrimaryColumn(): Column
    {
        foreach ($this->columns as $column) {
            if ($column->isPrimary()) {
                return $column;
            }
        }

        throw new \InvalidArgumentException('Primary column doesn\'t exists');
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->columns);
    }

    public function addExtension(Column $extension): self
    {
        $this->extensions[strtolower($extension->getType())] = $extension;

        return $this;
    }

    public function hasExtensionForColumnType(string $type): bool
    {
        return isset($this->extensions[$type]);
    }

    public function getExtensionForColumnType(string $type): bool
    {
        // @todo: should not index be checked?
        return $this->extensions[$type];
    }

    public function getHash(): string
    {
        $hash = '';
        foreach ($this->columns as $column) {
            $hash .= $column->getId();
        }

        return $hash;
    }

    public function setColumnsOrder(array $columnIds, bool $keepOtherColumns = true): self
    {
        $reorderedColumns    = [];
        $columnsIndexedByIds = [];

        foreach ($this->columns as $column) {
            $columnsIndexedByIds[$column->getId()] = $column;
        }

        foreach ($columnIds as $columnId) {
            if (isset($columnsIndexedByIds[$columnId])) {
                $reorderedColumns[] = $columnsIndexedByIds[$columnId];
                unset($columnsIndexedByIds[$columnId]);
            }
        }

        if ($keepOtherColumns) {
            $this->columns = array_merge($reorderedColumns, array_values($columnsIndexedByIds));
        } else {
            $this->columns = $reorderedColumns;
        }

        return $this;
    }
}
