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

namespace APY\DataGridBundle\Grid\Export;

/**
 * Tab-Separated Values.
 */
class TSVExport extends DSVExport
{
    protected ?string $fileExtension = 'tsv';

    protected string $mimeType = 'text/tab-separated-values';

    protected string $delimiter = "\t";
}
