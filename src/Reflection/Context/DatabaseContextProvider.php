<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Context;

use Dogma\Database\SimplePdo;
use PDOException;
use SqlFtw\Reflection\TableDoesNotExistException;

class DatabaseContextProvider ///implements ContextProvider
{

    private const TABLE_NOT_FOUND = 1146;

    /** @var SimplePdo */
    private $connection;

    public function __construct(SimplePdo $connection)
    {
        $this->connection = $connection;
    }

    public function getCreateTable(string $schema, string $tableName): string
    {
        try {
            return (string) $this->connection->query('SHOW CREATE TABLE %.%', $schema, $tableName)->fetchColumn('Create Table');
        } catch (PDOException $e) {
            if ($e->getCode() === self::TABLE_NOT_FOUND) {
                throw new TableDoesNotExistException($schema, $tableName, $e);
            }

            throw $e;
        }
    }

    public function getIndexSize(string $schema, string $tableName, string $indexName): ?int
    {
        $result = $this->connection->query(
            "SELECT stat_value * @@innodb_page_size AS size
                FROM mysql.innodb_index_stats
                WHERE stat_name = 'size'
                    AND database_name = ?
                    AND table_name = ?
                    AND index_name = ?",
            $schema,
            $tableName,
            $indexName
        );
        if ($result->rowCount() < 1) {
            return null;
        }

        return (int) $result->fetchColumn();
    }

    public function getIndexesSize(string $schema, string $tableName): ?int
    {
        $this->connection->query('USE %', $schema);
        $result = $this->connection->query('SHOW TABLE STATUS LIKE ?', $tableName);
        if ($result->rowCount() < 1) {
            return null;
        }

        return (int) $result->fetchColumn('Index_length');
    }

}
