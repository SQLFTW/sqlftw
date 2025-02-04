<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Node;

class UserPrivilege extends Node
{

    public UserPrivilegeType $type;

    /** @var non-empty-list<string>|null */
    public ?array $columns;

    /**
     * @param non-empty-list<string>|null $columns
     */
    public function __construct(UserPrivilegeType $type, ?array $columns)
    {
        $this->type = $type;
        $this->columns = $columns;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        if ($this->columns !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columns) . ')';
        }

        return $result;
    }

}
