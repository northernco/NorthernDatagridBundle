<?php

use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
                            PHPUnitSetList::PHPUNIT_90,
                        ]);
};
