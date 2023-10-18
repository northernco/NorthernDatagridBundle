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

use APY\DataGridBundle\Grid\Grid;

/**
 * PHPExcel 5 Export (97-2003) (.xls)
 * 52 columns maximum.
 */
class PHPExcel5Export extends Export
{
    protected ?string $fileExtension = 'xls';

    protected string $mimeType = 'application/vnd.ms-excel';

    public \PHPExcel $objPHPExcel;

    public function __construct(
        string $title,
        string $fileName = 'export',
        array $params = [],
        string $charset = 'UTF-8'
    ) {
        $this->objPHPExcel = new \PHPExcel();

        parent::__construct($title, $fileName, $params, $charset);
    }

    public function computeData(Grid $grid): void
    {
        $data = $this->getFlatGridData($grid);

        $row = 1;
        foreach ($data as $line) {
            $column = 'A';
            foreach ($line as $cell) {
                $this->objPHPExcel->getActiveSheet()->SetCellValue($column . $row, $cell);

                ++$column;
            }
            ++$row;
        }

        $objWriter = $this->getWriter();

        ob_start();

        $objWriter->save('php://output');

        $this->content = ob_get_contents();

        ob_end_clean();
    }

    protected function getWriter(): mixed
    {
        return new \PHPExcel_Writer_Excel5($this->objPHPExcel);
    }
}
