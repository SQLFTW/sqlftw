<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer\Context\Source;

use SqlFtw\Analyzer\Context\Provider\SourceProvider;
use SqlFtw\Connection\Connection;
use SqlFtw\Connection\ConnectionException;
use SqlFtw\Platform\Features\MysqlError;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\UserName;
use function str_replace;

// index: pg_indexes.indexdef
// view:  pg_views.definition
class PostgreSourceProvider implements SourceProvider
{

    private const SCHEMA_NOT_FOUND = MysqlError::ER_BAD_DB_ERROR;
    private const TABLE_NOT_FOUND = MysqlError::ER_NO_SUCH_TABLE;
    private const VIEW_NOT_FOUND = MysqlError::ER_NO_SUCH_TABLE;
    private const EVENT_NOT_FOUND = MysqlError::ER_EVENT_DOES_NOT_EXIST;
    private const FUNCTION_NOT_FOUND = MysqlError::ER_SP_DOES_NOT_EXIST;
    private const PROCEDURE_NOT_FOUND = MysqlError::ER_SP_DOES_NOT_EXIST;
    private const TRIGGER_NOT_FOUND = MysqlError::ER_TRG_DOES_NOT_EXIST;
    private const USER_NOT_FOUND = MysqlError::ER_CANNOT_USER;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getSchemaSource(string $name): ?string
    {
        $result = $this->connection->query("SELECT schema_name, schema_owner FROM information_schema.schemata WHERE schema_name = " . $this->quoteString($name))->all();
        if ($result === []) {
            return null;
        }
        $name = $result[0]['schema_name'];
        $owner = $result[0]['schema_owner'];

        return "CREATE SCHEMA \"{$name}\" AUTHORIZATION \"{$owner}\"";
    }

    public function getTableSource(ObjectIdentifier $name): ?string
    {
        if ($name instanceof QualifiedName) {
            $quotedName = $this->quoteName($name->getSchema()) . '.' . $this->quoteName($name->getName());
        } else {
            $quotedName = $this->quoteName($name->getName());
        }

        try {
            $result = $this->connection->query("SHOW CREATE TABLE " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::TABLE_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create Table'];
    }

    public function getViewSource(ObjectIdentifier $name): ?string
    {
        if ($name instanceof QualifiedName) {
            $quotedName = $this->quoteName($name->getSchema()) . '.' . $this->quoteName($name->getName());
        } else {
            $quotedName = $this->quoteName($name->getName());
        }

        try {
            $result = $this->connection->query("SHOW CREATE VIEW " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::VIEW_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create View'];
    }

    public function getEventSource(ObjectIdentifier $name): ?string
    {
        if ($name instanceof QualifiedName) {
            $quotedName = $this->quoteName($name->getSchema()) . '.' . $this->quoteName($name->getName());
        } else {
            $quotedName = $this->quoteName($name->getName());
        }

        try {
            $result = $this->connection->query("SHOW CREATE EVENT " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::EVENT_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create Event'];
    }

    public function getFunctionSource(ObjectIdentifier $name): ?string
    {
        if ($name instanceof QualifiedName) {
            $quotedName = $this->quoteName($name->getSchema()) . '.' . $this->quoteName($name->getName());
        } else {
            $quotedName = $this->quoteName($name->getName());
        }

        try {
            $result = $this->connection->query("SHOW CREATE FUNCTION " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::FUNCTION_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create Function'];
    }

    public function getProcedureSource(ObjectIdentifier $name): ?string
    {
        if ($name instanceof QualifiedName) {
            $quotedName = $this->quoteName($name->getSchema()) . '.' . $this->quoteName($name->getName());
        } else {
            $quotedName = $this->quoteName($name->getName());
        }

        try {
            $result = $this->connection->query("SHOW CREATE PROCEDURE " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::PROCEDURE_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create Procedure'];
    }

    public function getTriggerSource(ObjectIdentifier $name): ?string
    {
        if ($name instanceof QualifiedName) {
            $quotedName = $this->quoteName($name->getSchema()) . '.' . $this->quoteName($name->getName());
        } else {
            $quotedName = $this->quoteName($name->getName());
        }

        try {
            $result = $this->connection->query("SHOW CREATE TRIGGER " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::TRIGGER_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create Trigger'];
    }

    public function getUserSource(UserName $name): ?string
    {
        $host = $name->getHost();
        if ($host !== null) {
            $quotedName = $this->quoteString($name->getUser()) . '@' . $this->quoteString($name->getHost());
        } else {
            $quotedName = $this->quoteString($name->getUser());
        }

        try {
            $result = $this->connection->query("SHOW CREATE USER " . $quotedName)->all();
        } catch (ConnectionException $e) {
            if ($e->getCode() === self::USER_NOT_FOUND) {
                return null;
            } else {
                throw $e;
            }
        }
        if ($result === null) {
            return null;
        }

        return $result[0]['Create User'];
    }

    public function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    public function quoteString(string $string): string
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }

}
