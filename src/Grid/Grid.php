<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace APY\DataGridBundle\Grid;

use APY\DataGridBundle\Grid\Action\MassActionInterface;
use APY\DataGridBundle\Grid\Action\RowActionInterface;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Column\MassActionColumn;
use APY\DataGridBundle\Grid\Export\Export;
use APY\DataGridBundle\Grid\Export\ExportInterface;
use APY\DataGridBundle\Grid\Mapping\Metadata\Manager;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Source\Source;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\TemplateWrapper;

class Grid implements GridInterface
{
    public const REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED = '__action_all_keys';
    public const REQUEST_QUERY_MASS_ACTION                   = '__action_id';
    public const REQUEST_QUERY_EXPORT                        = '__export_id';
    public const REQUEST_QUERY_TWEAK                         = '__tweak_id';
    public const REQUEST_QUERY_PAGE                          = '_page';
    public const REQUEST_QUERY_LIMIT                         = '_limit';
    public const REQUEST_QUERY_ORDER                         = '_order';
    public const REQUEST_QUERY_TEMPLATE                      = '_template';
    public const REQUEST_QUERY_RESET                         = '_reset';

    public const SOURCE_ALREADY_SETTED_EX_MSG          = 'The source of the grid is already set.';
    public const SOURCE_NOT_SETTED_EX_MSG              = 'The source of the grid must be set.';
    public const TWEAK_MALFORMED_ID_EX_MSG             = 'Tweak id "%s" is malformed. The id have to match this regex ^[0-9a-zA-Z_\+-]+';
    public const TWIG_TEMPLATE_LOAD_EX_MSG             = 'Unable to load template';
    public const NOT_VALID_LIMIT_EX_MSG                = 'Limit has to be array or integer';
    public const NOT_VALID_PAGE_NUMBER_EX_MSG          = 'Page must be a positive number';
    public const NOT_VALID_MAX_RESULT_EX_MSG           = 'Max results must be a positive number.';
    public const MASS_ACTION_NOT_DEFINED_EX_MSG        = 'Action %s is not defined.';
    public const MASS_ACTION_CALLBACK_NOT_VALID_EX_MSG = 'Callback %s is not callable or Controller action';
    public const EXPORT_NOT_DEFINED_EX_MSG             = 'Export %s is not defined.';
    public const PAGE_NOT_VALID_EX_MSG                 = 'Page must be a positive number';
    public const COLUMN_ORDER_NOT_VALID_EX_MSG         = '%s is not a valid order.';
    public const DEFAULT_LIMIT_NOT_VALID_EX_MSG        = 'Limit must be a positive number';
    public const LIMIT_NOT_DEFINED_EX_MSG              = 'Limit %s is not defined in limits.';
    public const NO_ROWS_RETURNED_EX_MSG               = 'Source have to return Rows object.';
    public const INVALID_TOTAL_COUNT_EX_MSG            = 'Source function getTotalCount need to return integer result, returned: %s';
    public const NOT_VALID_TWEAK_ID_EX_MSG             = 'Tweak with id "%s" doesn\'t exists';
    public const GET_FILTERS_NO_REQUEST_HANDLED_EX_MSG = 'getFilters method is only available in the manipulate callback function or after the call of the method isRedirected of the grid.';
    public const HAS_FILTER_NO_REQUEST_HANDLED_EX_MSG  = 'hasFilters method is only available in the manipulate callback function or after the call of the method isRedirected of the grid.';
    public const TWEAK_NOT_DEFINED_EX_MSG              = 'Tweak %s is not defined.';

    private RouterInterface $router;

    private Environment $twig;

    private HttpKernelInterface $httpKernel;

    private ManagerRegistry $doctrine;

    private Manager $mapping;

    private TranslatorInterface $translator;

    private string $kernelCharset;

    private ?SessionInterface $session = null;

    private ?Request $request;

    private AuthorizationCheckerInterface $securityContext;

    protected ?string $id;

    protected ?string $hash = null;

    private ?string $routeUrl = null;

    private ?array $routeParameters;

    private ?Source $source = null;

    private bool $prepared = false;

    private ?int $totalCount = null;

    private int $page = 0;

    private ?int $limit = null;

    private array $limits = [];

    private array|Columns|null $columns;

    private array|Rows|null $rows = null;

    /**
     * @var \APY\DataGridBundle\Grid\Action\MassAction[]
     */
    private array $massActions = [];

    /**
     * @var \APY\DataGridBundle\Grid\Action\RowAction[]
     */
    private array $rowActions = [];

    private bool $showFilters = true;

    private bool $showTitles = true;

    private string|array|object|null $requestData;

    private array|object $sessionData = [];

    private string $prefixTitle = '';

    private bool $persistence = false;

    private bool $newSession = false;

    private ?string $noDataMessage;

    private ?string $noResultMessage;

    /**
     * @var \APY\DataGridBundle\Grid\Export\Export[]
     */
    private array $exports = [];

    private ?bool $redirect = null;

    private bool $isReadyForExport = false;

    private ?Response $exportResponse;

    private ?Response $massActionResponse = null;

    private ?int $maxResults = null;

    private int $dataJunction = Column::DATA_CONJUNCTION;

    private array $permanentFilters = [];

    private array $defaultFilters = [];

    private ?string $defaultOrder = null;

    private ?int $defaultLimit = null;

    private ?int $defaultPage = null;

    private array $tweaks = [];

    private ?string $defaultTweak = null;

    private ?array $sessionFilters = null;

    private array $lazyAddColumn = [];

    private array $lazyHiddenColumns = [];

    private array $lazyVisibleColumns = [];

    private array $lazyHideShowColumns = [];

    private ?int $actionsColumnSize;

