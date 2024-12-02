<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Features;

class Feature
{

    public const ALTER_INSTANCE = 'alter-instance';
    public const ALTER_INSTANCE_2 = 'alter-instance-2';
    public const COLUMN_VISIBILITY = 'column-visibility';
    public const CREATE_ROUTINE_IF_NOT_EXISTS = 'create-routine-if-not-exists';
    public const ENGINE_ATTRIBUTE = 'engine-attributes';
    public const FUNCTIONAL_INDEXES = 'functional-indexes';
    public const GROUP_REPLICATION_CREDENTIALS = 'group-replication-credentials';
    public const INSTALL_COMPONENT_SET = 'install-component-set'; // 8.0.33 https://blogs.oracle.com/mysql/post/announcing-mysql-server-8033
    public const OPTIMIZER_HINTS = 'optimizer-hints'; // /*+ ... */
    public const REQUIRE_TABLE_PRIMARY_KEY_CHECK_GENERATE = 'require-table-primary-key-check-generate'; // >=8.0.32
    public const SCHEMA_ENCRYPTION = 'schema-encryption';
    public const SCHEMA_READ_ONLY = 'schema-read-only';
    public const SECONDARY_ENGINE_ATTRIBUTE = 'secondary-engine-attributes';

    // deprecation of old features
    public const DEPRECATED_FULL_IS_VALID_NAME = 'full-is-valid-name'; // depr. 8.0.32
    public const DEPRECATED_GROUP_BY_ORDERING = 'group-by-ordering';
    public const DEPRECATED_IDENTIFIED_BY_PASSWORD = 'identified-by-password';
    public const DEPRECATED_OLD_NULL_LITERAL = 'old-null-literal'; // \N
    public const DEPRECATED_UNQUOTED_NAMES_CAN_START_WITH_DOLLAR_SIGN = 'unquoted-names-can-start-with-dollar-sign'; // depr. 8.0.32

}
