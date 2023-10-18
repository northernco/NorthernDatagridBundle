<?php

namespace APY\DataGridBundle\Tests\Grid\Action;

use APY\DataGridBundle\Grid\Action\MassAction;
use PHPUnit\Framework\TestCase;

class MassActionTest extends TestCase
{
    private MassAction $massAction;

    private string $title = 'foo';

    private string $callback = 'static::massAction';

    private bool $confirm = true;

    private array $parameters = ['foo' => 'foo', 'bar' => 'bar'];

    private string $role = 'ROLE_FOO';

    public function setUp(): void
    {
        $this->massAction = new MassAction($this->title, $this->callback, $this->confirm, $this->parameters, $this->role);
    }

    public function testMassActionConstruct(): void
    {
        $this->assertSame($this->title, $this->massAction->getTitle());
        $this->assertSame($this->callback, $this->massAction->getCallback());
        $this->assertSame($this->confirm, $this->massAction->getConfirm());
        $this->assertSame($this->parameters, $this->massAction->getParameters());
        $this->assertSame($this->role, $this->massAction->getRole());
    }

    public function testGetTitle(): void
    {
        $title = 'foobar';
        $this->massAction->setTitle($title);

        $this->assertEquals($title, $this->massAction->getTitle());
    }

    public function testGetCallback(): void
    {
        $callback = 'self::barMassAction';
        $this->massAction->setCallback($callback);

        $this->assertEquals($callback, $this->massAction->getCallback());
    }

    public function testGetConfirm(): void
    {
        $confirm = false;
        $this->massAction->setConfirm($confirm);

        $this->assertFalse($this->massAction->getConfirm());
    }

    public function testDefaultConfirmMessage(): void
    {
        $this->assertIsString($this->massAction->getConfirmMessage());
    }

    public function testGetConfirmMessage(): void
    {
        $message = 'A bar test message';
        $this->massAction->setConfirmMessage($message);

        $this->assertEquals($message, $this->massAction->getConfirmMessage());
    }

    public function testGetParameters(): void
    {
        $params = [1, 2, 3];
        $this->massAction->setParameters($params);

        $this->assertEquals($params, $this->massAction->getParameters());
    }

    public function testGetRole(): void
    {
        $role = 'ROLE_SUPER_ADMIN';
        $this->massAction->setRole($role);

        $this->assertEquals($role, $this->massAction->getRole());
    }
}
