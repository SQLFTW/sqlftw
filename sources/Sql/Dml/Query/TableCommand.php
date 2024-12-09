<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\StatementImpl;

class TableCommand extends StatementImpl implements SimpleQuery
{

    public ObjectIdentifier $table;

    /** @var non-empty-list<OrderByExpression>|null */
    public ?array $orderBy;

    /** @var int|SimpleName|Placeholder|null */
    public $limit;

    /** @var int|SimpleName|Placeholder|null */
    public $offset;

    public ?SelectInto $into;

    /**
     * @param non-empty-list<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     * @param int|SimpleName|Placeholder|null $offset
     */
    public function __construct(
        ObjectIdentifier $table,
        ?array $orderBy = null,
        $limit = null,
        $offset = null,
        ?SelectInto $into = null
    ) {
        $this->table = $table;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->into = $into;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "TABLE " . $this->table->serialize($formatter);

        if ($this->orderBy !== null) {
            $result .= "\nORDER BY " . $formatter->formatSerializablesList($this->orderBy, ",\n\t");
        }

        if ($this->limit !== null) {
            $result .= "\nLIMIT " . ($this->limit instanceof SqlSerializable ? $this->limit->serialize($formatter) : $this->limit);
            if ($this->offset !== null) {
                $result .= "\nOFFSET " . ($this->offset instanceof SqlSerializable ? $this->offset->serialize($formatter) : $this->offset);
            }
        }

        if ($this->into !== null) {
            $result .= "\n" . $this->into->serialize($formatter);
        }

        return $result;
    }

}
