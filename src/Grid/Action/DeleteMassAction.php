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

namespace APY\DataGridBundle\Grid\Action;

class DeleteMassAction extends MassAction
{
    public function __construct(bool $confirm = false)
    {
        parent::__construct('Delete', 'static::deleteAction', $confirm);
    }
}
