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
use SqlFtw\Sql\QualifiedName;

class ShowColumnsCommand implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $table;

    /** @var bool */
    private $full;

    /** @var string|null */
    private $like;

    /** @var ExpressionNode|null */
    private $where;

    public function __construct(QualifiedName $table, bool $full = false, ?string $like = null, ?ExpressionNode $where = null)
    {
        $this->table = $table;
        $this->full = $full;
        $this->like = $like;
        $this->where = $where;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
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
        $result .= ' COLUMNS FROM ' . $this->table->serialize($formatter);
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
