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

namespace APY\DataGridBundle\Grid;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class GridManager implements \IteratorAggregate, \Countable
{
    private ContainerInterface $container;

    private \SplObjectStorage $grids;

    private ?string $routeUrl = null;

    private ?Grid $exportGrid = null;

    private ?Grid $massActionGrid = null;

    public const NO_GRID_EX_MSG = 'No grid has been added to the manager.';

    public const SAME_GRID_HASH_EX_MSG = 'Some grids seem similar. Please set an Indentifier for your grids.';

    public function __construct($container)
    {
        $this->container = $container;
        $this->grids     = new \SplObjectStorage();
    }

    public function getIterator(): \Traversable
    {
        return $this->grids;
    }

    public function count(): int
    {
        return $this->grids->count();
    }

    public function createGrid(int|string $id = null): Grid
    {
        $grid = $this->container->get('grid');

        if ($id !== null) {
            $grid->setId($id);
        }

        $this->grids->attach($grid);

        return $grid;
    }

    public function isReadyForRedirect(): ?bool
    {
        if ($this->grids->count() == 0) {
            throw new \RuntimeException(self::NO_GRID_EX_MSG);
        }

        $checkHash = [];

        $isReadyForRedirect = false;
        $this->grids->rewind();

        // Route url is the same for all grids
        if ($this->routeUrl === null) {
            $grid           = $this->grids->current();
            $this->routeUrl = $grid->getRouteUrl();
        }

        while ($this->grids->valid()) {
            $grid = $this->grids->current();

            if ($grid->isReadyForRedirect()) {
                $isReadyForRedirect = true;
            }

            if (in_array($grid->getHash(), $checkHash)) {
                throw new \RuntimeException(self::SAME_GRID_HASH_EX_MSG);
            }

            $checkHash[] = $grid->getHash();

            $this->grids->next();
        }

        return $isReadyForRedirect;
    }

    public function isReadyForExport(): ?bool
    {
        if ($this->grids->count() == 0) {
            throw new \RuntimeException(self::NO_GRID_EX_MSG);
        }

        $checkHash = [];

        $this->grids->rewind();
        while ($this->grids->valid()) {
            $grid = $this->grids->current();

            if (in_array($grid->getHash(), $checkHash)) {
                throw new \RuntimeException(self::SAME_GRID_HASH_EX_MSG);
            }

            $checkHash[] = $grid->getHash();

            if ($grid->isReadyForExport()) {
                $this->exportGrid = $grid;

                return true;
            }

            $this->grids->next();
        }

        return false;
    }

    public function isMassActionRedirect(): bool
    {
        $this->grids->rewind();
        while ($this->grids->valid()) {
            $grid = $this->grids->current();

            if ($grid->isMassActionRedirect()) {
                $this->massActionGrid = $grid;

                return true;
            }

            $this->grids->next();
        }

        return false;
    }

    public function getGridManagerResponse(string|array|null $param1 = null, string|array|null $param2 = null, Response $response = null): Response|array
    {
        $isReadyForRedirect = $this->isReadyForRedirect();

        if ($this->isReadyForExport()) {
            return $this->exportGrid->getExportResponse();
        }

        if ($this->isMassActionRedirect()) {
            return $this->massActionGrid->getMassActionResponse();
        }

        if ($isReadyForRedirect) {
            return new RedirectResponse($this->getRouteUrl());
        } else {
            if (is_array($param1) || $param1 === null) {
                $parameters = (array)$param1;
                $view       = $param2;
            } else {
                $parameters = (array)$param2;
                $view       = $param1;
            }

            $i = 1;
            $this->grids->rewind();
            while ($this->grids->valid()) {
                $parameters = array_merge(['grid' . $i => $this->grids->current()], $parameters);
                $this->grids->next();
                ++$i;
            }

            if ($view === null) {
                return $parameters;
            }

            if (null === $response) {
                $response = new Response();
            }

            $response->setContent($this->container->get('twig')->render($view, $parameters));

            return $response;
        }
    }

    public function getRouteUrl(): ?string
    {
        return $this->routeUrl;
    }

    public function setRouteUrl(string $routeUrl): self
    {
        $this->routeUrl = $routeUrl;

        return $this;
    }
}
