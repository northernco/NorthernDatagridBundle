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
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Routing\RouterInterface;

class DateTimeColumn extends Column
{
    protected int $dateFormat = \IntlDateFormatter::MEDIUM;

    protected int $timeFormat = \IntlDateFormatter::MEDIUM;

    protected ?string $format;

    protected string $fallbackFormat = 'Y-m-d H:i:s';

    protected ?string $inputFormat;

    protected string $fallbackInputFormat = 'Y-m-d H:i:s';

    protected ?string $timezone;

    public function __initialize(array $params): void
    {
        parent::__initialize($params);

        $this->setFormat($this->getParam('format'));
        $this->setInputFormat($this->getParam('inputFormat', $this->fallbackInputFormat));
        $this->setOperators($this->getParam('operators', [
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
        ]));
        $this->setDefaultOperator($this->getParam('defaultOperator', self::OPERATOR_EQ));
        $this->setTimezone($this->getParam('timezone', date_default_timezone_get()));
    }

    public function isQueryValid(mixed $query): bool
    {
        $result = array_filter((array) $query, [$this, 'isDateTime']);

        return !empty($result);
    }

    protected function isDateTime(mixed $query): bool
    {
        return false !== \DateTime::createFromFormat($this->inputFormat, $query);
    }

    public function getFilters(string $source): array
    {
        $parentFilters = parent::getFilters($source);

        $filters = [];
        foreach ($parentFilters as $filter) {
            $filters[] = ($filter->getValue() === null) ? $filter : $filter->setValue(\DateTime::createFromFormat($this->inputFormat, $filter->getValue()));
        }

        return $filters;
    }

    public function renderCell(mixed $value, Row $row, RouterInterface $router): mixed
    {
        $value = $this->getDisplayedValue($value);

        if (is_callable($this->callback)) {
            $value = call_user_func($this->callback, $value, $row, $router);
        }

        return $value;
    }

    public function getDisplayedValue(\DateTimeInterface|string|int $value): string
    {
        if (!empty($value)) {
            $dateTime = $this->getDatetime($value, new \DateTimeZone($this->getTimezone()));

            if (isset($this->format)) {
                $value = $dateTime->format($this->format);
            } else {
                try {
                    $transformer = new DateTimeToLocalizedStringTransformer(null, $this->getTimezone(), $this->dateFormat, $this->timeFormat);
                    $value = $transformer->transform($dateTime);
                } catch (\Exception $e) {
                    $value = $dateTime->format($this->fallbackFormat);
                }
            }

            if (array_key_exists((string) $value, $this->values)) {
                $value = $this->values[$value];
            }

            return $value;
        }

        return '';
    }

    protected function getDatetime(\DateTimeInterface|string|int $data, \DateTimeZone $timezone): \DateTimeInterface
    {
        if ($data instanceof \DateTime || $data instanceof \DateTimeImmutable) {
            return $data->setTimezone($timezone);
        }

        // the format method accept array or integer
        if (is_numeric($data)) {
            $data = (int) $data;
        }

        if (is_string($data)) {
            $data = strtotime($data);
        }

        $date = new \DateTime();
        $date->setTimestamp($data);
        $date->setTimezone($timezone);

        return $date;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setInputFormat(?string $inputFormat): self
    {
        $this->inputFormat = $inputFormat;

        return $this;
    }

    public function getInputFormat(): ?string
    {
        return $this->inputFormat;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getType(): string
    {
        return 'datetime';
    }
}
