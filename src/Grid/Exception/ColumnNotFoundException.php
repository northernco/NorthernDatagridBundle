<?php

namespace APY\DataGridBundle\Grid\Exception;

/**
 * Class ColumnNotFoundException.
 *
 * @author  Quentin Ferrer
 */
class ColumnNotFoundException extends \InvalidArgumentException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('The type of column "%s" not found', $name));
    }
}
