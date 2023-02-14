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
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class GridFactory.
 *
 * @author  Quentin Ferrer
 */
class GridFactory implements GridFactoryInterface
{
    private GridRegistryInterface $registry;

    private AuthorizationCheckerInterface $authorizationChecker;

    private RouterInterface $router;

    private RequestStack $requestStack;

    private Environment $twig;

    private HttpKernelInterface $httpKernel;

    private ManagerRegistry $doctrine;

    private Manager $mapping;

    private KernelInterface $kernel;

    private TranslatorInterface $translator;

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
    public function create(string|GridTypeInterface|null $type = null, ?Source $source = null, array $options = []): Grid
    {
        return $this->createBuilder($type, $source, $options)->getGrid();
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilder(string|GridTypeInterface|null $type = 'grid', ?Source $source = null, array $options = []): GridBuilder
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
    public function createColumn(string $name, string|Column $type, array $options = []): Column
    {
        if (!$type instanceof Column) {
            if (!is_string($type)) {
                throw new UnexpectedTypeException($type, 'string, APY\DataGridBundle\Grid\Column\Column');
            }

            $column = clone $this->registry->getColumn($type);

            $column->__initialize(
                array_merge(
                    [
                        'id'     => $name,
                        'title'  => $name,
                        'field'  => $name,
                        'source' => true,
                    ],
                    $options
                )
            );
        } else {
            $column = $type;
            $column->setId($name);
        }

        return $column;
    }

    private function resolveType(string|GridTypeInterface $type): GridTypeInterface
    {
        if (!$type instanceof GridTypeInterface) {
            if (!is_string($type)) {
                throw new UnexpectedTypeException($type, 'string, APY\DataGridBundle\Grid\GridTypeInterface');
            }

            $type = $this->registry->getType($type);
        }

        return $type;
    }

    private function resolveOptions(GridTypeInterface $type, ?Source $source = null, array $options = []): array
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
