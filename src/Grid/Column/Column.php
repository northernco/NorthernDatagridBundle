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

namespace APY\DataGridBundle\Grid\Column;

use APY\DataGridBundle\Grid\Filter;
use APY\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class Column
{
    public const DEFAULT_VALUE = null;
    public const DATA_CONJUNCTION = 0;
    public const DATA_DISJUNCTION = 1;
    public const OPERATOR_EQ = 'eq';
    public const OPERATOR_NEQ = 'neq';
    public const OPERATOR_LT = 'lt';
    public const OPERATOR_LTE = 'lte';
    public const OPERATOR_GT = 'gt';
    public const OPERATOR_GTE = 'gte';
    public const OPERATOR_BTW = 'btw';
    public const OPERATOR_BTWE = 'btwe';
    public const OPERATOR_LIKE = 'like';
    public const OPERATOR_NLIKE = 'nlike';
    public const OPERATOR_RLIKE = 'rlike';
    public const OPERATOR_LLIKE = 'llike';
    public const OPERATOR_SLIKE = 'slike'; //simple/strict LIKE
    public const OPERATOR_NSLIKE = 'nslike';
    public const OPERATOR_RSLIKE = 'rslike';
    public const OPERATOR_LSLIKE = 'lslike';
    public const OPERATOR_ISNULL = 'isNull';
    public const OPERATOR_ISNOTNULL = 'isNotNull';

    protected static array $availableOperators = [
        self::OPERATOR_EQ,
        self::OPERATOR_NEQ,
        self::OPERATOR_LT,
        self::OPERATOR_LTE,
        self::OPERATOR_GT,
        self::OPERATOR_GTE,
        self::OPERATOR_BTW,
        self::OPERATOR_BTWE,
        self::OPERATOR_LIKE,
        self::OPERATOR_NLIKE,
        self::OPERATOR_RLIKE,
        self::OPERATOR_LLIKE,
        self::OPERATOR_SLIKE,
        self::OPERATOR_NSLIKE,
        self::OPERATOR_RSLIKE,
        self::OPERATOR_LSLIKE,
        self::OPERATOR_ISNULL,
        self::OPERATOR_ISNOTNULL,
    ];

    /**
     * Align.
     */
    public const ALIGN_LEFT = 'left';
    public const ALIGN_RIGHT = 'right';
    public const ALIGN_CENTER = 'center';

    protected static array $aligns = [
        self::ALIGN_LEFT,
        self::ALIGN_RIGHT,
        self::ALIGN_CENTER,
    ];

    protected string|int|null $id;

    protected ?string $title;

    protected ?bool $sortable;

    protected ?bool $filterable;

    protected ?bool $visible;

    protected ?\Closure $callback = null;

    protected ?int $order = null;

    protected ?int $size;

    protected ?bool $visibleForSource;

    protected ?bool $primary;

    protected ?string $align;

    protected ?string $inputType;

    protected ?string $field;

    protected ?string $role;

    protected ?string $filterType;

    protected ?array $params;

    protected bool $isSorted = false;

    protected ?AuthorizationCheckerInterface $authorizationChecker = null;

    protected ?array $data;

    protected ?bool $operatorsVisible;

    protected ?array $operators;

    protected ?string $defaultOperator;

    protected array $values = [];

    protected ?string $selectFrom;

    protected ?bool $selectMulti;

    protected ?bool $selectExpanded;

    protected bool $searchOnClick = false;

    protected string|bool|null $safe;

    protected ?string $separator;

    protected ?string $joinType;

    protected ?bool $export;

    protected ?string $class;

    protected ?bool $isManualField;

    protected ?bool $useHaving;

    protected ?bool $isAggregate;

    protected ?bool $usePrefixTitle;

    protected ?string $translationDomain;

    protected int $dataJunction = self::DATA_CONJUNCTION;

    private array $defaultOperators = [];

    public function __construct(
        ?array $params = null,
        ?array $defaultOperators = null
    )
    {
        if ($defaultOperators !== null) {
            $this->setDefaultOperators($defaultOperators);
        }

        $this->__initialize((array)$params);
    }

    public function __initialize(array $params): void
    {
        $this->params = $params;
        $this->setId($this->getParam('id'));
        $this->setTitle($this->getParam('title', $this->getParam('field')));
        $this->setSortable($this->getParam('sortable', true));
        $this->setVisible($this->getParam('visible', true));
        $this->setSize($this->getParam('size', -1));
        $this->setFilterable($this->getParam('filterable', true));
        $this->setVisibleForSource($this->getParam('source', false));
        $this->setPrimary($this->getParam('primary', false));
        $this->setAlign($this->getParam('align', self::ALIGN_LEFT));
        $this->setInputType($this->getParam('inputType', 'text'));
        $this->setField($this->getParam('field'));
        $this->setRole($this->getParam('role'));
        $this->setOrder($this->getParam('order'));
        $this->setJoinType($this->getParam('joinType'));
        $this->setFilterType($this->getParam('filter', 'input'));
        $this->setSelectFrom($this->getParam('selectFrom', 'query'));
        $this->setValues($this->getParam('values', []));
        $this->setOperatorsVisible($this->getParam('operatorsVisible', true));
        $this->setIsManualField($this->getParam('isManualField', false));
        $this->setUseHaving($this->getParam('useHaving', false));
        $this->setIsAggregate($this->getParam('isAggregate', false));
        $this->setUsePrefixTitle($this->getParam('usePrefixTitle', true));

        // Order is important for the order display
        $this->setOperators($this->getParam('operators', $this->getDefaultOperators()));
        $this->setDefaultOperator($this->getParam('defaultOperator', self::OPERATOR_LIKE));
        $this->setSelectMulti($this->getParam('selectMulti', false));
        $this->setSelectExpanded($this->getParam('selectExpanded', false));
        $this->setSearchOnClick($this->getParam('searchOnClick', false));
        $this->setSafe($this->getParam('safe', 'html'));
        $this->setSeparator($this->getParam('separator', '<br />'));
        $this->setExport($this->getParam('export'));
        $this->setClass($this->getParam('class'));
        $this->setTranslationDomain($this->getParam('translation_domain'));
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getParam(string|int $id, mixed $default = null): mixed
    {
        return isset($this->params[$id]) ? $this->params[$id] : $default;
    }

    public function renderCell(mixed $value, Row $row, RouterInterface $router): mixed
    {
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $value, $row, $router);
        }

        $value = is_bool($value) ? (int)$value : $value;
        if (array_key_exists((string)$value, $this->values)) {
            $value = $this->values[$value];
        }

        return $value;
    }

    /**
     * Set column callback.
     *
     * @param  $callback
     *
     * @return self
     */
    public function manipulateRenderCell(\Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): ?\Closure
    {
        return $this->callback;
    }

    public function setId(string|int|null $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string|int|null
    {
        return $this->id;
    }

    public function getRenderBlockId(): string|int
    {
        // For Mapping fields and aggregate dql functions
        return str_replace(['.', ':'], '_', $this->id);
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string|bool|null
    {
        return $this->title;
    }

    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function isVisible(bool $isExported = false): bool
    {
        $visible = $isExported && $this->export !== null ? $this->export : $this->visible;

        if ($visible && $this->authorizationChecker !== null && $this->getRole() !== null) {
            return $this->authorizationChecker->isGranted($this->getRole());
        }

        return $visible;
    }

    public function isSorted(): bool
    {
        return $this->isSorted;
    }

    public function setSortable(?bool $sortable): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isFiltered(): bool
    {
        if ($this->hasFromOperandFilter()) {
            return true;
        }

        if ($this->hasToOperandFilter()) {
            return true;
        }

        return $this->hasOperatorFilter();
    }

    private function hasFromOperandFilter(): bool
    {
        if (!isset($this->data['from'])) {
            return false;
        }

        if (!$this->isQueryValid($this->data['from'])) {
            return false;
        }

        return $this->data['from'] != static::DEFAULT_VALUE;
    }

    private function hasToOperandFilter(): bool
    {
        if (!isset($this->data['to'])) {
            return false;
        }

        if (!$this->isQueryValid($this->data['to'])) {
            return false;
        }

        return $this->data['to'] != static::DEFAULT_VALUE;
    }

    private function hasOperatorFilter(): bool
    {
        if (!isset($this->data['operator'])) {
            return false;
        }

        return $this->data['operator'] === self::OPERATOR_ISNULL || $this->data['operator'] === self::OPERATOR_ISNOTNULL;
    }

    public function setFilterable(?bool $filterable): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function getDefaultOrder(): string
    {
        return 'asc';
    }

    public function setOrder(?string $order): self
    {
        if ($order !== null) {
            $this->order = $order;
            $this->isSorted = true;
        }

        return $this;
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function setSize(?int $size): self
    {
        if ($size < -1) {
            throw new \InvalidArgumentException(sprintf('Unsupported column size %s, use positive value or -1 for auto resize', $size));
        }

        $this->size = $size;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setData(array|string|null $data): self
    {
        $this->data = ['operator' => $this->getDefaultOperator(), 'from' => static::DEFAULT_VALUE, 'to' => static::DEFAULT_VALUE];

        $hasValue = false;
        if (isset($data['from']) && $this->isQueryValid($data['from'])) {
            $this->data['from'] = $data['from'];
            $hasValue = true;
        }

        if (isset($data['to']) && $this->isQueryValid($data['to'])) {
            $this->data['to'] = $data['to'];
            $hasValue = true;
        }

        $isNullOperator = (isset($data['operator']) && ($data['operator'] === self::OPERATOR_ISNULL || $data['operator'] === self::OPERATOR_ISNOTNULL));
        if (($hasValue || $isNullOperator) && isset($data['operator']) && $this->hasOperator($data['operator'])) {
            $this->data['operator'] = $data['operator'];
        }

        return $this;
    }

    public function getData(): array
    {
        $result = [];

        $hasValue = false;
        if (isset($this->data['from']) && $this->data['from'] != $this::DEFAULT_VALUE) {
            $result['from'] = $this->data['from'];
            $hasValue = true;
        }

        if (isset($this->data['to']) && $this->data['to'] != $this::DEFAULT_VALUE) {
            $result['to'] = $this->data['to'];
            $hasValue = true;
        }

        $isNullOperator = (isset($this->data['operator']) && ($this->data['operator'] === self::OPERATOR_ISNULL || $this->data['operator'] === self::OPERATOR_ISNOTNULL));
        if ($hasValue || $isNullOperator) {
            $result['operator'] = $this->data['operator'];
        }

        return $result;
    }

    public function getDataAttribute(): ?array
    {
        return $this->data;
    }

    public function isQueryValid(mixed $query): bool
    {
        return true;
    }

    public function setVisibleForSource(?bool $visibleForSource): self
    {
        $this->visibleForSource = $visibleForSource;

        return $this;
    }

    public function isVisibleForSource(): bool
    {
        return $this->visibleForSource;
    }

    public function setPrimary(?bool $primary): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function setAlign(?string $align): self
    {
        if (!in_array($align, self::$aligns)) {
            throw new \InvalidArgumentException(sprintf('Unsupported align %s, just left, right and center are supported', $align));
        }

        $this->align = $align;

        return $this;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function setInputType(?string $inputType): self
    {
        $this->inputType = $inputType;

        return $this;
    }

    public function getInputType(): ?string
    {
        return $this->inputType;
    }

    public function setField(?string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setFilterType(?string $filterType): self
    {
        $this->filterType = strtolower($filterType);

        return $this;
    }

    public function getFilterType(): ?string
    {
        return $this->filterType;
    }

    public function getFilters(string $source): array
    {
        $filters = [];

        if (isset($this->data['operator']) && $this->hasOperator($this->data['operator'])) {
            if ($this instanceof ArrayColumn && in_array($this->data['operator'], [self::OPERATOR_EQ, self::OPERATOR_NEQ])) {
                $filters[] = new Filter($this->data['operator'], $this->data['from']);
            } else {
                switch ($this->data['operator']) {
                    case self::OPERATOR_BTW:
                        if ($this->data['from'] != static::DEFAULT_VALUE) {
                            $filters[] = new Filter(self::OPERATOR_GT, $this->data['from']);
                        }
                        if ($this->data['to'] != static::DEFAULT_VALUE) {
                            $filters[] = new Filter(self::OPERATOR_LT, $this->data['to']);
                        }
                        break;
                    case self::OPERATOR_BTWE:
                        if ($this->data['from'] != static::DEFAULT_VALUE) {
                            $filters[] = new Filter(self::OPERATOR_GTE, $this->data['from']);
                        }
                        if ($this->data['to'] != static::DEFAULT_VALUE) {
                            $filters[] = new Filter(self::OPERATOR_LTE, $this->data['to']);
                        }
                        break;
                    case self::OPERATOR_ISNULL:
                    case self::OPERATOR_ISNOTNULL:
                        $filters[] = new Filter($this->data['operator']);
                        break;
                    case self::OPERATOR_LIKE:
                    case self::OPERATOR_RLIKE:
                    case self::OPERATOR_LLIKE:
                    case self::OPERATOR_SLIKE:
                    case self::OPERATOR_RSLIKE:
                    case self::OPERATOR_LSLIKE:
                    case self::OPERATOR_EQ:
                        if ($this->getSelectMulti()) {
                            $this->setDataJunction(self::DATA_DISJUNCTION);
                        }
                    case self::OPERATOR_NEQ:
                    case self::OPERATOR_NLIKE:
                    case self::OPERATOR_NSLIKE:
                        foreach ((array)$this->data['from'] as $value) {
                            $filters[] = new Filter($this->data['operator'], $value);
                        }
                        break;
                    default:
                        $filters[] = new Filter($this->data['operator'], $this->data['from']);
                }
            }
        }

        return $filters;
    }

    public function setDataJunction(?int $dataJunction): self
    {
        $this->dataJunction = $dataJunction;

        return $this;
    }

    public function getDataJunction(): int
    {
        return $this->dataJunction;
    }

    public function setOperators(?array $operators): self
    {
        $this->operators = $operators;

        return $this;
    }

    public function getOperators(): array
    {
        return $this->operators;
    }

    public function setDefaultOperator(?string $defaultOperator): self
    {
        // @todo: should this be \InvalidArgumentException?
        if (!$this->hasOperator($defaultOperator)) {
            throw new \Exception($defaultOperator . ' operator not found in operators list.');
        }

        $this->defaultOperator = $defaultOperator;

        return $this;
    }

    public function getDefaultOperator(): ?string
    {
        return $this->defaultOperator;
    }

    public function hasOperator(string $operator): bool
    {
        return in_array($operator, $this->operators);
    }

    public function setOperatorsVisible(?bool $operatorsVisible): self
    {
        $this->operatorsVisible = $operatorsVisible;

        return $this;
    }

    public function getOperatorsVisible(): ?bool
    {
        return $this->operatorsVisible;
    }

    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setSelectFrom(?string $selectFrom): self
    {
        $this->selectFrom = $selectFrom;

        return $this;
    }

    public function getSelectFrom(): ?string
    {
        return $this->selectFrom;
    }

    public function getSelectMulti(): ?bool
    {
        return $this->selectMulti;
    }

    public function setSelectMulti(?bool $selectMulti): self
    {
        $this->selectMulti = $selectMulti;

        return $this;
    }

    public function getSelectExpanded(): ?bool
    {
        return $this->selectExpanded;
    }

    public function setSelectExpanded(?bool $selectExpanded): self
    {
        $this->selectExpanded = $selectExpanded;

        return $this;
    }

    public function hasDQLFunction(?array &$matches = null): false|int
    {
        $regex = '/(?P<all>(?P<field>\w+):(?P<function>\w+)(:)?(?P<parameters>\w*))$/';

        return ($matches === null) ? preg_match($regex, $this->field) : preg_match($regex, $this->field, $matches);
    }

    public function setAuthorizationChecker(?AuthorizationCheckerInterface $authorizationChecker): self
    {
        $this->authorizationChecker = $authorizationChecker;

        return $this;
    }

    public function getAuthorizationChecker(): ?AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    public function getParentType(): string
    {
        return '';
    }

    public function getType(): ?string
    {
        return '';
    }

    public function isFilterSubmitOnChange(): bool
    {
        return !$this->getSelectMulti();
    }

    public function setSearchOnClick(?bool $searchOnClick): self
    {
        $this->searchOnClick = $searchOnClick;

        return $this;
    }

    public function getSearchOnClick(): bool
    {
        return $this->searchOnClick;
    }

    public function setSafe(string|bool|null $safeOption): self
    {
        $this->safe = $safeOption;

        return $this;
    }

    public function getSafe(): string|bool|null
    {
        return $this->safe;
    }

    public function setSeparator(?string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }

    public function getSeparator(): ?string
    {
        return $this->separator;
    }

    public function setJoinType(?string $type): self
    {
        $this->joinType = $type;

        return $this;
    }

    public function getJoinType(): ?string
    {
        return $this->joinType;
    }

    public function setExport(?bool $export): self
    {
        $this->export = $export;

        return $this;
    }

    public function getExport(): ?bool
    {
        return $this->export;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setIsManualField(?bool $isManualField): self
    {
        $this->isManualField = $isManualField;

        return $this;
    }

    public function getIsManualField(): ?bool
    {
        return $this->isManualField;
    }

    public function setUseHaving(?bool $useHaving): self
    {
        $this->useHaving = $useHaving;

        return $this;
    }

    public function getUseHaving(): ?bool
    {
        return $this->useHaving;
    }

    public function setIsAggregate(?bool $isAggregate): self
    {
        $this->isAggregate = $isAggregate;

        return $this;
    }

    public function getIsAggregate(): ?bool
    {
        return $this->isAggregate;
    }

    public function getUsePrefixTitle(): ?bool
    {
        return $this->usePrefixTitle;
    }

    public function setUsePrefixTitle(?bool $usePrefixTitle): self
    {
        $this->usePrefixTitle = $usePrefixTitle;

        return $this;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function setTranslationDomain(?string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public static function getAvailableOperators(): array
    {
        return self::$availableOperators;
    }

    public function setDefaultOperators(array $operators): self
    {
        $this->defaultOperators = $operators;

        return $this;
    }

    public function getDefaultOperators(): array
    {
        return $this->defaultOperators;
    }
}
