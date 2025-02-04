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
use SqlFtw\Sql\UserName;

class RolesSpecification extends Node
{

    public RolesSpecificationType $type;

    /** @var non-empty-list<UserName>|null */
    public ?array $roles;

    /**
     * @param non-empty-list<UserName>|null $roles
     */
    public function __construct(RolesSpecificationType $type, ?array $roles = null)
    {
        $this->type = $type;
        $this->roles = $roles;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        if ($this->type->equalsAnyValue(RolesSpecificationType::ALL_EXCEPT)) {
            $result .= ' ';
        }
        if ($this->roles !== null) {
            $result .= $formatter->formatNodesList($this->roles);
        }

        return $result;
    }

}
