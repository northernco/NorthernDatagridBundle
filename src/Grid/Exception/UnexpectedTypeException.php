<?php

namespace APY\DataGridBundle\Grid\Exception;

/**
 * Class UnexpectedTypeException.
 *
 * @author  Quentin Ferrer
 */
class UnexpectedTypeException extends \InvalidArgumentException
{
    public function __construct(string $value, int $expectedType)
    {
        parent::__construct(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                $expectedType,
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}
