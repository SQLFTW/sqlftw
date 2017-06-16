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
use Dogma\Check;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class ReplaceSetCommand extends \SqlFtw\Sql\Dml\Insert\InsertOrReplaceCommand implements \SqlFtw\Sql\Dml\Insert\ReplaceCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $values;

    /**
     * @param \SqlFtw\Sql\Names\TableName $table
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $values (string $column => ExpressionNode $value)
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority
     * @param bool $ignore
     */
    public function __construct(
        TableName $table,
        array $values,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false
    ) {
        Check::itemsOfType($values, ExpressionNode::class);

        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->values = $values;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'INSERT' . $this->serializeBody($formatter);

        $result .= ' SET ' . implode(', ', Arr::mapPairs($this->values, function (string $column, ExpressionNode $value) use ($formatter): string {
            return $formatter->formatName($column) . ' = ' . $value->serialize($formatter);
        }));

        return $result;
    }

}
