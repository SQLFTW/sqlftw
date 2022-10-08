<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Mysql;

trait Aliases
{

    /** @var string[] */
    private static $aliases = [
        // scopes
        'SET LOCAL ' => 'SET @@SESSION.',
        'SET SESSION ' => 'SET @@SESSION.',
        'SET GLOBAL ' => 'SET @@GLOBAL.',
        'SET PERSIST ' => 'SET @@PERSIST.',
        'SET PERSIST_ONLY ' => 'SET @@PERSIST_ONLY.',
        'SET @@local.' => 'SET @@SESSION.',
        'SET @@session.' => 'SET @@SESSION.',
        'SET @@global.' => 'SET @@GLOBAL.',
        'SET @@persist.' => 'SET @@PERSIST.',
        'SET @@persist_only.' => 'SET @@PERSIST_ONLY.',

        // bare functions
        'CURRENT_USER()' => 'CURRENT_USER',

        // IN -> FROM
        'SHOW COLUMNS IN' => 'SHOW COLUMNS FROM',
        'SHOW EVENTS IN' => 'SHOW EVENTS FROM',
        'SHOW INDEXES IN' => 'SHOW INDEXES FROM',
        'SHOW OPEN TABLES IN' => 'SHOW OPEN TABLES FROM',
        'SHOW TABLE STATUS IN' => 'SHOW TABLE STATUS FROM',
        'SHOW TABLES IN' => 'SHOW TABLES FROM',
        'SHOW TRIGGERS IN' => 'SHOW TRIGGERS FROM',

        // DATABASE -> SCHEMA
        'ALTER DATABASE' => 'ALTER SCHEMA',
        'CREATE DATABASE' => 'CREATE SCHEMA',
        'DROP DATABASE' => 'DROP SCHEMA',
        'SHOW CREATE SCHEMA' => 'SHOW CREATE DATABASE', // todo: flip?
        'SHOW SCHEMAS' => 'SHOW DATABASES', // todo: flip?

        // TABLE -> TABLES
        'LOCK TABLE ' => 'LOCK TABLES ',

        // KEY -> INDEX
        //'KEY' => 'INDEX',
        'ADD KEY' => 'ADD INDEX',
        'ADD FULLTEXT KEY' => 'ADD FULLTEXT INDEX',
        'ADD SPATIAL KEY' => 'ADD SPATIAL INDEX',
        'ADD UNIQUE KEY' => 'ADD UNIQUE INDEX',
        'DROP KEY' => 'DROP INDEX',
        'SHOW KEYS' => 'SHOW INDEXES',

        // MASTER -> BINARY
        'PURGE MASTER LOGS' => 'PURGE BINARY LOGS',
        'SHOW MASTER LOGS' => 'SHOW BINARY LOGS',

        // NO_WRITE_TO_BINLOG -> LOCAL
        'ANALYZE NO_WRITE_TO_BINLOG' => 'ANALYZE LOCAL',
        'FLUSH NO_WRITE_TO_BINLOG' => 'FLUSH LOCAL',
        'OPTIMIZE NO_WRITE_TO_BINLOG' => 'OPTIMIZE LOCAL',
        'REPAIR NO_WRITE_TO_BINLOG' => 'REPAIR LOCAL',

        // WORK -> X
        'BEGIN WORK' => 'START TRANSACTION',
        'COMMIT WORK' => 'COMMIT',
        'ROLLBACK WORK' => 'ROLLBACK',

        // DEFAULT -> X
        'DEFAULT COLLATE' => 'COLLATE',
        'DEFAULT CHARACTER SET' => 'CHARACTER SET',

        // other
        'CHARSET' => 'CHARACTER SET',
        'COLUMNS TERMINATED BY' => 'FIELDS TERMINATED BY',
        'CREATE DEFINER =' => 'CREATE DEFINER',
        'DROP PREPARE' => 'DEALLOCATE PREPARE',
        'KILL CONNECTION' => 'KILL',
        'KILL QUERY' => 'KILL',
        'REVOKE ALL PRIVILEGES' => 'REVOKE ALL',
        'SHOW STORAGE ENGINES' => 'SHOW ENGINES',
        'XA BEGIN' => 'XA START',
    ];

}
