<?php

namespace APY\DataGridBundle\Tests\Grid\Action;

use APY\DataGridBundle\Grid\Action\DeleteMassAction;
use PHPUnit\Framework\TestCase;

class DeleteMassActionTest extends TestCase
{
    public function testConstructWithConfirmation(): void
    {
        $ma = new DeleteMassAction(true);
        $this->assertSame(true, $ma->getConfirm());
    }

    public function testConstructWithoutConfirmation(): void
    {
        $ma = new DeleteMassAction();
        $this->assertSame(false, $ma->getConfirm());
    }
}
