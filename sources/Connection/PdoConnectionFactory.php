<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Connection;

use Closure;
use SensitiveParameter;

/**
 * other platform DSNs:
 * cubrid:host=localhost;port=33000;dbname=demodb
 * mssql:host=localhost;dbname=testdb // +charset, appname, secure
 * sybase:host=localhost;dbname=testdb // +charset, appname, secure
 * dblib:host=localhost;dbname=testdb // +charset, appname, secure
 * firebird:dbname=hostname/port:/path/to/DATABASE.FDB // +charset, role, dialect
 * ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=testdb;HOSTNAME=11.22.33.444;PORT=56789;PROTOCOL=TCPIP;
 * informix:host=host.domain.com;service=9800;database=common_db;server=ids_server;protocol=onsoctcp;EnableScrollableCursors=1
 * sqlsrv:Server=localhost;Database=testdb // +APP, ConnectionPooling, Encrypt, Failover_Partner, LoginTimeout, MultipleActiveResultSets, QuotedId, TraceFile, TraceOn, TransactionIsolation, TrustServerCertificate, WSID
 * oci:dbname=//localhost:1521/mydb // +charset
 * odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME=localhost;PORT=50000;DATABASE=SAMPLE;PROTOCOL=TCPIP;UID=db2inst1;PWD=ibmdb2;
 * sqlite:/opt/databases/mydb.sq3
 * sqlite::memory:
 * sqlite: // (temporary file)
 */
class PdoConnectionFactory
{

    /**
     * @param int<1, 65535>|null $port
     * @param array{PDO::MYSQL_ATTR_LOCAL_INFILE?: int, PDO::MYSQL_ATTR_LOCAL_INFILE_DIRECTORY?: string, PDO::MYSQL_ATTR_INIT_COMMAND?: string, PDO::MYSQL_ATTR_MULTI_STATEMENTS?: int}|null $options
     * @param Closure<self>|null $onConnect
     */
    public static function mysql(
        string $host,
        ?int $port = null,
        ?string $user = null,
        #[SensitiveParameter]
        ?string $password = null,
        ?string $schema = null,
        ?string $charset = null,
        ?array $pdoOptions = null,
        ?Closure $onConnect = null
    ): PdoConnection
    {
        // mysql:host=localhost;port=3307;dbname=testdb // + charset
        // mysql:unix_socket=/tmp/mysql.sock;dbname=testdb // + charset
        $dsn = "mysql:host={$host}";
        if ($port !== null) {
            $dsn .= ";port={$port}";
        }
        if ($schema !== null) {
            $dsn .= ";dbname={$schema}";
        }
        if ($charset !== null) {
            $dsn .= ";charset={$charset}";
        }

        return new PdoConnection($dsn, $user, $password, $pdoOptions, $onConnect);
    }

    /**
     * @param int<1, 65535>|null $port
     * @param array{PDO::MYSQL_ATTR_LOCAL_INFILE?: int, PDO::MYSQL_ATTR_LOCAL_INFILE_DIRECTORY?: string, PDO::MYSQL_ATTR_INIT_COMMAND?: string, PDO::MYSQL_ATTR_MULTI_STATEMENTS?: int}|null $options
     * @param Closure<self>|null $onConnect
     */
    public static function postgre(
        string $host,
        ?int $port = null,
        ?string $user = null,
        #[SensitiveParameter]
        ?string $password = null,
        ?string $database = null,
        ?string $schema = null,
        ?string $charset = null,
        ?array $pdoOptions = null,
        ?Closure $onConnect = null
    ): PdoConnection
    {
        // pgsql:host=localhost;port=5432;dbname=testdb;user=bruce;password=mypass
        // + sslmode(disable|allow|prefer|require|verify-ca|verify-full), sslcert, sslkey, sslrootcert, sslcrl, application_name
        $dsn = "pgsql:host={$host}";
        if ($port !== null) {
            $dsn .= ";port={$port}";
        }
        if ($schema !== null) {
            $dsn .= ";dbname={$database}";
        }
        if ($charset !== null) {
            $dsn .= ";charset={$charset}";
        }

        if ($schema !== null) {
            $connect = function (PdoConnection $connection) use ($onConnect, $schema): void {
                $connection->execute('USE ' . $schema);
                if ($onConnect !== null) {
                    $onConnect($this);
                }
            };
        } else {
            $connect = $onConnect;
        }

        return new PdoConnection($dsn, $user, $password, $pdoOptions, $connect);
    }

}