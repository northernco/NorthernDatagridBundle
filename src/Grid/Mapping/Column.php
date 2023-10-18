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

namespace APY\DataGridBundle\Grid\Mapping;

/**
 * @Annotation
 */
class Column
{
    private array|string $metadata;

    private array $groups;

    public function __construct(array|string $metadata)
    {
        $this->metadata = $metadata;
        $this->groups   = isset($metadata['groups']) ? (array)$metadata['groups'] : ['default'];
    }

    public function getMetadata(): array|string
    {
        return $this->metadata;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
