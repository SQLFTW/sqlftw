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
use SqlFtw\Sql\SqlSerializable;

class UserPrivilegeResource implements SqlSerializable
{

    public const ALL = '*';

    public ?string $schema;

    public ?string $objectName;

    public ?UserPrivilegeResourceType $objectType;

    public function __construct(?string $schema, ?string $objectName, ?UserPrivilegeResourceType $objectType)
    {
        $this->schema = $schema;
        $this->objectName = $objectName;
        $this->objectType = $objectType;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->objectType !== null) {
            $result .= $this->objectType->serialize($formatter) . ' ';
        }
        if ($this->schema !== null) {
            $result .= ($this->schema === self::ALL ? self::ALL : $formatter->formatName($this->schema));
        }
        if ($this->schema !== null && $this->objectName !== null) {
            $result .= '.';
        }
        if ($this->objectName !== null) {
            $result .= $this->objectName === self::ALL ? self::ALL : $formatter->formatName($this->objectName);
        }

        return $result;
    }

}
