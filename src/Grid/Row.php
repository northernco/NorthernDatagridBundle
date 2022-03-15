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
use InvalidArgumentException;

class Row
{
    /** @var array */
    protected array $fields;

    /** @var string */
    protected string $class;

    /** @var string */
    protected string $color;

    /** @var string|null */
    protected ?string $legend;

    /** @var mixed */
    protected $primaryField;

    /** @var mixed */
    protected $entity;

    /** @var EntityRepository|null */
    protected ?EntityRepository $repository = null;

    public function __construct()
    {
        $this->fields = [];
        $this->color = '';
    }

    /**
     * @param EntityRepository $repository
     */
    public function setRepository(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getRepository(): ?EntityRepository
    {
        return $this->repository;
    }

    /**
     * @return null|object
     */
    public function getEntity(): ?object
    {
        $primaryKeyValue = current($this->getPrimaryKeyValue());

        return $this->repository->find($primaryKeyValue);
    }

    /**
     * @return array
     */
    public function getPrimaryKeyValue(): array
    {
        $primaryFieldValue = $this->getPrimaryFieldValue();

        if (is_array($primaryFieldValue)) {
            return $primaryFieldValue;
        }

        // @todo: is that correct? shouldn't be [$this->primaryField => $primaryFieldValue] ??
        return ['id' => $primaryFieldValue];
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array|mixed
     */
    public function getPrimaryFieldValue()
    {
        if (null === $this->primaryField) {
            throw new InvalidArgumentException('Primary column must be defined');
        }

        if (is_array($this->primaryField)) {
            return array_intersect_key($this->fields, array_flip($this->primaryField));
        }

        if (!isset($this->fields[$this->primaryField])) {
            throw new InvalidArgumentException('Primary field not added to fields');
        }

        return $this->fields[$this->primaryField];
    }

    /**
     * @param mixed $primaryField
     *
     * @return $this
     */
    public function setPrimaryField($primaryField): Row
    {
        $this->primaryField = $primaryField;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrimaryField()
    {
        return $this->primaryField;
    }

    /**
     * @param mixed $columnId
     * @param mixed $value
     *
     * @return $this
     */
    public function setField($columnId, $value): Row
    {
        $this->fields[$columnId] = $value;

        return $this;
    }

    /**
     * @param mixed $columnId
     *
     * @return mixed
     */
    public function getField($columnId)
    {
        return $this->fields[$columnId] ?? '';
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    public function setClass(string $class): Row
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $color
     *
     * @return $this
     */
    public function setColor(string $color): Row
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $legend
     *
     * @return $this
     */
    public function setLegend(string $legend): Row
    {
        $this->legend = $legend;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLegend(): ?string
    {
        return $this->legend;
    }
}
