<?php

namespace APY\DataGridBundle\Tests\Grid;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Exception\InvalidArgumentException;
use APY\DataGridBundle\Grid\Exception\UnexpectedTypeException;
use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\GridBuilder;
use APY\DataGridBundle\Grid\GridFactoryInterface;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\DataCollectorTranslator;
use Twig\Environment;

/**
 * Class GridBuilderTest.
 */
class GridBuilderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;

    /**
     * @var GridBuilder
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $auth         = $this->createMock(AuthorizationCheckerInterface::class);
        $router       = $this->createMock(RouterInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $twig         = $this->createMock(Environment::class);
        $httpKernel   = $this->createMock(HttpKernelInterface::class);
        $registry     = $this->createMock(ManagerRegistry::class);
        $manager      = $this->createMock(Manager::class);
        $kernel       = $this->createMock(KernelInterface::class);
        $translator   = $this->createMock(DataCollectorTranslator::class);

        $this->factory = $this->createMock(GridFactoryInterface::class);
        $this->builder = new GridBuilder($auth, $router, $requestStack, $twig, $httpKernel, $registry, $manager, $kernel, $translator, $this->factory, 'name');
    }

    protected function tearDown(): void
    {
        $this->factory = null;
        $this->builder = null;
    }

    public function testAddUnexpectedType()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->builder->add('foo', 123);
        $this->builder->add('foo', ['test']);
    }

    public function testAddColumnTypeString()
    {
        $this->assertFalse($this->builder->has('foo'));

        $this->factory->expects($this->once())
                      ->method('createColumn')
                      ->with('foo', 'text', [])
                      ->willReturn($this->createMock(Column::class));

        $this->builder->add('foo', 'text');

        $this->assertTrue($this->builder->has('foo'));
    }

    public function testAddColumnType()
    {
        $this->factory->expects($this->never())->method('createColumn');

        $this->assertFalse($this->builder->has('foo'));
        $this->builder->add('foo', $this->createMock(Column::class));
        $this->assertTrue($this->builder->has('foo'));
    }

    public function testAddIsFluent()
    {
        $builder = $this->builder->add('name', 'text', ['key' => 'value']);
        $this->assertSame($builder, $this->builder);
    }

    public function testGetUnknown()
    {
        $this->expectException(
            InvalidArgumentException::class,
            'The column with the name "foo" does not exist.'
        );

        $this->builder->get('foo');
    }

    public function testGetExplicitColumnType()
    {
        $expectedColumn = $this->createMock(Column::class);

        $this->factory->expects($this->once())
                      ->method('createColumn')
                      ->with('foo', 'text', [])
                      ->willReturn($expectedColumn);

        $this->builder->add('foo', 'text');

        $column = $this->builder->get('foo');

        $this->assertSame($expectedColumn, $column);
    }

    public function testHasColumnType()
    {
        $this->factory->expects($this->once())
                      ->method('createColumn')
                      ->with('foo', 'text', [])
                      ->willReturn($this->createMock(Column::class));

        $this->builder->add('foo', 'text');

        $this->assertTrue($this->builder->has('foo'));
    }

    public function assertHasNotColumnType()
    {
        $this->assertFalse($this->builder->has('foo'));
    }

    public function testRemove()
    {
        $this->factory->expects($this->once())
                      ->method('createColumn')
                      ->with('foo', 'text', [])
                      ->willReturn($this->createMock(Column::class));

        $this->builder->add('foo', 'text');

        $this->assertTrue($this->builder->has('foo'));
        $this->builder->remove('foo');
        $this->assertFalse($this->builder->has('foo'));
    }

    public function testRemoveIsFluent()
    {
        $builder = $this->builder->remove('foo');
        $this->assertSame($builder, $this->builder);
    }

    public function testGetGrid()
    {
        $this->assertInstanceOf(Grid::class, $this->builder->getGrid());
    }
}