    private ?string $actionsColumnTitle;

    private ?bool $massActionsInNewTab;

    private ?GridConfigInterface $config;

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
        ?string $id = '',
        ?GridConfigInterface $config = null
    ) {
        $this->config = $config;

        $this->router  = $router;
        $this->request = $requestStack->getCurrentRequest();

        $this->twig          = $twig;
        $this->httpKernel    = $httpKernel;
        $this->doctrine      = $doctrine;
        $this->mapping       = $mapping;
        $this->kernelCharset = $kernel->getCharset() ?? 'UTF-8';
        $this->translator    = $translator;

        if (null === $this->request) {
            $this->request = Request::createFromGlobals();
        } else {
            $this->session = $this->request->getSession();
        }

        $this->securityContext = $authorizationChecker;

        $this->id = $id;

        $this->columns = new Columns($this->securityContext);

        $this->routeParameters = $this->request->attributes->all();
        foreach (array_keys($this->routeParameters) as $key) {
            if (substr($key, 0, 1) == '_') {
                unset($this->routeParameters[$key]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): self
    {
        $config = $this->config;

        if (!$config) {
            return $this;
        }

        $this->setPersistence($config->isPersisted());

        // Route parameters
        $routeParameters = [];
        $parameters      = $config->getRouteParameters();
        if (!empty($parameters)) {
            $routeParameters = $parameters;
            foreach ($routeParameters as $parameter => $value) {
                $this->setRouteParameter($parameter, $value);
            }
        }

        // Route
        if (null !== $config->getRoute()) {
            $this->setRouteUrl($this->router->generate($config->getRoute(), $routeParameters));
        }

        // Route
        if (null !== $config->getRoute()) {
            $this->setRouteUrl($this->router->generate($config->getRoute(), $routeParameters));
        }

        // Columns
        foreach ($this->lazyAddColumn as $columnInfo) {
            /** @var Column $column */
            $column = $columnInfo['column'];

            if (!$config->isFilterable()) {
                $column->setFilterable(false);
            }

            if (!$config->isSortable()) {
                $column->setSortable(false);
            }
        }

        // Source
        $source = $config->getSource();

        if (null !== $source) {
            $this->source = $source;

            $source->initialise($this->doctrine, $this->mapping);

            if ($source instanceof Entity) {
                $groupBy = $config->getGroupBy();
                if (null !== $groupBy) {
                    if (!is_array($groupBy)) {
                        $groupBy = [$groupBy];
                    }

                    // Must be set after source because initialize method reset groupBy property
                    $source->setGroupBy($groupBy);
                }
            }
        }

        // Order
        if (null !== $config->getSortBy()) {
            $this->setDefaultOrder($config->getSortBy(), $config->getOrder());
        }

        if (null !== $config->getMaxPerPage()) {
            $this->setLimits($config->getMaxPerPage());
        }

        $this
            ->setMaxResults($config->getMaxResults())
            ->setPage($config->getPage())
            ->setMassActionsInNewTab((bool)$config->getMassActionsInNewTab());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request, $dumpRequestData = false): self
    {
        if (null === $this->source) {
            throw new \LogicException(self::SOURCE_NOT_SETTED_EX_MSG);
        }

        $this->request = $request;
        $this->session = $request->getSession();

        $this->createHash();

        $this->requestData = $request->get($this->hash);

        $this->processPersistence();

        if (null !== $this->session) {
            $this->sessionData = (array)$this->session->get($this->hash);
        }

        $this->processLazyParameters();

        if (!empty($this->requestData)) {
            $this->processRequestData();
        }

        if ($this->newSession) {
            $this->setDefaultSessionData();
        }

        $this->processPermanentFilters();

        $this->processSessionData();

        $this->prepare();

        return $this;
    }

    public function setSource(?Source $source): self
    {
        if ($this->source !== null) {
            throw new \InvalidArgumentException(self::SOURCE_ALREADY_SETTED_EX_MSG);
        }

        $this->source = $source;

        $this->source->initialise($this->doctrine, $this->mapping);

        // Get columns from the source
        $this->source->getColumns($this->columns);

        return $this;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function isReadyForRedirect(): ?bool
    {
        if ($this->source === null) {
            throw new \Exception(self::SOURCE_NOT_SETTED_EX_MSG);
        }

        if ($this->redirect !== null) {
            return $this->redirect;
        }

        $this->createHash();

        $this->requestData = (array)$this->request->get($this->hash);

        $this->processPersistence();

        if (null !== $this->session) {
            $this->sessionData = (array)$this->session->get($this->hash);
        }

        $this->processLazyParameters();

        // isReadyForRedirect ?
        if (!empty($this->requestData)) {
            $this->processRequestData();

            $this->redirect = true;
        }

        if ($this->redirect === null || ($this->request->isXmlHttpRequest() && !$this->isReadyForExport)) {
            if ($this->newSession) {
                $this->setDefaultSessionData();
            }

            $this->processPermanentFilters();

            //Configures the grid with the data read from the session.
            $this->processSessionData();

            $this->prepare();

            $this->redirect = false;
        }

        return $this->redirect;
    }

    protected function getCurrentUri(): string
    {
        return $this->request->getScheme() . '://' . $this->request->getHttpHost() . $this->request->getBaseUrl() . $this->request->getPathInfo();
    }

    protected function processPersistence(): void
    {
        $referer = strtok($this->request->headers->get('referer'), '?');

        // Persistence or reset - kill previous session
        if ((!$this->request->isXmlHttpRequest() && !$this->persistence && $referer != $this->getCurrentUri())
            || isset($this->requestData[self::REQUEST_QUERY_RESET])) {
            if (null !== $this->session) {
                $this->session->remove($this->hash);
            }
        }

        if (null !== $this->session && $this->session->get($this->hash) === null) {
            $this->newSession = true;
        }
    }

    protected function processLazyParameters(): void
    {
        // Additional columns
        foreach ($this->lazyAddColumn as $column) {
            $this->columns->addColumn($column['column'], $column['position']);
        }

        // Hidden columns
        foreach ($this->lazyHiddenColumns as $columnId) {
            $this->columns->getColumnById($columnId)->setVisible(false);
        }

        // Visible columns
        if (!empty($this->lazyVisibleColumns)) {
            $columnNames = [];
            foreach ($this->columns as $column) {
                $columnNames[] = $column->getId();
            }

            foreach (array_diff($columnNames, $this->lazyVisibleColumns) as $columnId) {
                $this->columns->getColumnById($columnId)->setVisible(false);
            }
        }

        // Hide and Show columns
        foreach ($this->lazyHideShowColumns as $columnId => $visible) {
            $this->columns->getColumnById($columnId)->setVisible($visible);
        }
    }

    /**
     * Reads data from the request and write this data to the session.
     */
    protected function processRequestData(): void
    {
        $this->processMassActions($this->getFromRequest(self::REQUEST_QUERY_MASS_ACTION));

        if ($this->processExports($this->getFromRequest(self::REQUEST_QUERY_EXPORT))
            || $this->processTweaks($this->getFromRequest(self::REQUEST_QUERY_TWEAK))) {
            return;
        }

        $filtering = $this->processRequestFilters();

        $this->processPage($this->getFromRequest(self::REQUEST_QUERY_PAGE), $filtering);

        $this->processOrder($this->getFromRequest(self::REQUEST_QUERY_ORDER));

        $this->processLimit($this->getFromRequest(self::REQUEST_QUERY_LIMIT));

        $this->saveSession();
    }

    protected function processMassActions(int|string|bool|null $actionId): void
    {
        if ($actionId > -1 && '' !== $actionId) {
            if (array_key_exists($actionId, $this->massActions)) {
                $action        = $this->massActions[$actionId];
                $actionAllKeys = (boolean)$this->getFromRequest(self::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED);
                $actionKeys    = $actionAllKeys === false ? array_keys((array)$this->getFromRequest(MassActionColumn::ID)) : [];

                $this->processSessionData();
                if ($actionAllKeys) {
                    $this->page  = 0;
                    $this->limit = 0;
                }

                $this->prepare();

                if ($actionAllKeys === true) {
                    foreach ($this->rows as $row) {
                        $actionKeys[] = $row->getPrimaryFieldValue();
                    }
                }

                if (is_callable($action->getCallback()) && null !== $this->session) {
                    $this->massActionResponse = call_user_func($action->getCallback(), $actionKeys, $actionAllKeys, $this->session, $action->getParameters());
                } elseif (strpos($action->getCallback(), ':') !== false) {
                    $path = array_merge(
                        [
                            'primaryKeys'    => $actionKeys,
                            'allPrimaryKeys' => $actionAllKeys,
                            '_controller'    => $action->getCallback(),
                        ],
                        $action->getParameters()
                    );

                    $subRequest = $this->request->duplicate([], null, $path);

                    $this->massActionResponse = $this->httpKernel->handle($subRequest, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);
                } else {
                    throw new \RuntimeException(sprintf(self::MASS_ACTION_CALLBACK_NOT_VALID_EX_MSG, $action->getCallback()));
                }
            } else {
                throw new \OutOfBoundsException(sprintf(self::MASS_ACTION_NOT_DEFINED_EX_MSG, $actionId));
            }
        }
    }

    protected function processExports(int|string|bool|null $exportId): bool
    {
        if ($exportId > -1 && '' !== $exportId) {
            if (array_key_exists($exportId, $this->exports)) {
                $this->isReadyForExport = true;

                $this->processSessionData();
                $this->page  = 0;
                $this->limit = 0;
                $this->prepare();

                $export = $this->exports[$exportId];

                if ($export instanceof Export) {
                    $export->setTwig($this->twig)
                           ->setTranslator($this->translator)
                           ->setRouter($this->router)
                           ->setKernelCharset($this->kernelCharset);
                }

                $export->computeData($this);

                $this->exportResponse = $export->getResponse();

                return true;
            } else {
                throw new \OutOfBoundsException(sprintf(self::EXPORT_NOT_DEFINED_EX_MSG, $exportId));
            }
        }

        return false;
    }

    protected function processTweaks(int|string|bool|null $tweakId): bool
    {
        if ($tweakId !== null) {
            if (array_key_exists($tweakId, $this->tweaks)) {
                $tweak        = $this->tweaks[$tweakId];
                $saveAsActive = false;

                if (isset($tweak['reset'])) {
                    $this->sessionData = [];

                    if (null !== $this->session) {
                        $this->session->remove($this->hash);
                    }
                }

                if (isset($tweak['filters'])) {
                    $this->defaultFilters = [];
                    $this->setDefaultFilters($tweak['filters']);
                    $this->processDefaultFilters();
                    $saveAsActive = true;
                }

                if (isset($tweak['order'])) {
                    $this->processOrder($tweak['order']);
                    $saveAsActive = true;
                }

                if (isset($tweak['massAction'])) {
                    $this->processMassActions($tweak['massAction']);
                }

                if (isset($tweak['page'])) {
                    $this->processPage($tweak['page']);
                    $saveAsActive = true;
                }

                if (isset($tweak['limit'])) {
                    $this->processLimit($tweak['limit']);
                    $saveAsActive = true;
                }

                if (isset($tweak['export'])) {
                    $this->processExports($tweak['export']);
                }

                if ($saveAsActive) {
                    $activeTweaks                  = $this->getActiveTweaks();
                    $activeTweaks[$tweak['group']] = $tweakId;
                    $this->set('tweaks', $activeTweaks);
                }

                if (isset($tweak['removeActiveTweaksGroups'])) {
                    $removeActiveTweaksGroups = (array)$tweak['removeActiveTweaksGroups'];
                    $activeTweaks             = $this->getActiveTweaks();
                    foreach ($removeActiveTweaksGroups as $id) {
                        if (isset($activeTweaks[$id])) {
                            unset($activeTweaks[$id]);
                        }
                    }

                    $this->set('tweaks', $activeTweaks);
                }

                if (isset($tweak['removeActiveTweaks'])) {
                    $removeActiveTweaks = (array)$tweak['removeActiveTweaks'];
                    $activeTweaks       = $this->getActiveTweaks();
                    foreach ($removeActiveTweaks as $id) {
                        if (array_key_exists($id, $this->tweaks)) {
                            if (isset($activeTweaks[$this->tweaks[$id]['group']])) {
                                unset($activeTweaks[$this->tweaks[$id]['group']]);
                            }
                        }
                    }

                    $this->set('tweaks', $activeTweaks);
                }

                if (isset($tweak['addActiveTweaks'])) {
                    $addActiveTweaks = (array)$tweak['addActiveTweaks'];
                    $activeTweaks    = $this->getActiveTweaks();
                    foreach ($addActiveTweaks as $id) {
                        if (array_key_exists($id, $this->tweaks)) {
                            $activeTweaks[$this->tweaks[$id]['group']] = $id;
                        }
                    }

                    $this->set('tweaks', $activeTweaks);
                }

                $this->saveSession();

                return true;
            } else {
                throw new \OutOfBoundsException(sprintf(self::TWEAK_NOT_DEFINED_EX_MSG, $tweakId));
            }
        }

        return false;
    }

    protected function processRequestFilters(): bool
    {
        $filtering = false;
        foreach ($this->columns as $column) {
            if ($column->isFilterable()) {
                $ColumnId = $column->getId();

                // Get data from request
                $data = $this->getFromRequest($ColumnId);

                //if no item is selectd in multi select filter : simulate empty first choice
                if ($column->getFilterType() == 'select'
                    && $column->getSelectMulti() === true
                    && $data === null
                    && $this->getFromRequest(self::REQUEST_QUERY_PAGE) === null
                    && $this->getFromRequest(self::REQUEST_QUERY_ORDER) === null
                    && $this->getFromRequest(self::REQUEST_QUERY_LIMIT) === null
                    && ($this->getFromRequest(self::REQUEST_QUERY_MASS_ACTION) === null || $this->getFromRequest(self::REQUEST_QUERY_MASS_ACTION) == '-1')) {
                    $data = ['from' => ''];
                }

                // Store in the session
                $this->set($ColumnId, $data);

                // Filtering ?
                if (!$filtering && $data !== null) {
                    $filtering = true;
                }
            }
        }

        return $filtering;
    }

    protected function processPage(?int $page, bool $filtering = false): void
    {
        // Set to the first page if this is a request of order, limit, mass action or filtering
        if ($this->getFromRequest(self::REQUEST_QUERY_ORDER) !== null
            || $this->getFromRequest(self::REQUEST_QUERY_LIMIT) !== null
            || $this->getFromRequest(self::REQUEST_QUERY_MASS_ACTION) !== null
            || $filtering) {
            $this->set(self::REQUEST_QUERY_PAGE, 0);
        } else {
            $this->set(self::REQUEST_QUERY_PAGE, $page);
        }
    }

    protected function processOrder(int|string|null $order): void
    {
        if ($order !== null) {
            [$columnId, $columnOrder] = explode('|', $order);

            $column = $this->columns->getColumnById($columnId);
            if ($column->isSortable() && in_array(strtolower($columnOrder), ['asc', 'desc'])) {
                $this->set(self::REQUEST_QUERY_ORDER, $order);
            }
        }
    }

    protected function processLimit(?int $limit): void
    {
        if (isset($this->limits[$limit])) {
            $this->set(self::REQUEST_QUERY_LIMIT, $limit);
        }
    }

    protected function setDefaultSessionData(): void
    {
        // Default filters
        $this->processDefaultFilters();

        // Default page
        if ($this->defaultPage !== null) {
            if ((int)$this->defaultPage >= 0) {
                $this->set(self::REQUEST_QUERY_PAGE, $this->defaultPage);
            } else {
                throw new \InvalidArgumentException(self::NOT_VALID_PAGE_NUMBER_EX_MSG);
            }
        }

        // Default order
        if ($this->defaultOrder !== null) {
            [$columnId, $columnOrder] = explode('|', $this->defaultOrder);

            $this->columns->getColumnById($columnId);
            if (in_array(strtolower($columnOrder), ['asc', 'desc'])) {
                $this->set(self::REQUEST_QUERY_ORDER, $this->defaultOrder);
            } else {
                throw new \InvalidArgumentException(sprintf(self::COLUMN_ORDER_NOT_VALID_EX_MSG, $columnOrder));
            }
        }

        if ($this->defaultLimit !== null) {
            if ((int)$this->defaultLimit >= 0) {
                if (isset($this->limits[$this->defaultLimit])) {
                    $this->set(self::REQUEST_QUERY_LIMIT, $this->defaultLimit);
                } else {
                    throw new \InvalidArgumentException(sprintf(self::LIMIT_NOT_DEFINED_EX_MSG, $this->defaultLimit));
                }
            } else {
                throw new \InvalidArgumentException(self::DEFAULT_LIMIT_NOT_VALID_EX_MSG);
            }
        }

        // Default tweak
        if ($this->defaultTweak !== null) {
            $this->processTweaks($this->defaultTweak);
        }
        $this->saveSession();
    }

    protected function processFilters(bool $permanent = true): void
    {
        foreach (($permanent ? $this->permanentFilters : $this->defaultFilters) as $columnId => $value) {
            /* @var $column Column */
            $column = $this->columns->getColumnById($columnId);

            if ($permanent) {
                // Disable the filter capability for the column
                $column->setFilterable(false);
            }

            // Convert simple value
            if (!is_array($value) || !is_string(key($value))) {
                $value = ['from' => $value];
            }

            // Convert boolean value
            if (isset($value['from']) && is_bool($value['from'])) {
                $value['from'] = $value['from'] ? '1' : '0';
            }

            // Convert simple value with select filter
            if ($column->getFilterType() === 'select') {
                if (isset($value['from']) && !is_array($value['from'])) {
                    $value['from'] = [$value['from']];
                }

                if (isset($value['to']) && !is_array($value['to'])) {
                    $value['to'] = [$value['to']];
                }
            }

            // Store in the session
            $this->set($columnId, $value);
        }
    }

    protected function processPermanentFilters(): void
    {
        $this->processFilters();
        $this->saveSession();
    }

    protected function processDefaultFilters(): void
    {
        $this->processFilters(false);
    }

    protected function processSessionData(): void
    {
        // Filters
        foreach ($this->columns as $column) {
            if (($data = $this->get($column->getId())) !== null) {
                $column->setData($data);
            }
        }

        // Page
        if (($page = $this->get(self::REQUEST_QUERY_PAGE)) !== null) {
            $this->setPage($page);
        } else {
            $this->setPage(0);
        }

        // Order
        if (($order = $this->get(self::REQUEST_QUERY_ORDER)) !== null) {
            [$columnId, $columnOrder] = explode('|', $order);

            $this->columns->getColumnById($columnId)->setOrder($columnOrder);
        }

        // Limit
        if (($limit = $this->get(self::REQUEST_QUERY_LIMIT)) !== null) {
            $this->limit = $limit;
        } else {
            $this->limit = key($this->limits);
        }
    }

    protected function prepare(): self
    {
        if ($this->prepared) {
            return $this;
        }

        if ($this->source->isDataLoaded()) {
            $this->rows = $this->source->executeFromData($this->columns->getIterator(true), $this->page, $this->limit, $this->maxResults);
        } else {
            $this->rows = $this->source->execute($this->columns->getIterator(true), $this->page, $this->limit, $this->maxResults, $this->dataJunction);
        }

        if (!$this->rows instanceof Rows) {
            throw new \Exception(self::NO_ROWS_RETURNED_EX_MSG);
        }

        if (count($this->rows) == 0 && $this->page > 0) {
            $this->page = 0;
            $this->prepare();

            return $this;
        }

        //add row actions column
        if (count($this->rowActions) > 0) {
            foreach ($this->rowActions as $column => $rowActions) {
                if (($actionColumn = $this->columns->hasColumnById($column, true))) {
                    $actionColumn->setRowActions($rowActions);
                } else {
                    $actionColumn = new ActionsColumn($column, $this->actionsColumnTitle, $rowActions);
                    if ($this->actionsColumnSize > -1) {
                        $actionColumn->setSize($this->actionsColumnSize);
                    }

                    $this->columns->addColumn($actionColumn);
                }
            }
        }

        //add mass actions column
        if (count($this->massActions) > 0) {
            $this->columns->addColumn(new MassActionColumn(), 1);
        }

        $primaryColumnId = $this->columns->getPrimaryColumn()->getId();

        foreach ($this->rows as $row) {
            $row->setPrimaryField($primaryColumnId);
        }

        //get size
        if ($this->source->isDataLoaded()) {
            $this->source->populateSelectFiltersFromData($this->columns);
            $this->totalCount = $this->source->getTotalCountFromData($this->maxResults);
        } else {
            $this->source->populateSelectFilters($this->columns);
            $this->totalCount = $this->source->getTotalCount($this->maxResults);
        }

        if (!is_int($this->totalCount)) {
            throw new \Exception(sprintf(self::INVALID_TOTAL_COUNT_EX_MSG, gettype($this->totalCount)));
        }

        $this->prepared = true;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|void
     */
    protected function getFromRequest(string $key)
    {
        if (isset($this->requestData[$key])) {
            return $this->requestData[$key];
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|void
     */
    protected function get(?string $key)
    {
        if (isset($this->sessionData[$key])) {
            return $this->sessionData[$key];
        }
    }

    protected function set(string $key, mixed $data): void
    {
        // Only the filters values are removed from the session
        $fromIsEmpty = isset($data['from']) && ((is_string($data['from']) && $data['from'] === '') || (is_array($data['from']) && $data['from'][0] === ''));
        $toIsSet     = isset($data['to']) && (is_string($data['to']) && $data['to'] !== '');
        if ($fromIsEmpty && !$toIsSet) {
            if (array_key_exists($key, $this->sessionData)) {
                unset($this->sessionData[$key]);
            }
        } elseif ($data !== null) {
            $this->sessionData[$key] = $data;
        }
    }

    protected function saveSession(): void
    {
        if (!empty($this->sessionData) && !empty($this->hash) && null !== $this->session) {
            $this->session->set($this->hash, $this->sessionData);
        }
    }

    protected function createHash(): void
    {
        $this->hash = 'grid_' . (empty($this->id) ? md5($this->request->get('_controller') . $this->columns->getHash() . $this->source->getHash()) : $this->getId());
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function addColumn(Column $column, int $position = 0): self
    {
        $this->lazyAddColumn[] = ['column' => $column, 'position' => $position];

        return $this;
    }

    public function getColumn(string|int $columnId): ?Column
    {
        foreach ($this->lazyAddColumn as $column) {
            if ($column['column']->getId() == $columnId) {
                return $column['column'];
            }
        }

        return $this->columns->getColumnById($columnId);
    }

    public function getColumns(): array|Columns|null
    {
        return $this->columns;
    }

    public function getLazyAddColumn(): array
    {
        return $this->lazyAddColumn;
    }

    public function hasColumn(string|int $columnId): bool
    {
        foreach ($this->lazyAddColumn as $column) {
            if ($column['column']->getId() == $columnId) {
                return true;
            }
        }

        return $this->columns->hasColumnById($columnId);
    }

    public function setColumns(?Columns $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function setColumnsOrder(array $columnIds, bool $keepOtherColumns = true): self
    {
        $this->columns->setColumnsOrder($columnIds, $keepOtherColumns);

        return $this;
    }

    public function addMassAction(MassActionInterface $action): self
    {
        if ($action->getRole() === null || $this->securityContext->isGranted($action->getRole())) {
            $this->massActions[] = $action;
        }

        return $this;
    }

    public function getMassActions(): array
    {
        return $this->massActions;
    }

    /**
     * Add a tweak.
     *
     * @param string $title title of the tweak
     * @param array  $tweak array('filters' => array, 'order' => 'colomunId|order', 'page' => integer, 'limit' => integer, 'export' => integer, 'massAction' => integer)
     * @param string $id    id of the tweak matching the regex ^[0-9a-zA-Z_\+-]+
     * @param string $group group of the tweak
     *
     * @return self
     */
    public function addTweak(string $title, array $tweak, ?string $id = null, ?string $group = null): self
    {
        if ($id !== null && !preg_match('/^[0-9a-zA-Z_\+-]+$/', $id)) {
            throw new \InvalidArgumentException(sprintf(self::TWEAK_MALFORMED_ID_EX_MSG, $id));
        }

        $tweak = array_merge(['id' => $id, 'title' => $title, 'group' => $group], $tweak);
        if (isset($id)) {
            $this->tweaks[$id] = $tweak;
        } else {
            $this->tweaks[] = $tweak;
        }

        return $this;
    }

    public function getTweaks(): array
    {
        $separator = strpos($this->getRouteUrl(), '?') ? '&' : '?';
        $url       = $this->getRouteUrl() . $separator . $this->getHash() . '[' . self::REQUEST_QUERY_TWEAK . ']=';

        foreach ($this->tweaks as $id => $tweak) {
            $this->tweaks[$id] = array_merge($tweak, ['url' => $url . $id]);
        }

        return $this->tweaks;
    }

    public function getAllTweaks(): ?array
    {
        return $this->tweaks;
    }

    public function getActiveTweaks(): array
    {
        return (array)$this->get('tweaks');
    }

    public function getTweak(string|int $id): array
    {
        $tweaks = $this->getTweaks();
        if (isset($tweaks[$id])) {
            return $tweaks[$id];
        }

        throw new \InvalidArgumentException(sprintf(self::NOT_VALID_TWEAK_ID_EX_MSG, $id));
    }

    public function getTweaksGroup(string $group): array
    {
        $tweaksGroup = $this->getTweaks();

        foreach ($tweaksGroup as $id => $tweak) {
            if ($tweak['group'] != $group) {
                unset($tweaksGroup[$id]);
            }
        }

        return $tweaksGroup;
    }

    public function getActiveTweakGroup(string $group): array|int|string
    {
        $tweaks = $this->getActiveTweaks();

        return isset($tweaks[$group]) ? $tweaks[$group] : -1;
    }

    public function addRowAction(RowActionInterface $action): self
    {
        if ($action->getRole() === null || $this->securityContext->isGranted($action->getRole())) {
            $this->rowActions[$action->getColumn()][] = $action;
        }

        return $this;
    }

    public function getRowActions(): array
    {
        return $this->rowActions;
    }

    public function setTemplate(TemplateWrapper|string|bool|null $template): self
    {
        if ($template !== null) {
            if ($template instanceof TemplateWrapper) {
                $template = '__SELF__' . $template->getTemplateName();
            } elseif (!is_string($template)) {
                throw new \Exception(self::TWIG_TEMPLATE_LOAD_EX_MSG);
            }

            $this->set(self::REQUEST_QUERY_TEMPLATE, $template);

            if ($this->hash === null) {
                $this->createHash();
            }

            $this->saveSession();
        }

        return $this;
    }

    public function getTemplate(): TemplateWrapper|string|null
    {
        return $this->get(self::REQUEST_QUERY_TEMPLATE);
    }

    public function addExport(ExportInterface $export): self
    {
        if ($export->getRole() === null || $this->securityContext->isGranted($export->getRole())) {
            $this->exports[] = $export;
        }

        return $this;
    }

    public function getExports(): array
    {
        return $this->exports;
    }

    public function getExportResponse(): Response
    {
        return $this->exportResponse;
    }

    public function getMassActionResponse(): Response
    {
        return $this->massActionResponse;
    }

    public function setRouteParameter(string $parameter, mixed $value): self
    {
        $this->routeParameters[$parameter] = $value;

        return $this;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setRouteUrl(?string $routeUrl): self
    {
        $this->routeUrl = $routeUrl;

        return $this;
    }

    public function getRouteUrl(): ?string
    {
        if ($this->routeUrl === null) {
            $this->routeUrl = $this->router->generate($this->request->get('_route'), $this->getRouteParameters());
        }

        return $this->routeUrl;
    }

    public function isReadyForExport(): ?bool
    {
        return $this->isReadyForExport;
    }

    public function isMassActionRedirect(): bool
    {
        return $this->massActionResponse instanceof Response;
    }

    protected function setFilters(array $filters, bool $permanent = true): self
    {
        foreach ($filters as $columnId => $value) {
            if ($permanent) {
                $this->permanentFilters[$columnId] = $value;
            } else {
                $this->defaultFilters[$columnId] = $value;
            }
        }

        return $this;
    }

    public function getPermanentFilters(): array
    {
        return $this->permanentFilters;
    }

    public function getDefaultFilters(): array
    {
        return $this->defaultFilters;
    }

    public function setPermanentFilters(array $filters): self
    {
        return $this->setFilters($filters);
    }

    public function setDefaultFilters(array $filters): self
    {
        return $this->setFilters($filters, false);
    }

    public function setDefaultOrder(string|int $columnId, string $order): self
    {
        $order              = strtolower($order);
        $this->defaultOrder = "$columnId|$order";

        return $this;
    }

    public function getDefaultOrder(): ?string
    {
        return $this->defaultOrder;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setPersistence(?bool $persistence): self
    {
        $this->persistence = (bool)$persistence;

        return $this;
    }

    public function getPersistence(): ?bool
    {
        return $this->persistence;
    }

    public function getDataJunction(): ?int
    {
        return $this->dataJunction;
    }

    public function setDataJunction(?int $dataJunction): self
    {
        $this->dataJunction = $dataJunction;

        return $this;
    }

    /**
     * Sets Limits.
     *
     * @param mixed $limits e.g. 10, array(10, 1000) or array(10 => '10', 1000 => '1000')
     *
     * @return self
     * @throws \InvalidArgumentException
     */
    public function setLimits(mixed $limits): self
    {
        if (is_array($limits)) {
            if ((int)key($limits) === 0) {
                $this->limits = array_combine($limits, $limits);
            } else {
                $this->limits = $limits;
            }
        } elseif (is_int($limits)) {
            $this->limits = [$limits => (string)$limits];
        } else {
            throw new \InvalidArgumentException(self::NOT_VALID_LIMIT_EX_MSG);
        }

        return $this;
    }

    public function getLimits(): array
    {
        return $this->limits;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setDefaultLimit(?int $limit): self
    {
        $this->defaultLimit = $limit;

        return $this;
    }

    public function setDefaultPage(?int $page): self
    {
        $this->defaultPage = $page - 1;

        return $this;
    }

    public function setDefaultTweak(?string $tweakId): self
    {
        $this->defaultTweak = $tweakId;

        return $this;
    }

    public function getDefaultTweak(): ?string
    {
        return $this->defaultTweak;
    }

    public function setPage(int $page): self
    {
        if ($page >= 0) {
            $this->page = $page;
        } else {
            throw new \InvalidArgumentException(self::PAGE_NOT_VALID_EX_MSG);
        }

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getRows(): array|Rows|null
    {
        return $this->rows;
    }

    public function getPageCount(): float
    {
        $pageCount = 1;
        if ($this->getLimit() > 0) {
            $pageCount = ceil($this->getTotalCount() / $this->getLimit());
        }

        // @todo why this should be a float?
        return $pageCount;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function setMaxResults(?int $maxResults = null): self
    {
        if ((is_int($maxResults) && $maxResults < 0) && $maxResults !== null) {
            throw new \InvalidArgumentException(self::NOT_VALID_MAX_RESULT_EX_MSG);
        }

        $this->maxResults = $maxResults;

        return $this;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function isFiltered(): bool
    {
        foreach ($this->columns as $column) {
            if ($column->isFiltered()) {
                return true;
            }
        }

        return false;
    }

    public function isTitleSectionVisible(): bool
    {
        if ($this->showTitles === true) {
            foreach ($this->columns as $column) {
                if ($column->getTitle() != '') {
                    return true;
                }
            }
        }

        return false;
    }

    public function isFilterSectionVisible(): bool
    {
        if ($this->showFilters === true) {
            foreach ($this->columns as $column) {
                if ($column->isFilterable() && $column->getType() != 'massaction' && $column->getType() != 'actions') {
                    return true;
                }
            }
        }

        return false;
    }

    public function isPagerSectionVisible(): bool
    {
        $limits = $this->getLimits();

        if (empty($limits)) {
            return false;
        }

        // true when totalCount rows exceed the minimum pager limit
        return min(array_keys($limits)) < $this->totalCount;
    }

    public function hideFilters(): self
    {
        $this->showFilters = false;

        return $this;
    }

    public function getShowFilters(): bool
    {
        return $this->showFilters;
    }

    public function hideTitles(): self
    {
        $this->showTitles = false;

        return $this;
    }

    public function getShowTitles(): bool
    {
        return $this->showTitles;
    }

    public function addColumnExtension(Column $extension): self
    {
        $this->columns->addExtension($extension);

        return $this;
    }

    public function setPrefixTitle(string $prefixTitle): self
    {
        $this->prefixTitle = $prefixTitle;

        return $this;
    }

    public function getPrefixTitle(): string
    {
        return $this->prefixTitle;
    }

    public function setNoDataMessage(?string $noDataMessage): self
    {
        $this->noDataMessage = $noDataMessage;

        return $this;
    }

    public function getNoDataMessage(): ?string
    {
        return $this->noDataMessage;
    }

    public function setNoResultMessage(string $noResultMessage): self
    {
        $this->noResultMessage = $noResultMessage;

        return $this;
    }

    public function getNoResultMessage(): ?string
    {
        return $this->noResultMessage;
    }

    public function setHiddenColumns(string|int|array $columnIds): self
    {
        $this->lazyHiddenColumns = (array)$columnIds;

        return $this;
    }

    public function getHiddenColumns(): array
    {
        return $this->lazyHiddenColumns;
    }

    public function setVisibleColumns(string|int|array $columnIds): self
    {
        $this->lazyVisibleColumns = (array)$columnIds;

        return $this;
    }

    public function getVisibleColumns(): array
    {
        return $this->lazyVisibleColumns;
    }

    public function showColumns(string|int|array $columnIds): self
    {
        foreach ((array)$columnIds as $columnId) {
            $this->lazyHideShowColumns[$columnId] = true;
        }

        return $this;
    }

    public function hideColumns(string|array $columnIds): self
    {
        foreach ((array)$columnIds as $columnId) {
            $this->lazyHideShowColumns[$columnId] = false;
        }

        return $this;
    }

    public function getLazyHideShowColumns(): array
    {
        return $this->lazyHideShowColumns;
    }

    public function setActionsColumnSize(int $size): self
    {
        $this->actionsColumnSize = $size;

        return $this;
    }

    public function getActionsColumnSize(): ?int
    {
        return $this->actionsColumnSize;
    }

    public function getNewSession(): bool
    {
        return $this->newSession;
    }

    public function setActionsColumnTitle(?string $title): self
    {
        $this->actionsColumnTitle = $title;

        return $this;
    }

    public function getActionsColumnTitle(): ?string
    {
        return $this->actionsColumnTitle;
    }

    public function setMassActionsInNewTab(bool $massActionsInNewTab): self
    {
        $this->massActionsInNewTab = $massActionsInNewTab;

        return $this;
    }

    public function getMassActionsInNewTab(): bool
    {
        return (bool)$this->massActionsInNewTab;
    }

    public function deleteAction(array $ids): void
    {
        $this->source->delete($ids);
    }

    public function __clone(): void
    {
        // clone all objects
        $this->columns = clone $this->columns;
    }

    /****** HELPER ******/

    public function getGridResponse(string|array|null $param1 = null, string|array|null $param2 = null, Response $response = null): Response|array
    {
        $isReadyForRedirect = $this->isReadyForRedirect();

        if ($this->isReadyForExport()) {
            return $this->getExportResponse();
        }

        if ($this->isMassActionRedirect()) {
            return $this->getMassActionResponse();
        }

        if ($isReadyForRedirect) {
            return new RedirectResponse($this->getRouteUrl());
        } else {
            return $this->getContentResponse($param1, $param2, $response);
        }
    }

    public function getContentResponse(string|array|null $param1 = null, string|array|null $param2 = null, ?Response $response = null): Response|array
    {
        if (is_array($param1) || $param1 === null) {
            $parameters = (array)$param1;
            $view       = $param2;
        } else {
            $parameters = (array)$param2;
            $view       = $param1;
        }

        $parameters = array_merge(['grid' => $this], $parameters);

        if ($view === null) {
            return $parameters;
        } else {
            if (null === $response) {
                $response = new Response();
            }

            $response->setContent($this->twig->render($view, $parameters));

            return $response;
        }
    }

    public function getRawData(string|array|null $columnNames = null, bool $namedIndexes = true): array
    {
        if ($columnNames === null) {
            foreach ($this->getColumns() as $column) {
                $columnNames[] = $column->getId();
            }
        }

        $columnNames = (array)$columnNames;
        $result      = [];
        foreach ($this->rows as $row) {
            $resultRow = [];
            foreach ($columnNames as $columnName) {
                if ($namedIndexes) {
                    $resultRow[$columnName] = $row->getField($columnName);
                } else {
                    $resultRow[] = $row->getField($columnName);
                }
            }

            $result[] = $resultRow;
        }

        return $result;
    }

    public function getFilters(): array
    {
        if ($this->hash === null) {
            throw new \Exception(self::GET_FILTERS_NO_REQUEST_HANDLED_EX_MSG);
        }

        if ($this->sessionFilters === null) {
            $this->sessionFilters = [];
            $session              = $this->sessionData;

            $requestQueries = [
                self::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED,
                self::REQUEST_QUERY_MASS_ACTION,
                self::REQUEST_QUERY_EXPORT,
                self::REQUEST_QUERY_PAGE,
                self::REQUEST_QUERY_LIMIT,
                self::REQUEST_QUERY_ORDER,
                self::REQUEST_QUERY_TEMPLATE,
                self::REQUEST_QUERY_RESET,
                MassActionColumn::ID,
            ];

            foreach ($requestQueries as $request_query) {
                unset($session[$request_query]);
            }

            foreach ($session as $columnId => $sessionFilter) {
                if (isset($sessionFilter['operator'])) {
                    $operator = $sessionFilter['operator'];
                    unset($sessionFilter['operator']);
                } else {
                    $operator = $this->getColumn($columnId)->getDefaultOperator();
                }

                if (!isset($sessionFilter['to']) && isset($sessionFilter['from'])) {
                    $sessionFilter = $sessionFilter['from'];
                }

                $this->sessionFilters[$columnId] = new Filter($operator, $sessionFilter);
            }
        }

        return $this->sessionFilters;
    }

    public function getFilter(string $columnId): ?Filter
    {
        if ($this->hash === null) {
            throw new \Exception(self::GET_FILTERS_NO_REQUEST_HANDLED_EX_MSG);
        }

        $sessionFilters = $this->getFilters();

        return isset($sessionFilters[$columnId]) ? $sessionFilters[$columnId] : null;
    }

    public function hasFilter(string $columnId): bool
    {
        if ($this->hash === null) {
            throw new \Exception(self::HAS_FILTER_NO_REQUEST_HANDLED_EX_MSG);
        }

        return $this->getFilter($columnId) !== null;
    }
}
