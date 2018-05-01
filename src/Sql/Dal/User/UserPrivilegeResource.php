<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;

class UserPrivilegeResource implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    public const ALL = '*';

    /** @var string|null */
    private $schema;

    /** @var string */
    private $objectName;

    /** @var \SqlFtw\Sql\Dal\User\UserPrivilegeResourceType|null */
    private $objectType;

    public function __construct(?string $schema, string $objectName, ?UserPrivilegeResourceType $objectType)
    {
        $this->schema = $schema;
        $this->objectName = $objectName;
        $this->objectType = $objectType;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function getObjectType(): ?UserPrivilegeResourceType
    {
        return $this->objectType;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->objectType !== null) {
            $result .= $this->objectType->serialize($formatter) . ' ';
        }
        if ($this->schema !== null) {
            $result .= ($this->schema === self::ALL ? self::ALL : $formatter->formatName($this->schema)) . '.';
        }
        $result .= $this->objectName === self::ALL ? self::ALL : $formatter->formatName($this->objectName);

        return $result;
    }

}
