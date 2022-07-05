<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use Dogma\StaticClassMixin;

class Entity
{
    use StaticClassMixin;

    public const SCHEMA = 'schema';
    public const TABLE = 'table';
    public const VIEW = 'view';
    public const COLUMN = 'column';
    public const INDEX = 'index';
    public const CONSTRAINT = 'constraint';
    public const TRIGGER = 'trigger';
    public const ROUTINE = 'routine';
    public const EVENT = 'event';
    public const USER_VARIABLE = 'user variable';
    public const TABLESPACE = 'tablespace';
    public const PARTITION = 'partition';
    public const SERVER = 'server';
    public const LOG_FILE_GROUP = 'log file group';
    public const RESOURCE_GROUP = 'resource group';
    public const ALIAS = 'alias';
    public const LABEL = 'label';

    // other named objects:
    // - user
    // - local variable
    // - system variable
    // - function parameter
    // - index part
    // - cursor
    // - prepared statement
    // - window
    // - CTE
    // - condition
    // - savepoint
    // - enum value

}
