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

use APY\DataGridBundle\Grid\Column\ArrayColumn;
use APY\DataGridBundle\Grid\Column\BooleanColumn;
use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Column\DateColumn;
use APY\DataGridBundle\Grid\Column\DateTimeColumn;
use APY\DataGridBundle\Grid\Column\NumberColumn;
use APY\DataGridBundle\Grid\Column\TextColumn;
use APY\DataGridBundle\Grid\Column\UntypedColumn;
use APY\DataGridBundle\Grid\Columns;
use APY\DataGridBundle\Grid\Helper\ColumnsIterator;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use APY\DataGridBundle\Grid\Rows;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Vector is really an Array.
 *
 * @author dellamowica
 */
class Vector extends Source
{
    protected array $data = [];

    protected string|array|null $id = null;

    protected array|Columns|ColumnsIterator $columns;

    public function __construct(array $data, array $columns = [])
    {
        if (!empty($data)) {
            $this->setData($data);
        }

        $this->setColumns($columns);
    }

    public function initialise(ManagerRegistry $doctrine, Manager $mapping): void
    {
        if (!empty($this->data)) {
            $this->guessColumns();
        }
    }

    protected function guessColumns(): void
    {
        $guessedColumns = [];
        $dataColumnIds  = array_keys(reset($this->data));

        foreach ($dataColumnIds as $id) {
            if (!$this->hasColumn($id)) {
                $params           = [
                    'id'         => $id,
                    'title'      => $id,
                    'source'     => true,
                    'filterable' => true,
                    'sortable'   => true,
                    'visible'    => true,
                    'field'      => $id,
                ];
                $guessedColumns[] = new UntypedColumn($params);
            }
        }

        $this->setColumns(array_merge($this->columns, $guessedColumns));

        // Guess on the first 10 rows only
        $iteration = min(10, count($this->data));

        foreach ($this->columns as $c) {
            if (!$c instanceof UntypedColumn) {
                continue;
            }

            $i          = 0;
            $fieldTypes = [];

            foreach ($this->data as $row) {
                if (!isset($row[$c->getId()])) {
                    continue;
                }

                $fieldValue = $row[$c->getId()];

                if ($fieldValue !== '' && $fieldValue !== null) {
                    if (is_array($fieldValue)) {
                        $fieldTypes['array'] = 1;
                    } elseif ($fieldValue instanceof \DateTime) {
                        if ($fieldValue->format('His') === '000000') {
                            $fieldTypes['date'] = 1;
                        } else {
                            $fieldTypes['datetime'] = 1;
                        }
                    } elseif (strlen($fieldValue) >= 3 && strtotime($fieldValue) !== false) {
                        $dt = new \DateTime($fieldValue);
                        if ($dt->format('His') === '000000') {
                            $fieldTypes['date'] = 1;
                        } else {
                            $fieldTypes['datetime'] = 1;
                        }
                    } elseif (true === $fieldValue || false === $fieldValue || 1 === $fieldValue || 0 === $fieldValue || '1' === $fieldValue || '0' === $fieldValue) {
                        $fieldTypes['boolean'] = 1;
                    } elseif (is_numeric($fieldValue)) {
                        $fieldTypes['number'] = 1;
                    } else {
                        $fieldTypes['text'] = 1;
                    }
                }

                if (++$i >= $iteration) {
                    break;
                }
            }

            if (count($fieldTypes) == 1) {
                $c->setType(key($fieldTypes));
            } elseif (isset($fieldTypes['boolean']) && isset($fieldTypes['number'])) {
                $c->setType('number');
            } elseif (isset($fieldTypes['date']) && isset($fieldTypes['datetime'])) {
                $c->setType('datetime');
            } else {
                $c->setType('text');
            }
        }
    }

    public function getColumns(Columns $columns): void
    {
        $token = empty($this->id); //makes the first column primary by default

        foreach ($this->columns as $c) {
            if ($c instanceof UntypedColumn) {
                switch ($c->getType()) {
                    case 'date':
                        $column = new DateColumn($c->getParams());
                        break;
                    case 'datetime':
                        $column = new DateTimeColumn($c->getParams());
                        break;
                    case 'boolean':
                        $column = new BooleanColumn($c->getParams());
                        break;
                    case 'number':
                        $column = new NumberColumn($c->getParams());
                        break;
                    case 'array':
                        $column = new ArrayColumn($c->getParams());
                        break;
                    case 'text':
                    default:
                        $column = new TextColumn($c->getParams());
                        break;
                }
            } else {
                $column = $c;
            }

            if (!$column->isPrimary()) {
                $column->setPrimary((is_array($this->id) && in_array($column->getId(), $this->id)) || $column->getId() == $this->id || $token);
            }

            $columns->addColumn($column);

            $token = false;
        }
    }

    public function execute(ColumnsIterator|Columns|array $columns, ?int $page = 0, ?int $limit = 0, ?int $maxResults = null, int $gridDataJunction = Column::DATA_CONJUNCTION): Rows|array
    {
        return $this->executeFromData($columns, $page, $limit, $maxResults);
    }

    public function populateSelectFilters(Columns|array $columns, bool $loop = false): void
    {
        $this->populateSelectFiltersFromData($columns, $loop);
    }

    public function getTotalCount(?int $maxResults = null): ?int
    {
        return $this->getTotalCountFromData($maxResults);
    }

    public function getHash(): ?string
    {
        return __CLASS__ . md5(
                implode(
                    '',
                    array_map(function ($c) {
                        return $c->getId();
                    }, $this->columns)
                )
            );
    }

    public function setId(string|array $id)
    {
        $this->id = $id;
    }

    public function getId(): string|array|null
    {
        return $this->id;
    }

    public function setData(array|object $data): self
    {
        $this->data = $data;

        if (!is_array($this->data) || empty($this->data)) {
            throw new \InvalidArgumentException('Data should be an array with content');
        }

        // This seems to exclude ...
        if (is_object(reset($this->data))) {
            foreach ($this->data as $key => $object) {
                $this->data[$key] = (array)$object;
            }
        }

        // ... this other (or vice versa)
        $firstRaw = reset($this->data);
        if (!is_array($firstRaw) || empty($firstRaw)) {
            throw new \InvalidArgumentException('Data should be a two-dimentional array');
        }

        return $this;
    }

    public function delete(array $ids): void
    {
    }

    protected function setColumns(Columns|array|ColumnsIterator $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    protected function hasColumn(string $id): bool
    {
        foreach ($this->columns as $c) {
            if ($id === $c->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getColumnsArray(): array
    {
        return $this->columns;
    }
}
