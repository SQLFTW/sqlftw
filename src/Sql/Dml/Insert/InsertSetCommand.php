<?php declare(strict_types = 1);
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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\TableName;

class InsertSetCommand extends \SqlFtw\Sql\Dml\Insert\InsertOrReplaceCommand implements \SqlFtw\Sql\Dml\Insert\InsertCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $values;

    /** @var \SqlFtw\Sql\Dml\Insert\OnDuplicateKeyActions|null */
    private $onDuplicateKeyActions;

    /**
     * @param \SqlFtw\Sql\TableName $table
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $values (string $column => ExpressionNode $value)
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority
     * @param bool $ignore
     * @param \SqlFtw\Sql\Dml\Insert\OnDuplicateKeyActions|null $onDuplicateKeyActions
     */
    public function __construct(
        TableName $table,
        array $values,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?OnDuplicateKeyActions $onDuplicateKeyActions = null
    ) {
        Check::itemsOfType($values, ExpressionNode::class);

        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->values = $values;
        $this->onDuplicateKeyActions = $onDuplicateKeyActions;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getOnDuplicateKeyAction(): ?OnDuplicateKeyActions
    {
        return $this->onDuplicateKeyActions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INSERT' . $this->serializeBody($formatter);

        $result .= ' SET ' . implode(', ', Arr::mapPairs($this->values, function (string $column, ExpressionNode $value) use ($formatter): string {
            return $formatter->formatName($column) . ' = ' . $value->serialize($formatter);
        }));

        if ($this->onDuplicateKeyActions !== null) {
            $result .= ' ' . $this->onDuplicateKeyActions->serialize($formatter);
        }

        return $result;
    }

}
