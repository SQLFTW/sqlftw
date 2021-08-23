<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class RolesSpecification implements SqlSerializable
{

    /** @var RolesSpecificationType */
    private $type;

    /** @var string[]|null */
    private $roles;

    /**
     * @param RolesSpecificationType $type
     * @param string[]|null $roles
     */
    public function __construct(RolesSpecificationType $type, ?array $roles = null)
    {
        if ($roles !== null) {
            Check::array($roles, 1);
            Check::itemsOfType($roles, Type::STRING);
        }

        $this->type = $type;
        $this->roles = $roles;
    }

    public function getType(): RolesSpecificationType
    {
        return $this->type;
    }

    /**
     * @return string[]|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        if ($this->type->equalsAny(RolesSpecificationType::ALL_EXCEPT)) {
            $result .= ' ';
        }
        if ($this->roles !== null) {
            $result .= $formatter->formatNamesList($this->roles);
        }

        return $result;
    }

}
