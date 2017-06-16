<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\Check;
use SqlFtw\SqlFormatter\SqlFormatter;

class ShowProfileCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var \SqlFtw\Sql\Dal\Show\ShowProfileType[] */
    private $types;

    /** @var int|null */
    private $queryId;

    /** @var int|null */
    private $limit;

    /** @var  int|null */
    private $offset;

    /**
     * @param \SqlFtw\Sql\Dal\Show\ShowProfileType[] $types
     * @param int|null $queryId
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(array $types, ?int $queryId = null, ?int $limit, ?int $offset)
    {
        Check::array($types, 1);
        Check::itemsOfType($types, ShowProfileType::class);

        $this->types = $types;
        $this->queryId = $queryId;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * @return \SqlFtw\Sql\Dal\Show\ShowProfileType[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getQueryId(): ?int
    {
        return $this->queryId;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'SHOW PROFILE ' . $formatter->formatSerializablesList($this->types);

        if ($this->queryId !== null) {
            $result .= ' FOR QUERY ' . $this->queryId;
        }

        if ($this->limit) {
            $result .= ' LIMIT ' . $this->limit;
            if ($this->offset) {
                $result .= ' OFFSET ' . $this->offset;
            }
        }

        return $result;
    }

}
