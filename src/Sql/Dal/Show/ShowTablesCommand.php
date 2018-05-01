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
    private $database;

    /** @var bool */
    private $full;

    /** @var string|null */
    private $like;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $where;

    public function __construct(?string $database = null, bool $full = false, ?string $like = null, ?ExpressionNode $where = null)
    {
        $this->database = $database;
        $this->full = $full;
        $this->like = $like;
        $this->where = $where;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
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
        $result .= ' TABLES FROM ' . $formatter->formatName($this->database);
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
