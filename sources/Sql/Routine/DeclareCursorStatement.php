<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\Statement;

class DeclareCursorStatement extends Statement implements SqlSerializable
{

    /** @var string */
    private $name;

    /** @var Query */
    private $query;

    public function __construct(string $name, Query $query)
    {
        $this->name = $name;
        $this->query = $query;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DECLARE ' . $formatter->formatName($this->name) . ' CURSOR FOR ' . $this->query->serialize($formatter);
    }

}
