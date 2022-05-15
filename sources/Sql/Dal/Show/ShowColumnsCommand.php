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

    /** @var string|null */
    private $like;

    /** @var ExpressionNode|null */
    private $where;

    /** @var bool */
    private $full;

    /** @var bool */
    private $extended;

    public function __construct(
        QualifiedName $table,
        ?string $like = null,
        ?ExpressionNode $where = null,
        bool $full = false,
        bool $extended = false
    )
    {
        $this->table = $table;
        $this->like = $like;
        $this->where = $where;
        $this->full = $full;
        $this->extended = $extended;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getLike(): ?string
    {
        return $this->like;
    }

    public function getWhere(): ?ExpressionNode
    {
        return $this->where;
    }

    public function full(): bool
    {
        return $this->full;
    }

    public function extended(): bool
    {
        return $this->extended;
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
        } elseif ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
