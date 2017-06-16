<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\Arr;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class ReplaceValuesCommand extends \SqlFtw\Sql\Dml\Insert\InsertOrReplaceCommand implements \SqlFtw\Sql\Dml\Insert\ReplaceCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[][] */
    private $rows;

    /**
     * @param \SqlFtw\Sql\Names\TableName $table
     * @param \SqlFtw\Sql\Expression\ExpressionNode[][] $rows
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority
     * @param bool $ignore
     */
    public function __construct(
        TableName $table,
        array $rows,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->rows = $rows;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]|\SqlFtw\Sql\Expression\ExpressionNode[][]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'REPLACE' . $this->serializeBody($formatter);

        $result .= implode(', ', Arr::map($this->rows, function (array $values) use ($formatter): string {
            return '(' . implode(', ', Arr::map($values, function (ExpressionNode $value) use ($formatter): string {
                return $value->serialize($formatter);
            })) . ')';
        }));

        return $result;
    }

}
