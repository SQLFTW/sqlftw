<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\SqlFormatter\SqlFormatter;

class UserPrivilegeResource implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    public const ALL = '*';

    /** @var string|null */
    private $databaseName;

    /** @var string */
    private $objectName;

    /** @var \SqlFtw\Sql\Dal\User\UserPrivilegeResourceType|null */
    private $objectType;

    public function __construct(?string $databaseName, string $objectName, ?UserPrivilegeResourceType $objectType)
    {
        $this->databaseName = $databaseName;
        $this->objectName = $objectName;
        $this->objectType = $objectType;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function getObjectType(): ?UserPrivilegeResourceType
    {
        return $this->objectType;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = '';
        if ($this->objectType !== null) {
            $result .= $this->objectType->serialize($formatter) . ' ';
        }
        if ($this->databaseName !== null) {
            $result .= ($this->databaseName === self::ALL ? self::ALL : $formatter->formatName($this->databaseName)) . '.';
        }
        $result .= $this->objectName === self::ALL ? self::ALL : $formatter->formatName($this->objectName);

        return $result;
    }

}
