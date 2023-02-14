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

interface MassActionInterface
{
    /**
     * get action title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * get action callback.
     *
     * @return string
     */
    public function getCallback(): ?string;

    /**
     * get action confirm.
     *
     * @return bool
     */
    public function getConfirm(): bool;

    /**
     * get action confirmMessage.
     *
     * @return bool
     */
    public function getConfirmMessage(): string;

    /**
     * get additional parameters.
     *
     * @return array
     */
    public function getParameters(): array;
}
