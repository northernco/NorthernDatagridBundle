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

namespace APY\DataGridBundle\Grid\Source;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Columns;
use APY\DataGridBundle\Grid\Exception\PropertyAccessDeniedException;
use APY\DataGridBundle\Grid\Helper\ColumnsIterator;
use APY\DataGridBundle\Grid\Mapping\Driver\DriverInterface;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Rows;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

abstract class Source implements DriverInterface
{
    /**
     * @var callable|null
     */
    private $prepareQueryCallback = null;

    /**
     * @var callable|null
     */
    private $prepareRowCallback = null;

    private array|object|null $data = null;

    private array $items = [];

    private int $count;

    public function prepareQuery(QueryBuilder $queryBuilder): void
    {
        if (is_callable($this->prepareQueryCallback)) {
            call_user_func($this->prepareQueryCallback, $queryBuilder);
        }
    }

    public function prepareRow(Row $row): ?Row
    {
        if (is_callable($this->prepareRowCallback)) {
            return call_user_func($this->prepareRowCallback, $row);
        }

        return $row;
    }

    public function manipulateQuery(?callable $callback = null): self
    {
        $this->prepareQueryCallback = $callback;

        return $this;
    }

    public function manipulateRow(?\Closure $callback = null): self
    {
        $this->prepareRowCallback = $callback;

        return $this;
    }

    abstract public function execute(ColumnsIterator $columns, int $page = 0, int $limit = 0, ?int $maxResults = null, int $gridDataJunction = Column::DATA_CONJUNCTION): Rows;

    abstract public function getTotalCount(?int $maxResults = null): int;

    abstract public function initialise(ManagerRegistry $doctrine, Manager $mapping): void;

    abstract public function getColumns(Columns $columns): void;

    public function getClassColumns(string $class, string $group = 'default'): array
    {
        return [];
    }

    public function getFieldsMetadata(string $class, string $group = 'default'): array
    {
        return [];
    }

    public function getGroupBy(string $class, string $group = 'default'): array
    {
        return [];
    }

    abstract public function populateSelectFilters(array $columns, bool $loop = false): void;

    abstract public function getHash(): string;

    abstract public function delete(array $ids): void;

