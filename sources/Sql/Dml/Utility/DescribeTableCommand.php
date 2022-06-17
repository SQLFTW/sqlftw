<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Utility;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;
use function strtr;

class DescribeTableCommand extends Statement implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $table;

    /** @var string|null */
    private $column;

    public function __construct(QualifiedName $table, ?string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DESCRIBE ' . $this->table->serialize($formatter);

        if ($this->column !== null) {
            if (strtr($this->column, '_%', 'xx') === $this->column) {
                $result .= ' ' . $formatter->formatName($this->column);
            } else {
                $result .= ' ' . $formatter->formatString($this->column);
            }
        }

        return $result;
    }

}
