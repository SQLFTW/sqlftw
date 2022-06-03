<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\QualifiedName;
use function array_map;
use function implode;

class InsertValuesCommand extends InsertOrReplaceCommand implements InsertCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<array<ExpressionNode>> */
    private $rows;

    /** @var string|null */
    private $alias;

    /** @var non-empty-array<string>|null */
    private $columnAliases;

    /** @var OnDuplicateKeyActions|null */
    private $onDuplicateKeyActions;

    /**
     * @param non-empty-array<array<ExpressionNode>> $rows
     * @param array<ColumnIdentifier>|null $columns
     * @param non-empty-array<string>|null $columnAliases
     * @param non-empty-array<string>|null $partitions
     */
    public function __construct(
        QualifiedName $table,
        array $rows,
        ?array $columns = null,
        ?string $alias = null,
        ?array $columnAliases = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?OnDuplicateKeyActions $onDuplicateKeyActions = null
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->rows = $rows;
        $this->alias = $alias;
        $this->columnAliases = $columnAliases;
        $this->onDuplicateKeyActions = $onDuplicateKeyActions;
    }

    /**
     * @return non-empty-array<array<ExpressionNode>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return non-empty-array<string>|null
     */
    public function getColumnAliases(): ?array
    {
        return $this->columnAliases;
    }

    public function getOnDuplicateKeyAction(): ?OnDuplicateKeyActions
    {
        return $this->onDuplicateKeyActions;
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
