<?php

namespace APY\DataGridBundle\Grid\Exception;

/**
 * Class TypeNotFoundException.
 *
 * @author  Quentin Ferrer
 */
class TypeNotFoundException extends \InvalidArgumentException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('The type of grid "%s" not found', $name));
    }
}
