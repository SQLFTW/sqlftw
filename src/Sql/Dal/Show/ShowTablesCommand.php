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
use SqlFtw\Sql\Expression\ExpressionNode;

class ShowTablesCommand implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $schema;

    /** @var bool */
    private $full;

    /** @var string|null */
    private $like;

    /** @var ExpressionNode|null */
    private $where;

    public function __construct(
        ?string $schema = null,
        bool $full = false,
        ?string $like = null,
        ?ExpressionNode $where = null
    ) {
        $this->schema = $schema;
        $this->full = $full;
        $this->like = $like;
        $this->where = $where;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function isFull(): bool
    {
        return $this->full;
    }

    public function getLike(): ?string
    {
        return $this->like;
    }

    public function getWhere(): ?ExpressionNode
    {
        return $this->where;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW';
        if ($this->full) {
            $result .= ' FULL';
        }
        $result .= ' TABLES';
        if ($this->schema) {
            $result .= ' FROM ' . $formatter->formatName($this->schema);
        }
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
