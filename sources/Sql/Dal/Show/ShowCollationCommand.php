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
use SqlFtw\Sql\Expression\RootNode;

class ShowCollationCommand implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $like;

    /** @var RootNode|null */
    private $where;

    public function __construct(?string $like = null, ?RootNode $where = null)
    {
        $this->like = $like;
        $this->where = $where;
    }

    public function getLike(): ?string
    {
        return $this->like;
    }

    public function getWhere(): ?RootNode
    {
        return $this->where;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW COLLATION';
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
