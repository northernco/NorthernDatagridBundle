<?php

namespace APY\DataGridBundle\Tests\Grid\Action;

use APY\DataGridBundle\Grid\Action\MassAction;
use PHPUnit\Framework\TestCase;

class MassActionTest extends TestCase
{
    /** @var MassAction */
    private MassAction $massAction;

    /** @var string */
    private string $title = 'foo';

    /** @var string */
    private string $callback = 'static::massAction';

    /** @var bool */
    private bool $confirm = true;

    /** @var array */
    private array $parameters = ['foo' => 'foo', 'bar' => 'bar'];

    /** @var string */
    private string $role = 'ROLE_FOO';

    public function setUp(): void
    {
        $this->massAction = new MassAction($this->title, $this->callback, $this->confirm, $this->parameters, $this->role);
    }

    public function testMassActionConstruct()
    {
        $this->assertEquals($this->title, $this->massAction->getTitle());
        $this->assertEquals($this->callback, $this->massAction->getCallback());
        $this->assertEquals($this->confirm, $this->massAction->getConfirm());
        $this->assertEquals($this->parameters, $this->massAction->getParameters());
        $this->assertEquals($this->role, $this->massAction->getRole());
    }

    public function testSetTile()
    {
        $title = 'bar';
        $this->massAction->setTitle($title);

        $this->assertEquals($title, $this->massAction->getTitle());
    }

    public function testGetTitle()
    {
        $title = 'foobar';
        $this->massAction->setTitle($title);

        $this->assertEquals($title, $this->massAction->getTitle());
    }

    public function testSetCallback()
    {
        $callback = 'self::fooMassAction';
        $this->massAction->setCallback($callback);

        $this->assertEquals($callback, $this->massAction->getCallback());
    }

    public function testGetCallback()
    {
        $callback = 'self::barMassAction';
        $this->massAction->setCallback($callback);

        $this->assertEquals($callback, $this->massAction->getCallback());
    }

    public function testSetConfirm()
    {
        $confirm = false;
        $this->massAction->setConfirm($confirm);

        $this->assertFalse($this->massAction->getConfirm());
    }

    public function testGetConfirm()
    {
        $confirm = false;
        $this->massAction->setConfirm($confirm);

        $this->assertFalse($this->massAction->getConfirm());
    }

    public function testDefaultConfirmMessage()
    {
        $this->assertIsString($this->massAction->getConfirmMessage());
    }

    public function testSetConfirmMessage()
    {
        $message = 'A foo test message';
        $this->massAction->setConfirmMessage($message);

        $this->assertEquals($message, $this->massAction->getConfirmMessage());
    }

    public function testGetConfirmMessage()
    {
        $message = 'A bar test message';
        $this->massAction->setConfirmMessage($message);

        $this->assertEquals($message, $this->massAction->getConfirmMessage());
    }

    public function testSetParameters()
    {
        $params = [1 => 1, 2 => 2];
        $this->massAction->setParameters($params);

        $this->assertEquals($params, $this->massAction->getParameters());
    }

    public function testGetParameters()
    {
        $params = [1, 2, 3];
        $this->massAction->setParameters($params);

        $this->assertEquals($params, $this->massAction->getParameters());
    }

    public function testSetRole()
    {
        $role = 'ROLE_ADMIN';
        $this->massAction->setRole($role);

        $this->assertEquals($role, $this->massAction->getRole());
    }

    public function testGetRole()
    {
        $role = 'ROLE_SUPER_ADMIN';
        $this->massAction->setRole($role);

        $this->assertEquals($role, $this->massAction->getRole());
    }
}
