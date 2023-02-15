<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\DataGridBundle\Twig;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Row;
use Pagerfanta\Adapter\NullAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

/**
 * DataGrid Twig Extension.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * Updated by Nicolas Claverie <info@artscore-studio.fr>
 */
class DataGridExtension extends AbstractExtension implements GlobalsInterface
{
    public const DEFAULT_TEMPLATE = '@APYDataGrid/blocks.html.twig';

    /**
     * @var TemplateWrapper[]
     */
    private array $templates = [];

    private ?string $theme;

    private RouterInterface $router;

    private array $names;

    private array $params = [];

    private array $pagerFantaDefs;

    private string $defaultTemplate;

    public function __construct(RouterInterface $router, string $defaultTemplate)
    {
        $this->router          = $router;
        $this->defaultTemplate = $defaultTemplate;
    }

    public function setPagerFanta(array $def): self
    {
        $this->pagerFantaDefs = $def;

        return $this;
    }

    public function getGlobals(): array
    {
        return [
            'grid'           => null,
            'column'         => null,
            'row'            => null,
            'value'          => null,
            'submitOnChange' => null,
            'withjs'         => true,
            'pagerfanta'     => false,
            'op'             => 'eq',
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('grid', [$this, 'getGrid'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_html', [$this, 'getGridHtml'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_url', [$this, 'getGridUrl'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_filter', [$this, 'getGridFilter'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_column_operator', [$this, 'getGridColumnOperator'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_cell', [$this, 'getGridCell'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_search', [$this, 'getGridSearch'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_pager', [$this, 'getGridPager'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
            new TwigFunction('grid_pagerfanta', [$this, 'getPagerfanta'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('grid_*', [$this, 'getGrid_'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
        ];
    }

    public function initGrid(Grid $grid, ?string $theme = null, string|int $id = '', array $params = []): void
    {
        $this->theme     = $theme;
        $this->templates = [];

        $this->names[$grid->getHash()] = ($id == '') ? $grid->getId() : $id;
        $this->params                  = $params;
    }

    public function getGrid(Environment $environment, Grid $grid, ?string $theme = null, string|int $id = '', array $params = [], bool $withjs = true): string
    {
        $this->initGrid($grid, $theme, $id, $params);

        // For export
        $grid->setTemplate($theme);

        return $this->renderBlock($environment, 'grid', ['grid' => $grid, 'withjs' => $withjs]);
    }

    public function getGridHtml(Environment $environment, Grid $grid, ?string $theme = null, string|int $id = '', array $params = [])
    {
        return $this->getGrid($environment, $grid, $theme, $id, $params, false);
    }

    public function getGrid_(Environment $environment, string $name, Grid $grid): string
    {
        return $this->renderBlock($environment, 'grid_' . $name, ['grid' => $grid]);
    }

    public function getGridPager(Environment $environment, Grid $grid): string
    {
        return $this->renderBlock($environment, 'grid_pager', ['grid' => $grid, 'pagerfanta' => $this->pagerFantaDefs['enable']]);
    }

    public function getGridCell(Environment $environment, Column $column, Row $row, Grid $grid): string
    {
        $value = $column->renderCell($row->getField($column->getId()), $row, $this->router);

        $id = $this->names[$grid->getHash()];

        if (($id != '' && ($this->hasBlock($environment, $block = 'grid_' . $id . '_column_' . $column->getRenderBlockId() . '_cell')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_' . $column->getType() . '_cell')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_' . $column->getParentType() . '_cell')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_id_' . $column->getRenderBlockId() . '_cell')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_type_' . $column->getType() . '_cell')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_type_' . $column->getParentType() . '_cell')))
            || $this->hasBlock($environment, $block = 'grid_column_' . $column->getRenderBlockId() . '_cell')
            || $this->hasBlock($environment, $block = 'grid_column_' . $column->getType() . '_cell')
            || $this->hasBlock($environment, $block = 'grid_column_' . $column->getParentType() . '_cell')
            || $this->hasBlock($environment, $block = 'grid_column_id_' . $column->getRenderBlockId() . '_cell')
            || $this->hasBlock($environment, $block = 'grid_column_type_' . $column->getType() . '_cell')
            || $this->hasBlock($environment, $block = 'grid_column_type_' . $column->getParentType() . '_cell')
        ) {
            return $this->renderBlock($environment, $block, ['grid' => $grid, 'column' => $column, 'row' => $row, 'value' => $value]);
        }

        return $this->renderBlock($environment, 'grid_column_cell', ['grid' => $grid, 'column' => $column, 'row' => $row, 'value' => $value]);
    }

    public function getGridFilter(Environment $environment, Column $column, Grid $grid, bool $submitOnChange = true): string
    {
        $id = $this->names[$grid->getHash()];

        if (($id != '' && ($this->hasBlock($environment, $block = 'grid_' . $id . '_column_' . $column->getRenderBlockId() . '_filter')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_id_' . $column->getRenderBlockId() . '_filter')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_type_' . $column->getType() . '_filter')
                           || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_type_' . $column->getParentType() . '_filter'))
             || $this->hasBlock($environment, $block = 'grid_' . $id . '_column_filter_type_' . $column->getFilterType()))
            || $this->hasBlock($environment, $block = 'grid_column_' . $column->getRenderBlockId() . '_filter')
            || $this->hasBlock($environment, $block = 'grid_column_id_' . $column->getRenderBlockId() . '_filter')
            || $this->hasBlock($environment, $block = 'grid_column_type_' . $column->getType() . '_filter')
            || $this->hasBlock($environment, $block = 'grid_column_type_' . $column->getParentType() . '_filter')
            || $this->hasBlock($environment, $block = 'grid_column_filter_type_' . $column->getFilterType())
        ) {
            return $this->renderBlock($environment, $block, ['grid' => $grid, 'column' => $column, 'submitOnChange' => $submitOnChange && $column->isFilterSubmitOnChange()]);
        }

        return '';
    }

    public function getGridColumnOperator(Environment $environment, Column $column, Grid $grid, string $operator, bool $submitOnChange = true): string
    {
        return $this->renderBlock($environment, 'grid_column_operator', ['grid' => $grid, 'column' => $column, 'submitOnChange' => $submitOnChange, 'op' => $operator]);
    }

    /**
     * @param string                                 $section
     * @param \APY\DataGridBundle\Grid\Grid          $grid
     * @param \APY\DataGridBundle\Grid\Column\Column $param
     *
     * @return string|void
     */
    public function getGridUrl($section, Grid $grid, Column|string|int|null $param = null)
    {
        $prefix = $grid->getRouteUrl() . (strpos($grid->getRouteUrl(), '?') ? '&' : '?') . $grid->getHash() . '[';

        switch ($section) {
            case 'order':
                if ($param->isSorted()) {
                    return $prefix . Grid::REQUEST_QUERY_ORDER . ']=' . $param->getId() . '|' . ($param->getOrder() == 'asc' ? 'desc' : 'asc');
                } else {
                    return $prefix . Grid::REQUEST_QUERY_ORDER . ']=' . $param->getId() . '|' . $param->getDefaultOrder();
                }
            case 'page':
                return $prefix . Grid::REQUEST_QUERY_PAGE . ']=' . $param;
            case 'limit':
                return $prefix . Grid::REQUEST_QUERY_LIMIT . ']=';
            case 'reset':
                return $prefix . Grid::REQUEST_QUERY_RESET . ']=';
            case 'export':
                return $prefix . Grid::REQUEST_QUERY_EXPORT . ']=' . $param;
        }
    }

    public function getGridSearch(Environment $environment, Grid $grid, ?string $theme = null, string|int $id = '', array $params = []): string
    {
        $this->initGrid($grid, $theme, $id, $params);

        return $this->renderBlock($environment, 'grid_search', ['grid' => $grid]);
    }

    public function getPagerfanta(Grid $grid): string
    {
        $adapter = new NullAdapter($grid->getTotalCount());

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($grid->getLimit());
        $pagerfanta->setCurrentPage($grid->getPage() + 1);

        $url            = $this->getGridUrl('page', $grid, '');
        $routeGenerator = function ($page) use ($url) {
            return sprintf('%s%d', $url, $page - 1);
        };

        $view = new $this->pagerFantaDefs['view_class']();
        $html = $view->render($pagerfanta, $routeGenerator, $this->pagerFantaDefs['options']);

        return $html;
    }

    protected function renderBlock(Environment $environment, string $name, array $parameters): string
    {
        foreach ($this->getTemplates($environment) as $template) {
            if ($template->hasBlock($name, [])) {
                return $template->renderBlock($name, array_merge($environment->getGlobals(), $parameters, $this->params));
            }
        }

        throw new \InvalidArgumentException(sprintf('Block "%s" doesn\'t exist in grid template "%s".', $name, $this->theme));
    }

    protected function hasBlock(Environment $environment, string $name): bool
    {
        foreach ($this->getTemplates($environment) as $template) {
            /** @var $template TemplateWrapper */
            if ($template->hasBlock($name, [])) {
                return true;
            }
        }

        return false;
    }

    protected function getTemplates(Environment $environment): array
    {
        if (empty($this->templates)) {
            if ($this->theme instanceof TemplateWrapper) {
                $this->templates[] = $this->theme;
                $this->templates[] = $environment->load($this->defaultTemplate);
            } elseif (is_string($this->theme)) {
                $this->templates = $this->getTemplatesFromString($environment, $this->theme);
            } elseif ($this->theme === null) {
                $this->templates = $this->getTemplatesFromString($environment, $this->defaultTemplate);
            } else {
                throw new \Exception('Unable to load template');
            }
        }

        return $this->templates;
    }

    protected function getTemplatesFromString(Environment $environment, string $theme): array
    {
        $this->templates = [];

        $template          = $environment->load($theme);
        $this->templates[] = $template;

        return $this->templates;
    }
}
