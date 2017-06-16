<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Scope;
use SqlFtw\SqlFormatter\SqlFormatter;

class ShowVariablesCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var \SqlFtw\Sql\Scope|null */
    private $scope;

    /** @var string|null */
    private $like;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $where;

    public function __construct(?Scope $scope = null, ?string $like = null, ?ExpressionNode $where = null)
    {
        $this->scope = $scope;
        $this->like = $like;
        $this->where = $where;
    }

    public function getScope(): ?Scope
    {
        return $this->scope;
    }

    public function getLike(): ?string
    {
        return $this->like;
    }

    public function getWhere(): ?ExpressionNode
    {
        return $this->where;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'SHOW';
        if ($this->scope) {
            $result .= ' ' . $this->scope->serialize($formatter);
        }
        $result .= ' VARIABLES';
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
