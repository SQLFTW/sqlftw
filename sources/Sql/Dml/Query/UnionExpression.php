<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\InvalidDefinitionException;
use function array_values;
use function count;

class UnionExpression implements Query
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<Query> */
    private $queries;

    /** @var non-empty-array<UnionType> */
    private $types;

    /** @var OrderByExpression[]|null */
    private $orderBy;

    /** @var int|null */
    private $limit;

    /** @var SelectInto|null */
    private $into;

    /**
     * @param non-empty-array<Query> $queries
     * @param non-empty-array<UnionType> $types
     * @param OrderByExpression[]|null $orderBy
     */
    public function __construct(
        array $queries,
        array $types,
        ?array $orderBy = null,
        ?int $limit = null,
        ?SelectInto $into = null
    )
    {
        if (count($queries) !== count($types) + 1) {
            throw new InvalidDefinitionException('Count of queries must be exactly 1 higher then count of union types.');
        }
        $this->queries = array_values($queries);
        $this->types = array_values($types);
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->into = $into;
    }

    /**
     * @return non-empty-array<Query>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * @return non-empty-array<UnionType>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return OrderByExpression[]|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getInto(): ?SelectInto
    {
        return $this->into;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->queries[0]->serialize($formatter);

        foreach ($this->types as $i => $type) {
            $result .= "\n\tUNION " . $type->serialize($formatter) . "\n" . $this->queries[$i + 1]->serialize($formatter);
        }

        if ($this->orderBy !== null) {
            $result .= "\n\tORDER BY " . $formatter->formatSerializablesList($this->orderBy, ",\n\t");
        }
        if ($this->limit !== null) {
            $result .= "\n\tLIMIT " . $this->limit;
        }
        if ($this->into !== null) {
            $result .= "\n\t" . $formatter->indent($this->into->serialize($formatter));
        }

        return $result;
    }

}