    public function setData(array|object $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array|object|null
    {
        return $this->data;
    }

    public function isDataLoaded(): bool
    {
        return $this->data !== null;
    }

    protected function getItemsFromData(Columns $columns): array
    {
        $items = [];

        foreach ($this->data as $key => $item) {
            foreach ($columns as $column) {
                $fieldName  = $column->getField();
                $fieldValue = '';

                if ($this instanceof Entity) {
                    // Mapped field
                    $itemEntity = $item;
                    if (strpos($fieldName, '.') === false) {
                        $functionName = ucfirst($fieldName);
                    } else {
                        // loop through all elements until we find the final entity and the name of the value for which we are looking
                        $elements = explode('.', $fieldName);
                        while ($element = array_shift($elements)) {
                            if (count($elements) > 0) {
                                $itemEntity = call_user_func([$itemEntity, 'get' . $element]);
                            } else {
                                $functionName = ucfirst($element);
                            }
                        }
                    }

                    // Get value of the column
                    if (isset($itemEntity->$fieldName)) {
                        $fieldValue = $itemEntity->$fieldName;
                    } elseif (is_callable([$itemEntity, $fullFunctionName = 'get' . $functionName])
                              || is_callable([$itemEntity, $fullFunctionName = 'has' . $functionName])
                              || is_callable([$itemEntity, $fullFunctionName = 'is' . $functionName])) {
                        $fieldValue = call_user_func([$itemEntity, $fullFunctionName]);
                    } else {
                        throw new PropertyAccessDeniedException(sprintf('Property "%s" is not public or has no accessor.', $fieldName));
                    }
                } elseif (isset($item[$fieldName])) {
                    $fieldValue = $item[$fieldName];
                }

                $items[$key][$fieldName] = $fieldValue;
            }
        }

        return $items;
    }

    public function executeFromData(Columns $columns, int $page = 0, int $limit = 0, ?int $maxResults = null): Rows
    {
        // Populate from data
        $items            = $this->getItemsFromData($columns);
        $serializeColumns = [];

        foreach ($this->data as $key => $item) {
            foreach ($columns as $column) {
                $fieldName     = $column->getField();
                $fieldValue    = $items[$key][$fieldName];
                $dataIsNumeric = ($column->getType() == 'number' || $column->getType() == 'boolean');

                if ($column->getType() === 'array') {
                    $serializeColumns[] = $column->getId();
                }

                // Filter
                if ($column->isFiltered()) {
                    // Some attributes of the column can be changed in this function
                    $filters = $column->getFilters('vector');

                    if ($column->getDataJunction() === Column::DATA_DISJUNCTION) {
                        $disjunction = true;
                        $keep        = false;
                    } else {
                        $disjunction = false;
                        $keep        = true;
                    }

                    $found = false;
                    foreach ($filters as $filter) {
                        $operator = $filter->getOperator();
                        $value    = $filter->getValue();

                        // Normalize value
                        if (!$dataIsNumeric && !($value instanceof \DateTime)) {
                            $value = $this->prepareStringForLikeCompare($value);
                            switch ($operator) {
                                case Column::OPERATOR_EQ:
                                    $value = '/^' . preg_quote($value, '/') . '$/i';
                                    break;
                                case Column::OPERATOR_NEQ:
                                    $value = '/^(?!' . preg_quote($value, '/') . '$).*$/i';
                                    break;
                                case Column::OPERATOR_LIKE:
                                    $value = '/' . preg_quote($value, '/') . '/i';
                                    break;
                                case Column::OPERATOR_NLIKE:
                                    $value = '/^((?!' . preg_quote($value, '/') . ').)*$/i';
                                    break;
                                case Column::OPERATOR_LLIKE:
                                    $value = '/' . preg_quote($value, '/') . '$/i';
                                    break;
                                case Column::OPERATOR_RLIKE:
                                    $value = '/^' . preg_quote($value, '/') . '/i';
                                    break;
                                case Column::OPERATOR_SLIKE:
                                    $value = '/' . preg_quote($value, '/') . '/';
                                    break;
                                case Column::OPERATOR_NSLIKE:
                                    $value = '/^((?!' . preg_quote($value, '/') . ').)*$/';
                                    break;
                                case Column::OPERATOR_LSLIKE:
                                    $value = '/' . preg_quote($value, '/') . '$/';
                                    break;
                                case Column::OPERATOR_RSLIKE:
                                    $value = '/^' . preg_quote($value, '/') . '/';
                                    break;
                            }
                        }

                        // Test
                        switch ($operator) {
                            case Column::OPERATOR_EQ:
                                if ($dataIsNumeric) {
                                    $found = abs($fieldValue - $value) < 0.00001;
                                    break;
                                }
                            case Column::OPERATOR_NEQ:
                                if ($dataIsNumeric) {
                                    $found = abs($fieldValue - $value) > 0.00001;
                                    break;
                                }
                            case Column::OPERATOR_LIKE:
                            case Column::OPERATOR_NLIKE:
                            case Column::OPERATOR_LLIKE:
                            case Column::OPERATOR_RLIKE:
                            case Column::OPERATOR_SLIKE:
                            case Column::OPERATOR_NSLIKE:
                            case Column::OPERATOR_LSLIKE:
                            case Column::OPERATOR_RSLIKE:
                                $fieldValue = $this->prepareStringForLikeCompare($fieldValue, $column->getType());

                                $found = preg_match($value, $fieldValue);
                                break;
                            case Column::OPERATOR_GT:
                                $found = $fieldValue > $value;
                                break;
                            case Column::OPERATOR_GTE:
                                $found = $fieldValue >= $value;
                                break;
                            case Column::OPERATOR_LT:
                                $found = $fieldValue < $value;
                                break;
                            case Column::OPERATOR_LTE:
                                $found = $fieldValue <= $value;
                                break;
                            case Column::OPERATOR_ISNULL:
                                $found = $fieldValue === null;
                                break;
                            case Column::OPERATOR_ISNOTNULL:
                                $found = $fieldValue !== null;
                                break;
                        }

                        // AND
                        if (!$found && !$disjunction) {
                            $keep = false;
                            break;
                        }

                        // OR
                        if ($found && $disjunction) {
                            $keep = true;
                            break;
                        }
                    }

                    if (!$keep) {
                        unset($items[$key]);
                        break;
                    }
                }
            }
        }

        // Order
        foreach ($columns as $column) {
            if ($column->isSorted()) {
                $sortType    = SORT_REGULAR;
                $sortedItems = [];
                foreach ($items as $key => $item) {
                    $value = $item[$column->getField()];

                    // Format values for sorting and define the type of sort
                    switch ($column->getType()) {
                        case 'text':
                            $sortedItems[$key] = strtolower($value);
                            $sortType          = SORT_STRING;
                            break;
                        case 'datetime':
                        case 'date':
                        case 'time':
                            if ($value instanceof \DateTime) {
                                $sortedItems[$key] = $value->getTimestamp();
                            } else {
                                $sortedItems[$key] = strtotime($value);
                            }
                            $sortType = SORT_NUMERIC;
                            break;
                        case 'boolean':
                            $sortedItems[$key] = $value ? 1 : 0;
                            $sortType          = SORT_NUMERIC;
                            break;
                        case 'array':
                            $sortedItems[$key] = json_encode($value);
                            $sortType          = SORT_STRING;
                            break;
                        case 'number':
                            $sortedItems[$key] = $value;
                            $sortType          = SORT_NUMERIC;
                            break;
                        default:
                            $sortedItems[$key] = $value;
                            $sortType          = SORT_REGULAR;
                    }
                }

                if (!empty($sortedItems)) {
                    array_multisort($sortedItems, ($column->getOrder() == 'asc') ? SORT_ASC : SORT_DESC, $sortType, $items);
                }

                break;
            }
        }

        $this->count = count($items);

        // Pagination
        if ($limit > 0) {
            $maxResults = ($maxResults !== null && ($maxResults - $page * $limit < $limit)) ? $maxResults - $page * $limit : $limit;

            $items = array_slice($items, $page * $limit, $maxResults);
        } elseif ($maxResults !== null) {
            $items = array_slice($items, 0, $maxResults);
        }

        $rows = new Rows();
        foreach ($items as $item) {
            $row = new Row();

            if ($this instanceof Vector) {
                $row->setPrimaryField($this->getId());
            }

            foreach ($item as $fieldName => $fieldValue) {
                if ($this instanceof Entity) {
                    if (in_array($fieldName, $serializeColumns)) {
                        if (is_string($fieldValue)) {
                            $fieldValue = unserialize($fieldValue);
                        }
                    }
                }

                $row->setField($fieldName, $fieldValue);
            }

            //call overridden prepareRow or associated closure
            if (($modifiedRow = $this->prepareRow($row)) != null) {
                $rows->addRow($modifiedRow);
            }
        }

        $this->items = $items;

        return $rows;
    }

    public function populateSelectFiltersFromData(Columns $columns, bool $loop = false): void
    {
        /* @var $column Column */
        foreach ($columns as $column) {
            $selectFrom = $column->getSelectFrom();

            if ($column->getFilterType() === 'select' && ($selectFrom === 'source' || $selectFrom === 'query')) {
                // For negative operators, show all values
                if ($selectFrom === 'query') {
                    foreach ($column->getFilters('vector') as $filter) {
                        if (in_array($filter->getOperator(), [Column::OPERATOR_NEQ, Column::OPERATOR_NLIKE, Column::OPERATOR_NSLIKE])) {
                            $selectFrom = 'source';
                            break;
                        }
                    }
                }

                // Dynamic from query or not ?
                $item = ($selectFrom === 'source') ? $this->data : $this->items;

                $values = [];
                foreach ($item as $row) {
                    $value = $row[$column->getField()];

                    switch ($column->getType()) {
                        case 'number':
                            $values[$value] = $column->getDisplayedValue($value);
                            break;
                        case 'datetime':
                        case 'date':
                        case 'time':
                            $displayedValue          = $column->getDisplayedValue($value);
                            $values[$displayedValue] = $displayedValue;
                            break;
                        case 'array':
                            if (is_string($value)) {
                                $value = unserialize($value);
                            }

                            foreach ($value as $val) {
                                $values[$val] = $val;
                            }
                            break;
                        default:
                            $values[$value] = $value;
                    }
                }

                // It avoids to have no result when the other columns are filtered
                if ($selectFrom === 'query' && empty($values) && $loop === false) {
                    $column->setSelectFrom('source');
                    $this->populateSelectFiltersFromData($columns, true);
                } else {
                    if ($column->getType() == 'array') {
                        natcasesort($values);
                    }

                    $values = $this->prepareColumnValues($column, $values);
                    $column->setValues(array_unique($values));
                }
            }
        }
    }

    public function getTotalCountFromData(?int $maxResults = null): int
    {
        return $maxResults === null ? $this->count : min($this->count, $maxResults);
    }

    protected function prepareStringForLikeCompare(string $input, ?string $type = null)
    {
        if ($type === 'array') {
            $outputString = str_replace(':{i:0;', ':{', serialize($input));
        } else {
            $outputString = $this->removeAccents($input);
        }

        return $outputString;
    }

    private function removeAccents(string $str): string
    {
        $entStr      = htmlentities($str, ENT_NOQUOTES, 'UTF-8');
        $noaccentStr = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $entStr);

        return preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $noaccentStr);
    }

    protected function prepareColumnValues(Column $column, array $values): array
    {
        $existingValues = $column->getValues();
        if (!empty($existingValues)) {
            $intersect = array_intersect_key($existingValues, $values);
            $values    = array_replace($values, $intersect);
        }

        return $values;
    }
}
