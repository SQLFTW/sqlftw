<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;

class ShowProfileCommand extends ShowCommand
{

    /** @var list<ShowProfileType> */
    public array $types;

    public ?int $queryId;

    public ?int $limit;

    public ?int $offset;

    /**
     * @param list<ShowProfileType> $types
     */
    public function __construct(array $types, ?int $queryId, ?int $limit, ?int $offset)
    {
        $this->types = $types;
        $this->queryId = $queryId;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW PROFILE';
        if ($this->types !== []) {
            $result .= ' ' . $formatter->formatNodesList($this->types);
        }

        if ($this->queryId !== null) {
            $result .= ' FOR QUERY ' . $this->queryId;
        }

        if ($this->limit !== null) {
            $result .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $result .= ' OFFSET ' . $this->offset;
            }
        }

        return $result;
    }

}
