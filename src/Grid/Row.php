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

use Doctrine\ORM\EntityRepository;

class Row
{
    private array $fields;

    private ?string $class = null;

    private string $color;

    private ?string $legend;

    private string|array|null $primaryField = null;

    private ?EntityRepository $repository;

    public function __construct()
    {
        $this->fields = [];
        $this->color  = '';
    }

    public function setRepository(EntityRepository $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function getRepository(): ?EntityRepository
    {
        return $this->repository;
    }

    public function getEntity(): ?object
    {
        $primaryKeyValue = current($this->getPrimaryKeyValue());

        return $this->repository->find($primaryKeyValue);
    }

    public function getPrimaryKeyValue(): array
    {
        $primaryFieldValue = $this->getPrimaryFieldValue();

        if (is_array($primaryFieldValue)) {
            return $primaryFieldValue;
        }

        // @todo: is that correct? shouldn't be [$this->primaryField => $primaryFieldValue] ??
        return ['id' => $primaryFieldValue];
    }

    public function getPrimaryFieldValue(): mixed
    {
        if (null === $this->primaryField) {
            throw new \InvalidArgumentException('Primary column must be defined');
        }

        if (is_array($this->primaryField)) {
            return array_intersect_key($this->fields, array_flip($this->primaryField));
        }

        if (!isset($this->fields[$this->primaryField])) {
            throw new \InvalidArgumentException('Primary field not added to fields');
        }

        return $this->fields[$this->primaryField];
    }

    public function setPrimaryField(string|array|null $primaryField): self
    {
        $this->primaryField = $primaryField;

        return $this;
    }

    public function getPrimaryField(): string|array|null
    {
        return $this->primaryField;
    }

    /**
     * @param string|int $columnId
     * @param mixed      $value
     *
     * @return $this
     */
    public function setField(string|int $columnId, mixed $value): self
    {
        $this->fields[$columnId] = $value;

        return $this;
    }

    public function getField(string|int $columnId): mixed
    {
        return isset($this->fields[$columnId]) ? $this->fields[$columnId] : '';
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setLegend(string $legend): self
    {
        $this->legend = $legend;

        return $this;
    }

    public function getLegend(): ?string
    {
        return $this->legend;
    }
}
