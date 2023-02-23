<?php

namespace APY\DataGridBundle\Tests\Grid;

use APY\DataGridBundle\Grid\Column\TextColumn;
use APY\DataGridBundle\Grid\Exception\UnexpectedTypeException;
use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\GridBuilder;
use APY\DataGridBundle\Grid\GridBuilderInterface;
use APY\DataGridBundle\Grid\GridFactory;
use APY\DataGridBundle\Grid\GridRegistryInterface;
use APY\DataGridBundle\Grid\GridTypeInterface;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use APY\DataGridBundle\Grid\Type\GridType;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class GridFactoryTest.
 */
class GridFactoryTest extends TestCase
{
    private MockObject $registry;

    private MockObject $builder;

    private GridFactory $factory;

    protected function setUp(): void
    {
        $auth           = $this->createMock(AuthorizationCheckerInterface::class);
        $router         = $this->createMock(RouterInterface::class);
        $requestStack   = $this->createMock(RequestStack::class);
        $twig           = $this->createMock(Environment::class);
        $httpKernel     = $this->createMock(HttpKernelInterface::class);
        $registry       = $this->createMock(ManagerRegistry::class);
        $manager        = $this->createMock(Manager::class);
        $kernel         = $this->createMock(KernelInterface::class);
        $translator     = $this->createMock(TranslatorInterface::class);
        $this->registry = $this->createMock(GridRegistryInterface::class);
        $this->builder  = $this->createMock(GridBuilderInterface::class);

        $this->factory = new GridFactory($auth, $router, $requestStack, $twig, $httpKernel, $registry, $manager, $kernel, $translator, $this->registry);
    }

    public function testCreateWithUnexpectedType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->factory->create(1234);
        $this->factory->create(['foo']);
        $this->factory->create(new \stdClass());
    }

    public function testCreateWithTypeString(): void
    {
        $this->registry->expects($this->once())
                       ->method('getType')
                       ->with('foo')
                       ->willReturn($this->createMock(GridTypeInterface::class));

        $this->assertInstanceOf(Grid::class, $this->factory->create('foo'));
    }

    public function testCreateWithTypeObject(): void
    {
        $this->registry->expects($this->never())->method('getType');

        $this->assertInstanceOf(Grid::class, $this->factory->create(new GridType()));
    }

    public function testCreateBuilderWithDefaultType(): void
    {
        $defaultType = new GridType();

        $this->registry->expects($this->once())
                       ->method('getType')
                       ->with('grid')
                       ->willReturn($defaultType);

        $builder = $this->factory->createBuilder();

        $this->assertSame($defaultType, $builder->getType());
    }

    public function testCreateBuilder(): void
    {
        $givenOptions    = ['a' => 1, 'b' => 2];
        $resolvedOptions = ['a' => 1, 'b' => 2, 'c' => 3];

        $type = $this->createMock(GridTypeInterface::class);

        $type->expects($this->once())
             ->method('getName')
             ->willReturn('TYPE');

        $type->expects($this->once())
             ->method('configureOptions')
             ->with(
                 $this->callback(function ($resolver) use ($resolvedOptions) {
                     if (!$resolver instanceof OptionsResolver) {
                         return false;
                     }

                     $resolver->setDefaults($resolvedOptions);

                     return true;
                 })
             );

        $type->expects($this->once())
             ->method('buildGrid')
             ->with(
                 $this->callback(function ($builder) {
                     return $builder instanceof GridBuilder && $builder->getName() == 'TYPE';
                 }),
                 $resolvedOptions
             );

        $builder = $this->factory->createBuilder($type, null, $givenOptions);

        $this->assertInstanceOf(GridBuilderInterface::class, $builder);
        $this->assertSame($type, $builder->getType());
        $this->assertSame('TYPE', $builder->getName());
        $this->assertEquals($resolvedOptions, $builder->getOptions());
        $this->assertNull($builder->getSource());
    }

    public function testCreateColumnWithUnexpectedType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->factory->createColumn('foo', 1234);
    }

    public function testCreateColumnWithTypeString(): void
    {
        $expectedColumn = new TextColumn();

        $this->registry->expects($this->once())
                       ->method('getColumn')
                       ->with('text')
                       ->willReturn($expectedColumn);

        $column = $this->factory->createColumn('foo', 'text', ['title' => 'bar']);

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertEquals('text', $column->getType());
        $this->assertEquals('foo', $column->getId());
        $this->assertEquals('bar', $column->getTitle());
        $this->assertEquals('foo', $column->getField());
        $this->assertTrue($column->isVisibleForSource());
    }

    public function testCreateColumnWithObject(): void
    {
        $column = $this->factory->createColumn('foo', new TextColumn(), ['title' => 'bar']);

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertEquals('text', $column->getType());
        $this->assertEquals('foo', $column->getId());
        $this->assertEmpty($column->getTitle());
        $this->assertNull($column->getField());
        $this->assertFalse($column->isVisibleForSource());
    }
}
