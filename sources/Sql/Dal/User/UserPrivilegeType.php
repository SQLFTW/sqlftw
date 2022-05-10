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

    // static
    public const ALL = Keyword::ALL; // [PRIVILEGES]
    public const ALTER = Keyword::ALTER;
    public const ALTER_ROUTINE = Keyword::ALTER . ' ' . Keyword::ROUTINE;
    public const CREATE = Keyword::CREATE;
    public const CREATE_ROLE = Keyword::CREATE . ' ' . Keyword::ROLE;
    public const CREATE_ROUTINE = Keyword::CREATE . ' ' . Keyword::ROUTINE;
    public const CREATE_TABLESPACE = Keyword::CREATE . ' ' . Keyword::TABLESPACE;
    public const CREATE_TEMPORARY_TABLES = Keyword::CREATE . ' ' . Keyword::TEMPORARY . ' ' . Keyword::TABLES;
    public const CREATE_USER = Keyword::CREATE . ' ' . Keyword::USER;
    public const CREATE_VIEW = Keyword::CREATE . ' ' . Keyword::VIEW;
    public const DELETE = Keyword::DELETE;
    public const DROP = Keyword::DROP;
    public const DROP_ROLE = Keyword::DROP . ' ' . Keyword::ROLE;
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

    // dynamic
    public const APPLICATION_PASSWORD_ADMIN	= 'APPLICATION_PASSWORD_ADMIN';
    public const AUDIT_ABORT_EXEMPT	= 'AUDIT_ABORT_EXEMPT';
    public const AUDIT_ADMIN = 'AUDIT_ADMIN';
    public const AUTHENTICATION_POLICY_ADMIN = 'AUTHENTICATION_POLICY_ADMIN';
    public const BACKUP_ADMIN = 'BACKUP_ADMIN';
    public const BINLOG_ADMIN = 'BINLOG_ADMIN';
    public const BINLOG_ENCRYPTION_ADMIN = 'BINLOG_ENCRYPTION_ADMIN';
    public const CLONE_ADMIN = 'CLONE_ADMIN';
    public const CONNECTION_ADMIN = 'CONNECTION_ADMIN';
    public const ENCRYPTION_KEY_ADMIN = 'ENCRYPTION_KEY_ADMIN';
    public const FIREWALL_ADMIN = 'FIREWALL_ADMIN';
    public const FIREWALL_EXEMPT = 'FIREWALL_EXEMPT';
    public const FIREWALL_USER = 'FIREWALL_USER';
    public const FLUSH_OPTIMIZER_COSTS = 'FLUSH_OPTIMIZER_COSTS';
    public const FLUSH_STATUS = 'FLUSH_STATUS';
    public const FLUSH_TABLES = 'FLUSH_TABLES';
    public const FLUSH_USER_RESOURCES = 'FLUSH_USER_RESOURCES';
    public const GROUP_REPLICATION_ADMIN = 'GROUP_REPLICATION_ADMIN';
    public const GROUP_REPLICATION_STREAM = 'GROUP_REPLICATION_STREAM';
    public const INNODB_REDO_LOG_ARCHIVE = 'INNODB_REDO_LOG_ARCHIVE';
    public const NDB_STORED_USER = 'NDB_STORED_USER';
    public const PASSWORDLESS_USER_ADMIN = 'PASSWORDLESS_USER_ADMIN';
    public const PERSIST_RO_VARIABLES_ADMIN = 'PERSIST_RO_VARIABLES_ADMIN';
    public const REPLICATION_APPLIER = 'REPLICATION_APPLIER';
    public const REPLICATION_SLAVE_ADMIN = 'REPLICATION_SLAVE_ADMIN';
    public const RESOURCE_GROUP_ADMIN = 'RESOURCE_GROUP_ADMIN';
    public const RESOURCE_GROUP_USER = 'RESOURCE_GROUP_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const SENSITIVE_VARIABLES_OBSERVER = 'SENSITIVE_VARIABLES_OBSERVER';
    public const SERVICE_CONNECTION_ADMIN = 'SERVICE_CONNECTION_ADMIN';
    public const SESSION_VARIABLES_ADMIN = 'SESSION_VARIABLES_ADMIN';
    public const SET_USER_ID = 'SET_USER_ID';
    public const SHOW_ROUTINE = 'SHOW_ROUTINE';
    public const SYSTEM_USER = 'SYSTEM_USER';
    public const SYSTEM_VARIABLES_ADMIN = 'SYSTEM_VARIABLES_ADMIN';
    public const TABLE_ENCRYPTION_ADMIN = 'TABLE_ENCRYPTION_ADMIN';
    public const VERSION_TOKEN_ADMIN = 'VERSION_TOKEN_ADMIN';
    public const XA_RECOVER_ADMIN = 'XA_RECOVER_ADMIN';

    /**
     * @return array<string, string[]|null>
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
