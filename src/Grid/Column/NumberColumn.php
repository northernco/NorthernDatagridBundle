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

use APY\DataGridBundle\Grid\Row;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Routing\RouterInterface;

class NumberColumn extends Column
{
    protected static $styles = [
        'decimal'    => \NumberFormatter::DECIMAL,
        'percent'    => \NumberFormatter::PERCENT,
        'money'      => \NumberFormatter::CURRENCY,
        'currency'   => \NumberFormatter::CURRENCY,
        'duration'   => \NumberFormatter::DURATION,
        'scientific' => \NumberFormatter::SCIENTIFIC,
        'spellout'   => \NumberFormatter::SPELLOUT,
    ];

    private ?int $style = null;

    private ?string $locale;

    private ?int $precision;

    private int|bool|null $grouping;

    private ?int $roundingMode;

    private int|string|null $ruleSet;

    private ?string $currencyCode;

    private ?bool $fractional;

    private ?int $maxFractionDigits;

    public function __initialize(array $params): void
    {
        parent::__initialize($params);

        $this->setAlign($this->getParam('align', Column::ALIGN_RIGHT));
        $this->setStyle($this->getParam('style', 'decimal'));
        $this->setLocale($this->getParam('locale', \Locale::getDefault()));
        $this->setPrecision($this->getParam('precision', null));
        $this->setGrouping($this->getParam('grouping', false));
        $this->setRoundingMode($this->getParam('roundingMode', \NumberFormatter::ROUND_HALFUP));
        $this->setRuleSet($this->getParam('ruleSet'));
        $this->setCurrencyCode($this->getParam('currencyCode'));
        $this->setFractional($this->getParam('fractional', false));
        $this->setMaxFractionDigits($this->getParam('maxFractionDigits', null));
        if ($this->style === \NumberFormatter::DURATION) {
            $this->setLocale('en');
            $this->setRuleSet($this->getParam('ruleSet', '%in-numerals')); // or '%with-words'
        }

        $this->setOperators(
            $this->getParam('operators', [
                self::OPERATOR_EQ,
                self::OPERATOR_NEQ,
                self::OPERATOR_LT,
                self::OPERATOR_LTE,
                self::OPERATOR_GT,
                self::OPERATOR_GTE,
                self::OPERATOR_BTW,
                self::OPERATOR_BTWE,
                self::OPERATOR_ISNULL,
                self::OPERATOR_ISNOTNULL,
            ])
        );
        $this->setDefaultOperator($this->getParam('defaultOperator', self::OPERATOR_EQ));
    }

    public function isQueryValid(mixed $query): bool
    {
        $result = array_filter((array)$query, 'is_numeric');

        return !empty($result);
    }

    public function renderCell(mixed $value, Row $row, RouterInterface $router): mixed
    {
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $value, $row, $router);
        }

        return $this->getDisplayedValue($value);
    }

    public function getDisplayedValue(mixed $value): mixed
    {
        if ($value !== null && $value !== '') {
            $formatter = new \NumberFormatter($this->locale, $this->style);

            if ($this->precision !== null) {
                $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->precision);
                $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $this->roundingMode);
            }

            if ($this->ruleSet !== null) {
                $formatter->setTextAttribute(\NumberFormatter::DEFAULT_RULESET, $this->ruleSet);
            }

            if ($this->maxFractionDigits !== null) {
                $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->maxFractionDigits);
            }

            $formatter->setAttribute(\NumberFormatter::GROUPING_USED, $this->grouping);

            if ($this->style === \NumberFormatter::PERCENT && !$this->fractional) {
                $value /= 100;
            }

            if ($this->style === \NumberFormatter::CURRENCY) {
                if ($this->currencyCode === null) {
                    $this->currencyCode = $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
                }

                if (strlen($this->currencyCode) !== 3) {
                    throw new TransformationFailedException('Your locale definition is not complete, you have to define a language and a country. (.e.g en_US, fr_FR)');
                }

                $value = $formatter->formatCurrency($value, $this->currencyCode);
            } else {
                $value = $formatter->format($value);
            }

            if (intl_is_failure($formatter->getErrorCode())) {
                throw new TransformationFailedException($formatter->getErrorMessage());
            }

            if (array_key_exists((string)$value, $this->values)) {
                $value = $this->values[$value];
            }

            return $value;
        }

        return '';
    }

    public function getFilters(string $source): array
    {
        $parentFilters = parent::getFilters($source);

        $filters = [];
        foreach ($parentFilters as $filter) {
            // Transforme in number for ODM
            $filters[] = ($filter->getValue() === null) ? $filter : $filter->setValue($filter->getValue() + 0);
        }

        return $filters;
    }

    public function setStyle(string $style): self
    {
        if (!isset(static::$styles[$style])) {
            throw new \InvalidArgumentException(sprintf('Expected parameter of style "%s", "%s" given', implode('", "', array_keys(static::$styles)), $this->style));
        }

        $this->style = static::$styles[$style];

        return $this;
    }

    public function getStyle(): ?int
    {
        return $this->style;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setPrecision(?int $precision): self
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function setGrouping(int|bool|null $grouping): self
    {
        $this->grouping = $grouping;

        return $this;
    }

    public function getGrouping(): int|bool|null
    {
        return $this->grouping;
    }

    public function setRoundingMode(?int $roundingMode): self
    {
        $this->roundingMode = $roundingMode;

        return $this;
    }

    public function getRoundingMode(): ?int
    {
        return $this->roundingMode;
    }

    public function setRuleSet(string|int|null $ruleSet): self
    {
        $this->ruleSet = $ruleSet;

        return $this;
    }

    public function getRuleSet(): int|string|null
    {
        return $this->ruleSet;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setFractional(?bool $fractional): self
    {
        $this->fractional = $fractional;

        return $this;
    }

    public function getFractional(): ?bool
    {
        return $this->fractional;
    }

    public function setMaxFractionDigits(?int $maxFractionDigits): self
    {
        $this->maxFractionDigits = $maxFractionDigits;

        return $this;
    }

    public function getMaxFractionDigits(): ?int
    {
        return $this->maxFractionDigits;
    }

    public function getType(): string
    {
        return 'number';
    }
}
