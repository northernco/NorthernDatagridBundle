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

class Filter
{
    private mixed $value;

    private string $operator;

    private ?string $columnName;

    /**
     * @param string      $operator
     * @param mixed|null  $value
     * @param string|null $columnName
     */
    public function __construct(
        string $operator,
        mixed $value = null,
        ?string $columnName = null
    ) {
        $this->value      = $value;
        $this->operator   = $operator;
        $this->columnName = $columnName;
    }

    public function setOperator(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function hasColumnName(): bool
    {
        return $this->columnName !== null;
    }

    public function setColumnName(string $columnName): self
    {
        $this->columnName = $columnName;

        return $this;
    }

    public function getColumnName(): ?string
    {
        return $this->columnName;
    }
}
