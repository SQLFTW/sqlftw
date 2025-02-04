<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use Dogma\Arr;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\StorageType;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Node;
use SqlFtw\Util\TypeChecker;
use function array_filter;
use function implode;
use function is_int;

/**
 * @phpstan-import-type TableOptionValue from TableOption
 */
class TableOptionsList
{

    /** @var array<TableOption::*, TableOptionValue|null> */
    public array $options = [];

    /**
     * @param array<string|TableOption::*, TableOptionValue> $options
     */
    public function __construct(array $options)
    {
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
                        $this->options[TableOption::ROW_FORMAT] = $value;
                        break;
                }
            } elseif ($option === TableOption::UNION) {
                TypeChecker::check($value, ObjectIdentifier::class . '[]');
                $this->options[$option] = $value;
            } else {
                if (!TableOption::isValidValue($option)) {
                    throw new InvalidDefinitionException("Invalid table option '$option'.");
                }
                TypeChecker::check($value, TableOption::$types[$option]);

                // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
                /** @var TableOption::* $option */
                $this->options[$option] = $value;
            }
        }
    }

    /**
     * @param TableOption::* $option
     * @return TableOptionValue|null $option
     */
    public function get(string $option)
    {
        if (!TableOption::isValidValue($option)) {
            throw new InvalidDefinitionException("Invalid table option '$option'.");
        }

        return $this->options[$option] ?? null;
    }

    /**
     * @param TableOption::* $option
     * @param TableOptionValue|null $value
     */
    public function set(string $option, $value): void
    {
        if (!TableOption::isValidValue($option)) {
            throw new InvalidDefinitionException("Invalid table option '$option'.");
        }
        TypeChecker::check($value, TableOption::$types[$option], $option);

        $this->options[$option] = $value;
    }

    /**
     * @param TableOption::* $option
     * @param TableOptionValue $value
     */
    public function setDefault(string $option, $value): void
    {
        if (!TableOption::isValidValue($option)) {
            throw new InvalidDefinitionException("Invalid table option '$option'.");
        }
        if (!isset($this->options[$option])) {
            TypeChecker::check($value, TableOption::$types[$option], $option);
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

        return implode($itemSeparator, array_filter(Arr::mapPairs( // @phpstan-ignore arrayFilter.strict
            $this->options,
            static function (string $option, $value) use ($formatter, $valueSeparator): ?string {
                if ($value === null) {
                    return null;
                } elseif ($value instanceof Node) {
                    return $option . $valueSeparator . $value->serialize($formatter);
                } elseif ($option === TableOption::ENCRYPTION) {
                    return $option . $valueSeparator . ($value ? "'Y'" : "'N'");
                } elseif ($option === TableOption::UNION) {
                    return $option . $valueSeparator . '(' . $formatter->formatNodesList($value) . ')';
                } elseif ($option === TableOption::AUTO_INCREMENT) {
                    return $option . $valueSeparator . $value;
                } elseif ($option === TableOption::TABLESPACE) {
                    return $option . $valueSeparator . $formatter->formatName($value);
                } else {
                    return $option . $valueSeparator . $formatter->formatValue($value);
                }
            }
        )));
    }

}
