<?php

namespace APY\DataGridBundle\Grid;

use App\Kernel;
use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Exception\InvalidArgumentException;
use APY\DataGridBundle\Grid\Exception\UnexpectedTypeException;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\DataCollectorTranslator;
use Twig\Environment;

/**
 * A builder for creating Grid instances.
 *
 * @author  Quentin Ferrer
 */
class GridBuilder extends GridConfigBuilder implements GridBuilderInterface
{
    /**
     * The container.
     *
     * @var Container
     */
    private $container;

    private $authorizationChecker;

    private $router;

    private $requestStack;

    private $twig;

    private $httpKernel;

    private $doctrine;

    private $mapping;

    private $kernel;

    private $translator;

    /**
     * The factory.
     *
     * @var GridFactoryInterface
     */
    private $factory;

    /**
     * Columns of the grid builder.
     *
     * @var Column[]
     */
    private $columns = [];

    /**
     * Constructor.
     *
     * @param Container $container          The service container
     * @param GridFactoryInterface $factory The grid factory
     * @param string $name                  The name of the grid
     * @param array $options                The options of the grid
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        RequestStack $requestStack,
        Environment $twig,
        HttpKernelInterface $httpKernel,
        Registry $doctrine,
        Manager $mapping,
        Kernel $kernel,
        DataCollectorTranslator $translator,
        // Container $container,
        GridFactoryInterface $factory,
        $name,
        array $options = []
    ) {
        parent::__construct($name, $options);

        // $this->container            = $container;
        $this->authorizationChecker = $authorizationChecker;
        $this->router               = $router;
        $this->requestStack         = $requestStack;
        $this->twig                 = $twig;
        $this->httpKernel           = $httpKernel;
        $this->doctrine             = $doctrine;
        $this->mapping              = $mapping;
        $this->factory              = $factory;
        $this->kernel               = $kernel;
        $this->translator           = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function add($name, $type, array $options = [])
    {
        if (!$type instanceof Column) {
            if (!is_string($type)) {
                throw new UnexpectedTypeException($type, 'string, APY\DataGridBundle\Grid\Column\Column');
            }

            $type = $this->factory->createColumn($name, $type, $options);
        }

        $this->columns[$name] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('The column with the name "%s" does not exist.', $name));
        }

        $column = $this->columns[$name];

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        unset($this->columns[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGrid()
    {
        $config = $this->getGridConfig();

        $grid = new Grid(
            $this->authorizationChecker,
            $this->router,
            $this->requestStack,
            $this->twig,
            $this->httpKernel,
            $this->doctrine,
            $this->mapping,
            $this->kernel,
            $this->translator,
            $config->getName(),
            $config
        );

        foreach ($this->columns as $column) {
            $grid->addColumn($column);
        }

        if (!empty($this->actions)) {
            foreach ($this->actions as $columnId => $actions) {
                foreach ($actions as $action) {
                    $grid->addRowAction($action);
                }
            }
        }

        $grid->initialize();

        return $grid;
    }
}
