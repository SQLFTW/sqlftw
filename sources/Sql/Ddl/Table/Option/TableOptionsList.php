<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\StorageType;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Util\TypeChecker;
use function implode;
use function is_int;

/**
 * @phpstan-type TableOptionValue int|string|bool|Charset|Collation|TableCompression|StorageEngine|StorageType|TableInsertMethod|ThreeStateValue|TableRowFormat|QualifiedName[]
 */
class TableOptionsList
{
    use StrictBehaviorMixin;

    /** @var array<TableOptionValue> */
    private $options = [];

    /**
     * @param array<TableOptionValue> $options (string $name => mixed $value)
     */
    public function __construct(array $options)
    {
        $types = TableOption::getTypes();

        foreach ($options as $option => $value) {
            if (is_int($option)) {
                switch (true) {
                    case $value instanceof StorageEngine:
                        $this->options[TableOption::ENGINE] = $value;
                        break;
                    case $value instanceof StorageType:
                        $this->options[TableOption::STORAGE] = $value;
                        break;
                    case $value instanceof Charset:
                        $this->options[TableOption::CHARACTER_SET] = $value;
                        break;
                    case $value instanceof Collation:
                        $this->options[TableOption::COLLATE] = $value;
                        break;
                    case $value instanceof TableCompression:
                        $this->options[TableOption::COMPRESSION] = $value;
                        break;
                    case $value instanceof TableInsertMethod:
                        $this->options[TableOption::INSERT_METHOD] = $value;
                        break;
                    case $value instanceof TableRowFormat:
                        $this->options[TableRowFormat::class] = $value;
                        break;
                }
            } elseif ($option === TableOption::UNION) {
                TypeChecker::check($value, QualifiedName::class . '[]');
                $this->options[$option] = $value;
            } else {
                if (!TableOption::isValid($option)) {
                    throw new InvalidDefinitionException("Invalid table option '$option'.");
                }
                TypeChecker::check($value, $types[$option]);
                $this->options[$option] = $value;
            }
        }
    }

    /**
     * @return array<TableOptionValue>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return mixed|null $option
     */
    public function get(string $option)
    {
        if (!TableOption::isValid($option)) {
            throw new InvalidDefinitionException("Invalid table option '$option'.");
        }

        return $this->options[$option] ?? null;
    }

    /**
     * @param mixed|null $value
     */
    public function set(string $option, $value): void
    {
        if (!TableOption::isValid($option)) {
            throw new InvalidDefinitionException("Invalid table option '$option'.");
        }
        TypeChecker::check($value, TableOption::getTypes()[$option], $option);

        $this->options[$option] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setDefault(string $option, $value): void
    {
        if (!TableOption::isValid($option)) {
            throw new InvalidDefinitionException("Invalid table option '$option'.");
        }
        $types = TableOption::getTypes();
        if (!isset($this->options[$option])) {
            TypeChecker::check($value, $types[$option], $option);
            $this->options[$option] = $value;
        }
    }

    public function isEmpty(): bool
    {
        return $this->options === [];
    }

    public function serialize(Formatter $formatter, string $itemSeparator, string $valueSeparator): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        return implode($itemSeparator, Arr::filter(Arr::mapPairs(
            $this->options,
            static function (string $option, $value) use ($formatter, $valueSeparator): ?string {
                if ($value === null) {
                    return null;
                } elseif ($value instanceof SqlSerializable) {
                    return $option . $valueSeparator . $value->serialize($formatter);
                } elseif ($option === TableOption::ENCRYPTION) {
                    return $option . $valueSeparator . ($value ? "'Y'" : "'N'");
                } elseif ($option === TableOption::UNION) {
                    return $option . $valueSeparator . '(' . $formatter->formatSerializablesList($value) . ')';
                } elseif ($option === TableOption::AUTO_INCREMENT) {
                    return $option . $valueSeparator . $value;
                } else {
                    return $option . $valueSeparator . $formatter->formatValue($value);
                }
            }
        )));
    }

}
