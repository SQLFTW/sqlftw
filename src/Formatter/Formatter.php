<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Formatter;

use DateTimeInterface;
use Dogma\Arr;
use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Time;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlSerializable;
use function implode;
use function is_numeric;
use function is_string;
use function str_replace;

class Formatter
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Platform\PlatformSettings */
    private $settings;

    /** @var string */
    public $indent;

    public function __construct(PlatformSettings $settings, string $indent = '  ')
    {
        $this->settings = $settings;
        $this->indent = $indent;
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
    }

    public function formatName(string $name): string
    {
        return $this->settings->quoteAllNames()
            ? '`' . $name . '`'
            : ($this->settings->getPlatform()->getFeatures()->isReserved($name)
                ? '`' . $name . '`'
                : $name);
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
        // todo: replace entities (\n...)
        return "'" . str_replace("'", "''", $string) . "'";
    }

    /**
     * @param string[] $strings
     * @param string $separator
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
     * @param string $separator
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
        } elseif ($value === true) {
            return '1';
        } elseif ($value === false) {
            return '0';
        } elseif (is_numeric($value)) {
            return (string) $value;
        } elseif (is_string($value)) {
            return $this->formatString($value);
        } elseif ($value instanceof SqlSerializable) {
            return $value->serialize($this);
        } elseif ($value instanceof Date) {
            return $this->formatDate($value);
        } elseif ($value instanceof Time) {
            return $this->formatTime($value);
        } elseif ($value instanceof DateTimeInterface) {
            return $this->formatDateTime($value);
        }

        throw new NotImplementedException('Unknown type.');
    }

    /**
     * @param mixed[] $values
     * @param string $separator
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
