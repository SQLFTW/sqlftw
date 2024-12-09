<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\OptimizerHint\OptimizerHint;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use function array_map;
use function implode;

class InsertValuesCommand extends InsertOrReplaceCommand implements InsertCommand
{

    /** @var non-empty-list<list<ExpressionNode>> */
    public array $rows;

    public ?string $alias;

    /** @var non-empty-list<string>|null */
    public ?array $columnAliases;

    public ?OnDuplicateKeyActions $onDuplicateKeyActions;

    /**
     * @param non-empty-list<list<ExpressionNode>> $rows
     * @param list<ColumnIdentifier>|null $columns
     * @param non-empty-list<string>|null $columnAliases
     * @param non-empty-list<string>|null $partitions
     * @param non-empty-list<OptimizerHint>|null $optimizerHints
     */
    public function __construct(
        ObjectIdentifier $table,
        array $rows,
        ?array $columns = null,
        ?string $alias = null,
        ?array $columnAliases = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?array $optimizerHints = null,
        ?OnDuplicateKeyActions $onDuplicateKeyActions = null
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore, $optimizerHints);

        $this->rows = $rows;
        $this->alias = $alias;
        $this->columnAliases = $columnAliases;
        $this->onDuplicateKeyActions = $onDuplicateKeyActions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INSERT' . $this->serializeBody($formatter);

        $result .= ' VALUES ' . implode(', ', array_map(static function (array $values) use ($formatter): string {
            return '(' . implode(', ', array_map(static function (ExpressionNode $value) use ($formatter): string {
                return $value->serialize($formatter);
            }, $values)) . ')';
        }, $this->rows));

        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
            if ($this->columnAliases !== null) {
                $result .= '(' . implode(', ', array_map(static function (string $columnAlias) use ($formatter): string {
                    return $formatter->formatName($columnAlias);
                }, $this->columnAliases)) . ')';
            }
        }

        if ($this->onDuplicateKeyActions !== null) {
            $result .= ' ' . $this->onDuplicateKeyActions->serialize($formatter);
        }

        return $result;
    }

}
