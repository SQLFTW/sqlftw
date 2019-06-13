<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;

class UserPrivilegeType extends SqlEnum
{

    public const ALL = Keyword::ALL; // [PRIVILEGES]
    public const ALTER = Keyword::ALTER;
    public const ALTER_ROUTINE = Keyword::ALTER . ' ' . Keyword::ROUTINE;
    public const CREATE = Keyword::CREATE;
    public const CREATE_ROUTINE = Keyword::CREATE . ' ' . Keyword::ROUTINE;
    public const CREATE_TABLESPACE = Keyword::CREATE . ' ' . Keyword::TABLESPACE;
    public const CREATE_TEMPORARY_TABLES = Keyword::CREATE . ' ' . Keyword::TEMPORARY . ' ' . Keyword::TABLES;
    public const CREATE_USER = Keyword::CREATE . ' ' . Keyword::USER;
    public const CREATE_VIEW = Keyword::CREATE . ' ' . Keyword::VIEW;
    public const DELETE = Keyword::DELETE;
    public const DROP = Keyword::DROP;
    public const EVENT = Keyword::EVENT;
    public const EXECUTE = Keyword::EXECUTE;
    public const FILE = Keyword::FILE;
    public const GRANT_OPTION = Keyword::GRANT . ' ' . Keyword::OPTION;
    public const INDEX = Keyword::INDEX;
    public const INSERT = Keyword::INSERT;
    public const LOCK_TABLES = Keyword::LOCK . ' ' . Keyword::TABLES;
    public const PROCESS = Keyword::PROCESS;
    public const PROXY = Keyword::PROXY;
    public const REFERENCES = Keyword::REFERENCES;
    public const RELOAD = Keyword::RELOAD;
    public const REPLICATION_CLIENT = Keyword::REPLICATION . ' ' . Keyword::CLIENT;
    public const REPLICATION_SLAVE = Keyword::REPLICATION . ' ' . Keyword::SLAVE;
    public const SELECT = Keyword::SELECT;
    public const SHOW_DATABASES = Keyword::SHOW . ' ' . Keyword::DATABASES;
    public const SHOW_VIEW = Keyword::SHOW . ' ' . Keyword::VIEW;
    public const SHUTDOWN = Keyword::SHUTDOWN;
    public const SUPER = Keyword::SUPER;
    public const TRIGGER = Keyword::TRIGGER;
    public const UPDATE = Keyword::UPDATE;
    public const USAGE = Keyword::USAGE;

    /**
     * @return string[][]|null[]
     */
    public static function getFistAndSecondKeywords(): array
    {
        return [
            Keyword::ALL => [Keyword::PRIVILEGES],
            Keyword::ALTER => [Keyword::ROUTINE],
            Keyword::CREATE => [Keyword::ROUTINE, Keyword::TABLESPACE, Keyword::TEMPORARY, Keyword::USER, Keyword::VIEW],
            Keyword::DELETE => null,
            Keyword::DROP => null,
            Keyword::EVENT => null,
            Keyword::EXECUTE => null,
            Keyword::FILE => null,
            Keyword::GRANT => [Keyword::OPTION],
            Keyword::INDEX => null,
            Keyword::INSERT => null,
            Keyword::LOCK => [Keyword::TABLES],
            Keyword::PROCESS => null,
            Keyword::PROXY => null,
            Keyword::REFERENCES => null,
            Keyword::RELOAD => null,
            Keyword::REPLICATION => [Keyword::CLIENT, Keyword::SLAVE],
            Keyword::SELECT => null,
            Keyword::SHOW => [Keyword::DATABASES, Keyword::VIEW],
            Keyword::SHUTDOWN => null,
            Keyword::SUPER => null,
            Keyword::TRIGGER => null,
            Keyword::UPDATE => null,
            Keyword::USAGE => null,
        ];
    }

}
