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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\QualifiedName;
use function implode;

class InsertSetCommand extends InsertOrReplaceCommand implements InsertCommand
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode[] */
    private $values;

    /** @var OnDuplicateKeyActions|null */
    private $onDuplicateKeyActions;

    /**
     * @param QualifiedName $table
     * @param ExpressionNode[] $values (string $column => ExpressionNode $value)
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param InsertPriority|null $priority
     * @param bool $ignore
     * @param OnDuplicateKeyActions|null $onDuplicateKeyActions
     */
    public function __construct(
        QualifiedName $table,
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
     * @return ExpressionNode[]
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

        $result .= ' SET ' . implode(', ', Arr::mapPairs($this->values, static function (string $column, ExpressionNode $value) use ($formatter): string {
            return $formatter->formatName($column) . ' = ' . $value->serialize($formatter);
        }));

        if ($this->onDuplicateKeyActions !== null) {
            $result .= ' ' . $this->onDuplicateKeyActions->serialize($formatter);
        }

        return $result;
    }

}
