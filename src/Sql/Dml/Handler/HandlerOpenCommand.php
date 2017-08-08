<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Handler;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class HandlerOpenCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName */
    private $table;

    /** @var string|null */
    private $alias;

    public function __construct(TableName $table, ?string $alias = null)
    {
        $this->table = $table;
        $this->alias = $alias;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'HANDLER ' . $this->table->serialize($formatter) . ' OPEN';
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }

        return $result;
    }

}
