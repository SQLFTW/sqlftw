<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\SqlFormatter;

use Dogma\Arr;
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Time;
use SqlFtw\Platform\Settings;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlSerializable;

class SqlFormatter
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Platform\Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function formatName(string $name): string
    {
        return '`' . $name . '`';
    }

    /**
     * @param string[] $names
     * @param string $separator
     * @return string
     */
    public function formatNamesList(array $names, string $separator = ', '): string
    {
        return implode($separator, Arr::map($names, function (string $name): string {
            return $this->formatName($name);
        }));
    }

    public function formatString(string $string): string
    {
        /// replace entities (\n...)
        return "'" . str_replace("'", "''", $string) . "'";
    }

    /**
     * @param string[] $strings
     * @return string
     */
    public function formatStringList(array $strings, string $separator = ', '): string
    {
        return implode($separator, Arr::map($strings, function (string $string): string {
            return $this->formatString($string);
        }));
    }

    /**
     * @param \SqlFtw\Sql\SqlSerializable[] $serializables
     * @return string
     */
    public function formatSerializablesList(array $serializables, string $separator = ', '): string
    {
        return implode($separator, Arr::map($serializables, function (SqlSerializable $serializable): string {
            return $serializable->serialize($this);
        }));
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function formatValue($value): string
    {
        if ($value === null) {
            return Keyword::NULL;
        } if (is_numeric($value)) {
            return (string) $value;
        } elseif (is_string($value)) {
            return $this->formatString($value);
        } elseif ($value instanceof SqlSerializable) {
            return $value->serialize($this);
        } elseif ($value instanceof Date) {
            return $this->formatDate($value);
        } elseif ($value instanceof Time) {
            return $this->formatTime($value);
        } elseif ($value instanceof \DateTimeInterface) {
            return $this->formatDateTime($value);
        }
    }

    /**
     * @param mixed[] $values
     * @return string
     */
    public function formatValuesList(array $values, string $separator = ', '): string
    {
        return implode($separator, Arr::map($values, function ($value): string {
            return $this->formatValue($value);
        }));
    }

    /**
     * @param \Dogma\Time\Date|\DateTimeInterface $date
     * @return string
     */
    public function formatDate($date): string
    {
        return "'" . $date->format(Date::DEFAULT_FORMAT) . "'";
    }

    /**
     * @param \Dogma\Time\Time|\DateTimeInterface $time
     * @return string
     */
    public function formatTime($time): string
    {
        return "'" . $time->format(Time::DEFAULT_FORMAT) . "'";
    }

    public function formatDateTime(\DateTimeInterface $dateTime): string
    {
        return "'" . $dateTime->format(DateTime::DEFAULT_FORMAT) . "'";
    }

}
