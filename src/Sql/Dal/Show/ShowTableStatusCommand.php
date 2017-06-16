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
use SqlFtw\SqlFormatter\SqlFormatter;

class ShowTableStatusCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var string|null */
    private $databaseName;

    /** @var string|null */
    private $like;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $where;

    public function __construct(?string $databaseName = null, ?string $like = null, ?ExpressionNode $where = null)
    {
        $this->databaseName = $databaseName;
        $this->like = $like;
        $this->where = $where;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
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
        $result = 'SHOW TABLE STATUS';
        if ($this->databaseName) {
            $result .= ' FROM ' . $formatter->formatName($this->databaseName);
        }
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
