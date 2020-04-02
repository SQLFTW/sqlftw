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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\QualifiedName;
use function implode;

class InsertValuesCommand extends InsertOrReplaceCommand implements InsertCommand
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode[][] */
    private $rows;

    /** @var OnDuplicateKeyActions|null */
    private $onDuplicateKeyActions;

    /**
     * @param QualifiedName $table
     * @param ExpressionNode[][] $rows
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param InsertPriority|null $priority
     * @param bool $ignore
     * @param OnDuplicateKeyActions|null $onDuplicateKeyActions
     */
    public function __construct(
        QualifiedName $table,
        array $rows,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?OnDuplicateKeyActions $onDuplicateKeyActions = null
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->rows = $rows;
        $this->onDuplicateKeyActions = $onDuplicateKeyActions;
    }

    /**
     * @return ExpressionNode[]|ExpressionNode[][]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function getOnDuplicateKeyAction(): ?OnDuplicateKeyActions
    {
        return $this->onDuplicateKeyActions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INSERT' . $this->serializeBody($formatter);

        $result .= ' VALUES ' . implode(', ', Arr::map($this->rows, static function (array $values) use ($formatter): string {
            return '(' . implode(', ', Arr::map($values, static function (ExpressionNode $value) use ($formatter): string {
                return $value->serialize($formatter);
            })) . ')';
        }));

        if ($this->onDuplicateKeyActions !== null) {
            $result .= ' ' . $this->onDuplicateKeyActions->serialize($formatter);
        }

        return $result;
    }

}
