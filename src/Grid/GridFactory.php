<?php

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Exception\UnexpectedTypeException;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use APY\DataGridBundle\Grid\Source\Source;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\DataCollectorTranslator;
use Twig\Environment;

/**
 * Class GridFactory.
 *
 * @author  Quentin Ferrer
 */
class GridFactory implements GridFactoryInterface
{
    /**
     * @var GridRegistryInterface
     */
    private $registry;

    private $authorizationChecker;

    private $router;

    private $requestStack;

    private $twig;

    private $httpKernel;

    private $doctrine;

    private $mapping;

    private $kernel;

    private $translator;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        RequestStack $requestStack,
        Environment $twig,
        HttpKernelInterface $httpKernel,
        ManagerRegistry $doctrine,
        Manager $mapping,
        KernelInterface $kernel,
        DataCollectorTranslator $translator,
        GridRegistryInterface $registry
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->router               = $router;
        $this->requestStack         = $requestStack;
        $this->twig                 = $twig;
        $this->httpKernel           = $httpKernel;
        $this->registry             = $registry;
        $this->doctrine             = $doctrine;
        $this->mapping              = $mapping;
        $this->kernel               = $kernel;
        $this->translator           = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function create($type = null, Source $source = null, array $options = [])
    {
        return $this->createBuilder($type, $source, $options)->getGrid();
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilder($type = 'grid', Source $source = null, array $options = [])
    {
        $type    = $this->resolveType($type);
        $options = $this->resolveOptions($type, $source, $options);

        $builder = new GridBuilder(
            $this->authorizationChecker,
            $this->router,
            $this->requestStack,
            $this->twig,
            $this->httpKernel,
            $this->doctrine,
            $this->mapping,
            $this->kernel,
            $this->translator,
            $this,
            $type->getName(),
            $options
        );

        $builder->setType($type);

        $type->buildGrid($builder, $options);

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function createColumn($name, $type, array $options = [])
    {
        if (!$type instanceof Column) {
            if (!is_string($type)) {
                throw new UnexpectedTypeException($type, 'string, APY\DataGridBundle\Grid\Column\Column');
            }

            $column = clone $this->registry->getColumn($type);

            $column->__initialize(
                array_merge([
                                'id' => $name,
                                                                                                                                      'title' => $name,
                                                                                                                                                                          'field' => $name,
                                                                                                                                                                                            'source' => true,
                            ], $options)
            );
        } else {
            $column = $type;
            $column->setId($name);
        }

        return $column;
    }

    /**
     * Returns an instance of type.
     *
     * @param string|GridTypeInterface $type The type of the grid
     *
     * @return GridTypeInterface
     */
    private function resolveType($type)
    {
        if (!$type instanceof GridTypeInterface) {
            if (!is_string($type)) {
                throw new UnexpectedTypeException($type, 'string, APY\DataGridBundle\Grid\GridTypeInterface');
            }

            $type = $this->registry->getType($type);
        }

        return $type;
    }

    /**
     * Returns the options resolved.
     *
     * @param GridTypeInterface $type
     * @param Source            $source
     * @param array             $options
     *
     * @return array
     */
    private function resolveOptions(GridTypeInterface $type, Source $source = null, array $options = [])
    {
        $resolver = new OptionsResolver();

        $type->configureOptions($resolver);

        if (null !== $source && !isset($options['source'])) {
            $options['source'] = $source;
        }

        $options = $resolver->resolve($options);

        return $options;
    }
}
