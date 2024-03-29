<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Exception\InvalidArgumentException;
use APY\DataGridBundle\Grid\Exception\UnexpectedTypeException;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * A builder for creating Grid instances.
 *
 * @author  Quentin Ferrer
 */
class GridBuilder extends GridConfigBuilder implements GridBuilderInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;

    private RouterInterface $router;

    private RequestStack $requestStack;

    private Environment $twig;

    private HttpKernelInterface $httpKernel;

    private ManagerRegistry $doctrine;

    private Manager $mapping;

    private KernelInterface $kernel;

    private TranslatorInterface $translator;

    private GridFactoryInterface $factory;

    private array $columns = [];

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        RequestStack $requestStack,
        Environment $twig,
        HttpKernelInterface $httpKernel,
        ManagerRegistry $doctrine,
        Manager $mapping,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        GridFactoryInterface $factory,
        string $name,
        array $options = []
    ) {
        parent::__construct($name, $options);

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
    public function add(string $name, string|array|Column $type, array $options = []): self
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
    public function get(string $name): Column
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
    public function has(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): self
    {
        unset($this->columns[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGrid(): Grid
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
