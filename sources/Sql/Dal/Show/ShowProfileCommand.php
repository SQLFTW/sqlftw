<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class ShowProfileCommand extends Statement implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var ShowProfileType[] */
    private $types;

    /** @var int|null */
    private $queryId;

    /** @var int|null */
    private $limit;

    /** @var int|null */
    private $offset;

    /**
     * @param ShowProfileType[] $types
     */
    public function __construct(array $types, ?int $queryId, ?int $limit, ?int $offset)
    {
        $this->types = $types;
        $this->queryId = $queryId;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * @return ShowProfileType[]
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

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW PROFILE';
        if ($this->types !== []) {
            $result .= ' ' . $formatter->formatSerializablesList($this->types);
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
