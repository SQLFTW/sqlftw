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
use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Time;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Expression\AllLiteral;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\PrimaryLiteral;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlSerializable;
use function array_map;
use function implode;
use function is_numeric;
use function is_string;
use function str_replace;

class Formatter
{
    use StrictBehaviorMixin;

    /** @var PlatformSettings */
    private $settings;

    /** @var string */
    public $indent;

    /** @var bool */
    public $comments;

    public function __construct(
        PlatformSettings $settings,
        string $indent = '  ',
        bool $comments = false
    )
    {
        $this->settings = $settings;
        $this->indent = $indent;
        $this->comments = $comments;
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
    }

    public function indent(string $code): string
    {
        return str_replace("\n", "\n\t", $code);
    }

    public function formatName(string $name): string
    {
        // todo: sqlmode ansi uses "
        return $this->settings->quoteAllNames()
            ? '`' . $name . '`'
            : ($this->settings->getPlatform()->getFeatures()->isReserved($name)
                ? '`' . $name . '`'
                : $name);
    }

    /**
     * @param non-empty-array<string|AllLiteral|PrimaryLiteral> $names
     */
    public function formatNamesList(array $names, string $separator = ', '): string
    {
        return implode($separator, array_map(function ($name): string {
            return $name instanceof Literal ? $name->getValue() : $this->formatName($name);
        }, $names));
    }

    /**
     * @param mixed $value
     */
    public function formatValue($value): string
    {
        if ($value === null) {
            return Keyword::NULL;
        } elseif ($value === true) {
            return '1';
        } elseif ($value === false) {
            return '0';
        } elseif (is_string($value)) {
            return $this->formatString($value);
        } elseif (is_numeric($value)) {
            return (string) $value;
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
     * @param non-empty-array<mixed> $values
     */
    public function formatValuesList(array $values, string $separator = ', '): string
    {
        return implode($separator, array_map(function ($value): string {
            return $this->formatValue($value);
        }, $values));
    }

    public function formatString(string $string): string
    {
        // todo: replace entities (\n...)
        return "'" . str_replace("'", "''", $string) . "'";
    }

    /**
     * @param non-empty-array<string> $strings
     */
    public function formatStringList(array $strings, string $separator = ', '): string
    {
        return implode($separator, array_map(function (string $string): string {
            return $this->formatString($string);
        }, $strings));
    }

    /**
     * @param non-empty-array<SqlSerializable> $serializables
     */
    public function formatSerializablesList(array $serializables, string $separator = ', '): string
    {
        return implode($separator, array_map(function (SqlSerializable $serializable): string {
            return $serializable->serialize($this);
        }, $serializables));
    }

    /**
     * @param Date|DateTimeInterface $date
     */
    public function formatDate($date): string
    {
        return "'" . $date->format(Date::DEFAULT_FORMAT) . "'";
    }

    /**
     * @param Time|DateTimeInterface $time
     */
    public function formatTime($time): string
    {
        return "'" . $time->format(Time::DEFAULT_FORMAT) . "'";
    }

    public function formatDateTime(DateTimeInterface $dateTime): string
    {
        return "'" . $dateTime->format(DateTime::DEFAULT_FORMAT) . "'";
    }

}
