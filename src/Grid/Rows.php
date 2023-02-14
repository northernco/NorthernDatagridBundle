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

class Rows implements \IteratorAggregate, \Countable
{
    protected \SplObjectStorage $rows;

    public function __construct(array $rows = [])
    {
        $this->rows = new \SplObjectStorage();

        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator(): \Traversable
    {
        return $this->rows;
    }

    public function addRow(Row $row): self
    {
        $this->rows->attach($row);

        return $this;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Countable::count()
     */
    public function count(): int
    {
        return $this->rows->count();
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), true);
    }
}
