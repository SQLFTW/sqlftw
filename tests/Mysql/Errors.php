<?php declare(strict_types = 1);

// spell-check-ignore: AUTHID AUTOPOSITION CARTESIAN CFG COLUMNACCESS CRS CURSHNDLR DBACCESS DEINITIALIZE DISPLAYWIDTH DML DOCID DOESNT DONT DUP DUPID ENDIANESS ERRMSG FCT FIELDLENGTH FIELDNAME GEOGCS GEOJSON GNO GRP HASHCHK INEXISTENT INSERTABLE INVAL JT KEYFILE KEYNAME LILABEL MGMT MULTIUPDATE MVI NCOLLATIONS NONEXISTING NONPARTITIONED NONPOSITIVE NONUNIQ NONUPD NONUPDATEABLE NORETURN NOTA OUTOFBOUNDS OUTOFMEMORY PARAMCOUNT PAREN PERFSCHEMA PROCACCESS PROJ RBDEADLOCK RBROLLBACK READLOCK REORG REPL REPREPARE REQ RETSET RLI RMERR RMFAIL ROWSIZE SBR SORTMEMORY SUBDIR SUBPART TABLEACCESS TABLENAME TEXTFILE TMPTABLE TRG TRXS UNIQ UNSUPORTED UNSUPPORT VARCOND VTOKEN XAER geo libs que

namespace SqlFtw\Tests\Mysql;

trait Errors
{

    /** @var string[] */
    private static $partiallyParsedErrors = [
        'ER_WRONG_USAGE', // various alter issues
        'ER_PARTITION_COLUMN_LIST_ERROR',
        'ER_SUBPARTITION_ERROR',
        'ER_PARTITION_WRONG_VALUES_ERROR',
        'ER_PARTITION_MAXVALUE_ERROR',
        'ER_ADD_PARTITION_NO_NEW_PARTITION',
        'ER_UNKNOWN_COLLATION',
        'ER_SP_BAD_VAR_SHADOW',
        'ER_WRONG_COLUMN_NAME',
        'ER_TOO_BIG_PRECISION',
        'ER_TABLENAME_NOT_ALLOWED_HERE',
        'ER_NO_TABLES_USED',
        'ER_SP_LILABEL_MISMATCH',
        'ER_INVALID_ENCRYPTION_REQUEST',
        'ER_ILLEGAL_HA', // invalid combination of engine and compression
        'ER_UNSUPPORTED_EXTENSION',
        'ER_WRONG_SUB_KEY', // invalid combination of index limit and column type
        'ER_WRONG_STRING_LENGTH',
        'ER_BAD_SLAVE', // ..._UNTIL_COND
        'ER_WRONG_TYPE_FOR_VAR',
        'ER_SPECIFIC_ACCESS_DENIED_ERROR',
        'ER_WRONG_TABLE_NAME',
        'ER_TRUNCATED_WRONG_VALUE', // e.g. adding column without default value
        'ER_TRUNCATED_WRONG_VALUE_FOR_FIELD',
    ];

    /** @var string[] */
    private static $ignoredErrors = [
        // todo: could be thrown by parser (no context needed)
        'ER_GENERATED_COLUMN_FUNCTION_IS_NOT_ALLOWED',
        'ER_ILLEGAL_VALUE_FOR_TYPE', // set with funny values
        'ER_TABLE_CANT_HANDLE_BLOB', // engine
        'ER_FOREIGN_KEY_WITH_ATOMIC_CREATE_SELECT',
        'ER_NONUNIQ_TABLE',
        'ER_EVENT_SAME_NAME',
        // index limits
        'ER_FUNCTIONAL_INDEX_FUNCTION_IS_NOT_ALLOWED',
        'ER_FUNCTIONAL_INDEX_ON_JSON_OR_GEOMETRY_FUNCTION',
        'ER_FUNCTIONAL_INDEX_PRIMARY_KEY',
        'ER_SPATIAL_FUNCTIONAL_INDEX',
        'ER_FUNCTIONAL_INDEX_ON_FIELD',
        'ER_FUNCTIONAL_INDEX_ON_LOB',
        'ER_FUNCTIONAL_INDEX_ROW_VALUE_IS_NOT_ALLOWED',
        // gen columns
        'ER_GENERATED_COLUMN_NAMED_FUNCTION_IS_NOT_ALLOWED',
        // lengths & limits
        'ER_TOO_BIG_DISPLAYWIDTH',
        'ER_TOO_LONG_FIELD_COMMENT',
        'ER_TOO_LONG_INDEX_COMMENT',
        'ER_TOO_LONG_TABLE_COMMENT',
        'ER_PATH_LENGTH',
        'ER_TOO_MANY_KEY_PARTS', // e.g. spatial indexes can only have one part
        'ER_TOO_BIG_FIELDLENGTH',
        'ER_INVALID_ON_UPDATE',
        // names & encoding
        'ER_INVALID_CHARACTER_STRING',
        'ER_COMMENT_CONTAINS_INVALID_STRING',
        'ER_DEFINITION_CONTAINS_INVALID_STRING',
        // variables
        'ER_UNKNOWN_TIME_ZONE',

        // for static analysis (context needed) ------------------------------------------------------------------------
        // schema
        'ER_NO_DB_ERROR',
        'ER_BAD_DB_ERROR',
        'ER_NO_SUCH_DB',
        'ER_DB_CREATE_EXISTS',
        'ER_NO_SYSTEM_SCHEMA_ACCESS',
        // tablespace
        'ER_TABLESPACE_MISSING_WITH_NAME',
        'ER_WRONG_TABLESPACE_NAME',
        'ER_RESERVED_TABLESPACE_NAME',
        'ER_TABLESPACE_ENGINE_MISMATCH',
        'ER_TABLESPACE_EXISTS',
        'ER_FEATURE_UNSUPPORTED',
        'ER_NOT_ALLOWED_COMMAND',
        'ER_DISALLOWED_OPERATION',
        // tables
        'ER_UNKNOWN_TABLE',
        'ER_TABLE_EXISTS_ERROR',
        'ER_BAD_TABLE_ERROR',
        'ER_NO_SUCH_TABLE',
        'ER_CANNOT_DISCARD_TEMPORARY_TABLE',
        'ER_PARTITION_MGMT_ON_NONPARTITIONED',
        'ER_UNSUPPORTED_ENGINE',
        'ER_ALTER_OPERATION_NOT_SUPPORTED',
        'ER_TOO_MANY_FIELDS',
        'ER_TOO_BIG_ROWSIZE',
        'ER_CHECK_NOT_IMPLEMENTED', // engine vs something
        'ER_NO_SYSTEM_TABLE_ACCESS',
        'ER_ERROR_ON_RENAME',
        'ER_TABLE_MUST_HAVE_A_VISIBLE_COLUMN',
        'ER_FORBID_SCHEMA_CHANGE',
        'ER_FOREIGN_DATA_STRING_INVALID_CANT_CREATE',
        'ER_FOREIGN_DATA_SOURCE_DOESNT_EXIST',
        'ER_CANNOT_USE_AUTOEXTEND_SIZE_CLAUSE',
        'ER_INNODB_INCOMPATIBLE_WITH_TABLESPACE',
        'ER_CANNOT_USE_ENCRYPTION_CLAUSE',
        'ER_TARGET_TABLESPACE_UNENCRYPTED',
        'ER_TABLE_NAME_CAUSES_TOO_LONG_PATH',
        'ER_UNSUPPORT_COMPRESSED_TEMPORARY_TABLE',
        'ER_TABLE_CANT_HANDLE_AUTO_INCREMENT',
        'ER_CANT_CREATE_TABLE',
        // columns
        'ER_DUP_FIELDNAME',
        'ER_BAD_FIELD_ERROR',
        'ER_CANT_REMOVE_ALL_FIELDS',
        'ER_DEPENDENT_BY_GENERATED_COLUMN',
        'ER_UNSUPPORTED_ACTION_ON_GENERATED_COLUMN',
        'ER_INVALID_USE_OF_NULL', // adding non-null column?
        'ER_BAD_NULL_ERROR', // null vs generated columns
        'ER_INVALID_DEFAULT',
        'ER_DEFAULT_VAL_GENERATED_NON_PRIOR',
        'ER_DEFAULT_VAL_GENERATED_REF_AUTO_INC',
        'ER_DEFAULT_VAL_GENERATED_NAMED_FUNCTION_IS_NOT_ALLOWED',
        'ER_DEFAULT_AS_VAL_GENERATED',
        'ER_DEPENDENT_BY_DEFAULT_GENERATED_VALUE',
        'ER_DEFAULT_VAL_GENERATED_FUNCTION_IS_NOT_ALLOWED',
        'ER_DEFAULT_VAL_GENERATED_ROW_VALUE',
        'ER_DEFAULT_VAL_GENERATED_VARIABLES',
        'ER_BINLOG_UNSAFE_SYSTEM_FUNCTION',
        'ER_NON_DEFAULT_VALUE_FOR_GENERATED_COLUMN',
        'ER_DUPLICATED_VALUE_IN_TYPE', // depends on sql_mode and engine
        'ER_GENERATED_COLUMN_ROW_VALUE',
        'ER_CANNOT_ALTER_SRID_DUE_TO_INDEX',
        'ER_PRIMARY_CANT_HAVE_NULL',
        'ER_FK_COLUMN_CANNOT_CHANGE',
        'ER_BLOB_KEY_WITHOUT_LENGTH',
        'ER_UNSUPPORTED_ACTION_ON_DEFAULT_VAL_GENERATED',
        // keys
        'ER_CANT_DROP_FIELD_OR_KEY',
        'ER_KEY_DOES_NOT_EXITS',
        'ER_DUP_KEYNAME',
        'ER_DUP_KEY',
        'ER_SPATIAL_UNIQUE_INDEX',
        'ER_TOO_LONG_KEY',
        'ER_WRONG_AUTO_KEY',
        'ER_KEY_COLUMN_DOES_NOT_EXITS',
        'ER_TOO_MANY_KEYS',
        'ER_SPATIAL_MUST_HAVE_GEOM_COL',
        'ER_FUNCTIONAL_INDEX_REF_AUTO_INCREMENT',
        'ER_DEPENDENT_BY_FUNCTIONAL_INDEX',
        'ER_CANNOT_DROP_COLUMN_FUNCTIONAL_INDEX',
        'ER_COLUMN_CHANGE_SIZE',
        'ER_UNKNOWN_KEY_CACHE',
        'ER_WARN_CANT_DROP_DEFAULT_KEYCACHE',
        'ER_INDEX_TYPE_NOT_SUPPORTED_FOR_SPATIAL_INDEX',
        'ER_NOT_IMPLEMENTED_FOR_PROJECTED_SRS',
        'ER_STD_OVERFLOW_ERROR',
        'ER_NOT_IMPLEMENTED_FOR_CARTESIAN_SRS',
        'ER_INVALID_JSON_VALUE_FOR_FUNC_INDEX',
        'ER_FUNCTION_NOT_DEFINED',
        // constraints
        'ER_CONSTRAINT_NOT_FOUND',
        'ER_MULTIPLE_CONSTRAINTS_WITH_SAME_NAME',
        'ER_TABLE_WITHOUT_PK',
        'ER_ALTER_CONSTRAINT_ENFORCEMENT_NOT_SUPPORTED',
        'ER_FK_CANNOT_DROP_PARENT',
        'ER_FK_CANNOT_OPEN_PARENT',
        'ER_FK_DUP_NAME',
        'ER_FK_CANNOT_USE_VIRTUAL_COLUMN',
        'ER_WRONG_FK_OPTION_FOR_GENERATED_COLUMN',
        'ER_CANNOT_ADD_FOREIGN',
        'ER_FK_COLUMN_CANNOT_DROP',
        'ER_DROP_INDEX_FK',
        'ER_FK_INCOMPATIBLE_COLUMNS',
        'ER_FK_NO_INDEX_PARENT',
        'ER_FK_NO_COLUMN_PARENT',
        'ER_FK_NO_INDEX_CHILD',
        'ER_FOREIGN_KEY_ON_PARTITIONED',
        'ER_FK_COLUMN_NOT_NULL',
        'ER_FK_CANNOT_CHANGE_ENGINE',
        'ER_FK_INCORRECT_OPTION',
        'ER_FK_FAIL_ADD_SYSTEM',
        'ER_MULTIPLE_PRI_KEY',
        'ER_DUP_UNIQUE',
        // checks
        'ER_CHECK_CONSTRAINT_NOT_FOUND',
        'ER_CHECK_CONSTRAINT_DUP_NAME',
        'ER_DEPENDENT_BY_CHECK_CONSTRAINT',
        'ER_NON_BOOLEAN_EXPR_FOR_CHECK_CONSTRAINT', // can be resolved from top node
        'ER_COLUMN_CHECK_CONSTRAINT_REFERENCES_OTHER_COLUMN',
        'ER_CHECK_CONSTRAINT_REFERS_AUTO_INCREMENT_COLUMN',
        'ER_CHECK_CONSTRAINT_REFERS_UNKNOWN_COLUMN',
        'ER_CHECK_CONSTRAINT_NAMED_FUNCTION_IS_NOT_ALLOWED',
        'ER_CHECK_CONSTRAINT_FUNCTION_IS_NOT_ALLOWED',
        'ER_CHECK_CONSTRAINT_VARIABLES',
        'ER_CHECK_CONSTRAINT_CLAUSE_USING_FK_REFER_ACTION_COLUMN',
        // partitions
        'ER_UNKNOWN_PARTITION',
        'ER_SAME_NAME_PARTITION',
        'ER_DROP_PARTITION_NON_EXISTENT',
        'ER_WRONG_EXPR_IN_PARTITION_FUNC_ERROR',
        'ER_FIELD_TYPE_NOT_ALLOWED_AS_PARTITION_FIELD',
        'ER_BLOB_FIELD_IN_PART_FUNC_ERROR',
        'ER_PARTITION_FUNCTION_IS_NOT_ALLOWED',
        'ER_VALUES_IS_NOT_INT_TYPE_ERROR',
        'ER_MULTIPLE_DEF_CONST_IN_LIST_PART_ERROR',
        'ER_PARTITION_REQUIRES_VALUES_ERROR',
        'ER_PARTITION_NO_TEMPORARY',
        'ER_UNIQUE_KEY_NEED_ALL_FIELDS_IN_PF',
        'ER_PARTITION_CONST_DOMAIN_ERROR',
        'ER_NULL_IN_VALUES_LESS_THAN',
        'ER_PARTITION_MERGE_ERROR',
        'ER_ONLY_ON_RANGE_LIST_PARTITION',
        'ER_WRONG_TYPE_COLUMN_VALUE_ERROR',
        'ER_DEPENDENT_BY_PARTITION_FUNC',
        'ER_FIELD_NOT_FOUND_PART_ERROR',
        'ER_RANGE_NOT_INCREASING_ERROR',
        'ER_REORG_OUTSIDE_RANGE',
        'ER_PARTITION_FIELDS_TOO_LONG',
        'ER_CHECK_NO_SUCH_TABLE',
        'ER_PARTITION_FUNC_NOT_ALLOWED_ERROR',
        'ER_PARTITIONS_MUST_BE_DEFINED_ERROR',
        'ER_PARTITION_EXCHANGE_DIFFERENT_OPTION',
        'ER_TABLES_DIFFERENT_METADATA',
        'ER_PARTITION_EXCHANGE_FOREIGN_KEY',
        'ER_ROW_DOES_NOT_MATCH_PARTITION',
        'ER_PARTITION_INSTEAD_OF_SUBPARTITION',
        'ER_PARTITION_EXCHANGE_PART_TABLE',
        'ER_PARTITION_EXCHANGE_TEMP_TABLE',
        'ER_MIX_HANDLER_ERROR',
        'ER_PARTITION_WRONG_NO_SUBPART_ERROR',
        'ER_REORG_PARTITION_NOT_EXIST',
        'ER_CONSECUTIVE_REORG_PARTITIONS',
        'ER_ADD_PARTITION_SUBPART_ERROR',
        'ER_TOO_MANY_PARTITIONS_ERROR',
        'ER_DROP_LAST_PARTITION',
        'ER_COALESCE_ONLY_ON_HASH_PARTITION',
        'ER_REORG_NO_PARAM_ERROR',
        'ER_LIMITED_PART_RANGE',
        // fulltext
        'ER_BAD_FT_COLUMN',
        'ER_FT_MATCHING_KEY_NOT_FOUND',
        // triggers
        'ER_TRG_ALREADY_EXISTS',
        'ER_TRG_DOES_NOT_EXIST',
        'ER_IF_NOT_EXISTS_UNSUPPORTED_TRG_EXISTS_ON_DIFFERENT_TABLE',
        'ER_TRG_NO_SUCH_ROW_IN_TRG',
        'ER_NO_TRIGGERS_ON_SYSTEM_SCHEMA',
        'ER_REFERENCED_TRG_DOES_NOT_EXIST',
        // events
        'ER_EVENT_DOES_NOT_EXIST',
        'ER_EVENT_ALREADY_EXISTS',
        'ER_EVENT_CANNOT_ALTER_IN_THE_PAST',
        'ER_EVENT_ENDS_BEFORE_STARTS',
        'ER_TRG_CANT_CHANGE_ROW',
        'ER_TRG_ON_VIEW_OR_TEMP_TABLE',
        'ER_TRG_IN_WRONG_SCHEMA',
        // locking
        'ER_WRONG_LOCK_OF_SYSTEM_TABLE',
        // routines
        'ER_UDF_EXISTS',
        'ER_SP_DOES_NOT_EXIST',
        'ER_SP_ALREADY_EXISTS',
        'ER_DUP_ENTRY',
        'ER_SP_NO_RETSET', // disallow SHOW/SELECT in functions
        'ER_SP_WRONG_NO_OF_ARGS',
        'ER_COMMIT_NOT_ALLOWED_IN_SF_OR_TRG',
        'ER_SP_UNDECLARED_VAR',
        'ER_GET_STACKED_DA_WITHOUT_ACTIVE_HANDLER',
        'ER_SP_COND_MISMATCH',
        'ER_SIGNAL_BAD_CONDITION_TYPE',
        'ER_SIGNAL_NOT_FOUND',
        'ER_RESIGNAL_WITHOUT_ACTIVE_HANDLER',
        'ER_SP_FETCH_NO_DATA',
        'ER_CANT_USE_OPTION_HERE',
        'ER_SP_LABEL_REDEFINE',
        'ER_SP_NORETURN',
        'ER_SP_CURSOR_MISMATCH',
        'ER_SP_DUP_VAR',
        'ER_SP_DUP_COND',
        'ER_SP_DUP_CURS',
        'ER_SP_VARCOND_AFTER_CURSHNDLR',
        'ER_SP_CURSOR_AFTER_HANDLER',
        'ER_SP_NOT_VAR_ARG',
        'ER_SP_CURSOR_NOT_OPEN',
        'ER_SP_CASE_NOT_FOUND',
        'ER_SP_DUP_HANDLER',
        'ER_SP_NO_RECURSION',
        'ER_SP_CANT_SET_AUTOCOMMIT',
        'ER_VIEW_SELECT_VARIABLE',
        'ER_TOO_LONG_BODY',
        'ER_NATIVE_FCT_NAME_COLLISION',
        'ER_BINLOG_UNSAFE_ROUTINE',
        'ER_NONEXISTING_PROC_GRANT',
        'ER_VARIABLE_NOT_SETTABLE_IN_SP',
        // resource groups
        'ER_RESOURCE_GROUP_EXISTS',
        // queries
        'ER_WRONG_FIELD_WITH_GROUP',
        'ER_WRONG_KEY_COLUMN',
        'ER_CANT_REOPEN_TABLE',
        'ER_SUBQUERY_NO_1_ROW',
        'ER_IS_QUERY_INVALID_CLAUSE',
        'ER_INVALID_GROUP_FUNC_USE',
        'ER_UPDATE_WITHOUT_KEY_IN_SAFE_MODE',
        'ER_VIEW_DELETE_MERGE_VIEW',
        'ER_OPERAND_COLUMNS',
        'ER_WRONG_GROUP_FIELD',
        'ER_FIELD_IN_ORDER_NOT_SELECT',
        'ER_AGGREGATE_ORDER_NON_AGG_QUERY',
        'ER_MIX_OF_GROUP_FUNC_AND_FIELDS',
        'ER_FIELD_SPECIFIED_TWICE',
        'ER_TABLE_CANT_HANDLE_FT',
        'ER_FIELD_IN_GROUPING_NOT_GROUP_BY',
        'ER_PARTITION_CLAUSE_ON_NONPARTITIONED',
        'ER_INVALID_JSON_CHARSET',
        'ER_WRONG_NUMBER_OF_COLUMNS_IN_SELECT', // union
        'ER_FULLTEXT_WITH_ROLLUP',
        // window
        'ER_WINDOW_ILLEGAL_ORDER_BY',
        'ER_WINDOW_RANGE_FRAME_NUMERIC_TYPE',
        'ER_WINDOW_RANGE_FRAME_TEMPORAL_TYPE',
        'ER_WINDOW_NO_CHILD_PARTITIONING',
        'ER_WINDOW_NO_REDEFINE_ORDER_BY',
        'ER_WINDOW_NO_INHERIT_FRAME',
        'ER_WINDOW_INVALID_WINDOW_FUNC_USE',
        'ER_AGGREGATE_ORDER_FOR_UNION',
        'ER_WINDOW_NESTED_WINDOW_FUNC_USE_IN_WINDOW_SPEC',
        'ER_WINDOW_RANGE_BOUND_NOT_CONSTANT',
        'ER_WINDOW_INVALID_WINDOW_FUNC_ALIAS_USE',
        'ER_WINDOW_CIRCULARITY_IN_WINDOW_GRAPH',
        'ER_WINDOW_NO_SUCH_WINDOW',
        // re
        'ER_REGEXP_ILLEGAL_ARGUMENT',
        'ER_REGEXP_INVALID_RANGE',
        'ER_REGEXP_RULE_SYNTAX',
        'ER_REGEXP_BAD_ESCAPE_SEQUENCE',
        'ER_REGEXP_MISMATCHED_PAREN',
        'ER_REGEXP_INVALID_BACK_REF',
        'ER_REGEXP_UNIMPLEMENTED',
        'ER_REGEXP_LOOK_BEHIND_LIMIT',
        'ER_REGEXP_MAX_LT_MIN',
        'ER_REGEXP_PATTERN_TOO_BIG',
        'ER_REGEXP_INVALID_CAPTURE_GROUP_NAME',
        'ER_REGEXP_INVALID_FLAG',
        'ER_REGEX_NUMBER_TOO_BIG',
        'ER_REGEXP_INDEX_OUTOFBOUNDS_ERROR',
        'ER_REGEXP_TIME_OUT',
        'ER_REGEXP_BAD_INTERVAL',
        'ER_REGEXP_STACK_OVERFLOW',
        'ER_REGEXP_BUFFER_OVERFLOW',
        // updates
        'ER_CANT_UPDATE_USED_TABLE_IN_SF_OR_TRG',
        'ER_NONUPDATEABLE_COLUMN',
        'ER_NON_INSERTABLE_TABLE',
        'ER_NON_UPDATABLE_TABLE',
        'ER_CANT_UPDATE_TABLE_IN_CREATE_TABLE_SELECT',
        'ER_NO_DEFAULT_FOR_FIELD',
        'ER_NON_UNIQ_ERROR',
        'ER_UPDATE_TABLE_USED',
        'ER_ILLEGAL_REFERENCE',
        'ER_VIEW_MULTIUPDATE',
        'ER_VIEW_WRONG_LIST',
        'ER_TOO_MANY_TABLES',
        'ER_VIEW_PREVENT_UPDATE',
        'ER_MULTI_UPDATE_KEY_CONFLICT',
        'ER_ROW_IN_WRONG_PARTITION',
        'ER_TRUNCATE_ILLEGAL_FK',
        'ER_VIEW_NO_INSERT_FIELD_LIST',
        // expressions
        'ER_WRONG_PARAMCOUNT_TO_NATIVE_FCT',
        'ER_WRONG_PARAMETERS_TO_NATIVE_FCT',
        'ER_INVALID_CAST',
        'ER_INVALID_BITWISE_OPERANDS_SIZE',
        'ER_INVALID_BITWISE_AGGREGATE_OPERANDS_SIZE',
        'ER_INVALID_JSON_TEXT_IN_PARAM',
        'ER_INVALID_TYPE_FOR_JSON',
        'ER_INVALID_ARGUMENT_FOR_LOGARITHM',
        'ER_REGEXP_MISSING_CLOSE_BRACKET',
        'ER_REGEXP_INTERNAL_ERROR',
        'ER_CHARACTER_SET_MISMATCH',
        'ER_NONPOSITIVE_RADIUS',
        'ER_UNEXPECTED_GEOMETRY_TYPE',
        'ER_JSON_DOCUMENT_NULL_KEY',
        'ER_FUNC_INEXISTENT_NAME_COLLISION', // whitespace dependent function parsing
        'ER_INVALID_JSON_ATTRIBUTE', // engine attribute
        'ER_INVALID_USER_ATTRIBUTE_JSON',
        'ER_WRONG_PARAMETERS_TO_STORED_FCT',
        'ER_USER_LOCK_WRONG_NAME',
        'ER_MALFORMED_GTID_SET_SPECIFICATION',
        'ER_INVALID_JSON_TEXT',
        'ER_JSON_VALUE_OUT_OF_RANGE_FOR_FUNC_INDEX',
        'ER_JT_MAX_NESTED_PATH',
        'ER_MULTIPLE_JSON_VALUES',
        'ER_MISSING_JSON_VALUE',
        'ER_WRONG_SIZE_NUMBER',
        'ER_SIZE_OVERFLOW_ERROR',
        'ER_STD_INVALID_ARGUMENT',
        'ER_WRONG_VALUE_FOR_TYPE',
        'ER_WRONG_VALUE_FOR_VAR_PLUS_ACTIONABLE_PART',
        //'ER_SET_STATEMENT_CANNOT_INVOKE_FUNCTION',
        // geo
        'ER_GEOMETRY_PARAM_LONGITUDE_OUT_OF_RANGE',
        'ER_GEOMETRY_PARAM_LATITUDE_OUT_OF_RANGE',
        'ER_INCORRECT_TYPE',
        'ER_INVALID_GEOJSON_MISSING_MEMBER',
        'ER_DIMENSION_UNSUPPORTED',
        'ER_INVALID_GEOJSON_WRONG_TYPE',
        'ER_INVALID_GEOJSON_CRS_NOT_TOP_LEVEL',
        'ER_JSON_DOCUMENT_TOO_DEEP',
        'ER_LATITUDE_OUT_OF_RANGE',
        'ER_NOT_IMPLEMENTED_FOR_GEOGRAPHIC_SRS',
        'ER_UNIT_NOT_FOUND',
        'ER_BOOST_GEOMETRY_INCONSISTENT_TURNS_EXCEPTION',
        'ER_GIS_UNKNOWN_ERROR',
        'ER_GEOMETRY_IN_UNKNOWN_LENGTH_UNIT',
        'ER_SPATIAL_CANT_HAVE_NULL',
        'ER_CANT_MODIFY_SRID_0',
        'ER_SRS_MULTIPLE_ATTRIBUTE_DEFINITIONS',
        'ER_SRS_INVALID_CHARACTER_IN_ATTRIBUTE',
        'ER_SRS_PARSE_ERROR',
        'ER_SRS_ID_ALREADY_EXISTS',
        'ER_CANT_MODIFY_SRS_USED_BY_COLUMN',
        'ER_SRS_PROJ_PARAMETER_MISSING',
        'ER_SRS_GEOGCS_INVALID_AXES',
        'ER_SRS_INVALID_SEMI_MAJOR_AXIS',
        'ER_SRS_INVALID_INVERSE_FLATTENING',
        'ER_SRS_INVALID_PRIME_MERIDIAN',
        'ER_SRS_INVALID_ANGULAR_UNIT',
        'ER_SRS_NOT_GEOGRAPHIC',
        'ER_TRANSFORM_SOURCE_SRS_NOT_SUPPORTED',
        'ER_TRANSFORM_TARGET_SRS_NOT_SUPPORTED',
        'ER_TRANSFORM_SOURCE_SRS_MISSING_TOWGS84',
        'ER_TRANSFORM_TARGET_SRS_MISSING_TOWGS84',
        'ER_DUPLICATE_OPTION_KEY',
        'ER_INVALID_OPTION_KEY',
        'ER_INVALID_OPTION_VALUE',
        'ER_INVALID_OPTION_START_CHARACTER',
        'ER_INVALID_OPTION_END_CHARACTER',
        'ER_INVALID_OPTION_CHARACTERS',
        // views
        'ER_VIEW_NO_EXPLAIN',
        'ER_VIEW_NONUPD_CHECK',
        'ER_VIEW_RECURSIVE',
        // data
        'ER_WRONG_VALUE_COUNT_ON_ROW',
        'ER_WARN_DATA_OUT_OF_RANGE',
        'ER_CANT_AGGREGATE_2COLLATIONS',
        'ER_CANT_AGGREGATE_3COLLATIONS',
        'ER_CANT_AGGREGATE_NCOLLATIONS',
        'ER_WRONG_ARGUMENTS',
        'ER_CANNOT_CONVERT_STRING',
        'ER_NO_DEFAULT_FOR_VIEW_FIELD',
        'ER_WRONG_SRID_FOR_COLUMN',
        'ER_TABLE_HAS_NO_FT',
        'ER_WRONG_MVI_VALUE',
        'ER_FUNCTIONAL_INDEX_DATA_IS_TOO_LONG',
        'ER_INVALID_JSON_VALUE_FOR_CAST',
        'ER_INVALID_JSON_PATH',
        'ER_JSON_BAD_ONE_OR_ALL_ARG',
        'ER_JSON_VACUOUS_PATH',
        'ER_INVALID_JSON_TYPE',
        'ER_MISSING_JSON_TABLE_VALUE',
        'ER_WRONG_JSON_TABLE_VALUE',
        'ER_JT_VALUE_OUT_OF_RANGE',
        // load
        'ER_LOAD_FROM_FIXED_SIZE_ROWS_TO_VAR',
        // plugins
        'ER_COMPONENTS_NO_SCHEME',
        'ER_COMPONENTS_NO_SCHEME_SERVICE',
        'ER_COMPONENTS_UNLOAD_DUPLICATE_IN_GROUP',
        // access
        'ER_NONEXISTING_TABLE_GRANT',
        // prepared
        'ER_EMPTY_QUERY',
        'ER_WINDOW_RANGE_FRAME_ORDER_TYPE',
        'ER_INVALID_PARAMETER_USE',
        'ER_PS_NO_RECURSION',
        'ER_IMPOSSIBLE_STRING_CONVERSION',
        // variables
        'ER_MALFORMED_GTID_SPECIFICATION',
        'ER_VARIABLE_NOT_SETTABLE_IN_SF_OR_TRIGGER',
        'ER_CHANGE_RPL_SRC_WRONG_COMPRESSION_ALGORITHM_SIZE',
        // wtf
        'ER_WRONG_OBJECT',
        'ER_ILLEGAL_PRIVILEGE_LEVEL',

        // runtime errors - cannot resolve in parse neither by static analysis -----------------------------------------
        // debug
        'ER_UNKNOWN_ERROR', // debug things
        'ER_GET_ERRNO', // WTF
        'ER_SIGNAL_EXCEPTION', // checks etc.
        'ER_DA_UNKNOWN_ERROR_NUMBER',
        'ER_INTERNAL_ERROR',
        'ER_SP_STORE_FAILED',
        'ER_SP_CANT_ALTER',
        'S22008', // date underflow or what not
        'ER_NO_ACCESS_TO_NATIVE_FCT',
        'ER_CLIENT_QUERY_FAILURE_INVALID_NON_ROW_FORMAT',
        'ER_INVALID_THREAD_PRIORITY',
        '8888',
        '12',
        '$error_code',
        '$error_success',
        '$expected_error',
        // misc
        'ER_SUBQUERY_TRANSFORM_REJECTED',
        'ER_PLUGIN_DELETE_BUILTIN',
        'ER_DA_SSL_LIBRARY_ERROR',
        'ER_PLUGIN_IS_PERMANENT',
        'ER_CANNOT_LOAD_FROM_TABLE_V2',
        'ER_COMPONENT_TABLE_INCORRECT',
        'ER_FEATURE_DISABLED',
        'ER_FOREIGN_DUPLICATE_KEY_WITH_CHILD_INFO',
        'ER_FK_DEPTH_EXCEEDED',
        'ER_SET_PASSWORD_AUTH_PLUGIN_ERROR',
        'ER_MAX_PREPARED_STMT_COUNT_REACHED',
        'ER_QUERY_TIMEOUT',
        'ER_CANNOT_FIND_KEY_IN_KEYRING',
        'ER_PREVENTS_VARIABLE_WITHOUT_RBR',
        'ER_VIEW_INVALID',
        'ER_VIEW_CHECK_FAILED',
        'ER_UNABLE_TO_COLLECT_LOG_STATUS',
        'ER_ADMIN_WRONG_MRG_TABLE',
        'ER_SECONDARY_ENGINE_DDL',
        'ER_CRASHED_ON_USAGE',
        'ER_AUTOINC_READ_FAILED',
        'CR_SERVER_LOST',
        'ER_ALTER_FILEGROUP_FAILED',
        'ER_SP_LOAD_FAILED',
        'ER_SP_DROP_FAILED',
        'ER_CANT_SET_VARIABLE_WHEN_OWNING_GTID',
        'ER_INVALID_RPL_WILD_TABLE_FILTER_PATTERN',
        'ER_RUN_HOOK_ERROR',
        'ER_NOT_KEYFILE',
        'ER_INNODB_INDEX_CORRUPT',
        'ER_INNODB_FT_LIMIT',
        'ER_PROCACCESS_DENIED_ERROR',
        'ER_PARTITION_FUNCTION_FAILURE',
        'ER_GIS_INVALID_DATA',
        'ER_WRONG_MRG_TABLE',
        'ER_TOO_MANY_ROWS',
        'ER_NEED_REPREPARE',
        'ER_SDI_OPERATION_FAILED',
        'ER_UNABLE_TO_DROP_COLUMN_STATISTICS',
        'ER_UNABLE_TO_UPDATE_COLUMN_STATISTICS',
        'ER_BINLOG_MULTIPLE_ENGINES_AND_SELF_LOGGING_ENGINE',
        'ER_GTID_MODE_ON_REQUIRES_ENFORCE_GTID_CONSISTENCY_ON',
        'ER_CANT_SET_GTID_PURGED_DUE_SETS_CONSTRAINTS',
        'ER_CONFLICT_FN_PARSE_ERROR',
        'ER_WRONG_PERFSCHEMA_USAGE',
        'ER_TABLESPACE_DISCARDED',
        'ER_TABLE_SCHEMA_MISMATCH',
        'ER_SP_CURSOR_ALREADY_OPEN',
        'ER_VIEW_SELECT_TMPTABLE',
        'ER_WARN_I_S_SKIPPED_TABLE',
        'ER_TABLESPACE_IS_NOT_EMPTY',
        'ER_CANT_FIND_SYSTEM_REC',
        'ER_BASE64_DECODE_ERROR',
        'ER_TABLE_DEF_CHANGED', // partitions, wtf
        'ER_BINLOG_STMT_MODE_AND_ROW_ENGINE',
        'ER_BINLOG_STMT_MODE_AND_NO_REPL_TABLES',
        'ER_VARIABLE_NOT_SETTABLE_IN_TRANSACTION', // sys vars
        'ER_HASHCHK',
        'ER_GET_ERRMSG',
        '9999', // que?
        // config
        'ER_DA_SSL_FIPS_MODE_ERROR',
        'ER_PARTIAL_REVOKES_EXIST',
        'ER_NO_SECURE_TRANSPORTS_CONFIGURED',
        'ER_BINLOG_EXPIRE_LOG_DAYS_AND_SECS_USED_TOGETHER',
        'ER_NO_SESSION_TEMP',
        'ER_INNODB_NO_FT_TEMP_TABLE',
        'ER_RUNNING_APPLIER_PREVENTS_SWITCH_GLOBAL_BINLOG_FORMAT',
        'ER_TEMP_TABLE_PREVENTS_SWITCH_GLOBAL_BINLOG_FORMAT',
        // udf
        'ER_UDF_DROP_DYNAMICALLY_REGISTERED',
        'ER_COMPONENTS_UNLOAD_CANT_DEINITIALIZE',
        'ER_COMPONENTS_LOAD_CANT_INITIALIZE',
        // data
        'ER_ROW_IS_REFERENCED_2',
        'ER_CUT_VALUE_GROUP_CONCAT',
        'ER_WARN_NULL_TO_NOTNULL',
        'ER_ROW_DOES_NOT_MATCH_GIVEN_PARTITION_SET',
        'ER_NO_PARTITION_FOR_GIVEN_VALUE',
        'ER_DATETIME_FUNCTION_OVERFLOW',
        'ER_DIVISION_BY_ZERO',
        // import data
        'ER_CLIENT_LOCAL_FILES_DISABLED',
        'ER_BLOBS_AND_NO_TERMINATED',
        'ER_TEXTFILE_NOT_READABLE',
        // gis
        'ER_CANT_CREATE_GEOMETRY_OBJECT',
        'ER_GIS_DIFFERENT_SRIDS',
        'ER_SRS_NOT_FOUND',
        'ER_INVALID_GEOJSON_UNSPECIFIED',
        'ER_GIS_MAX_POINTS_IN_GEOMETRY_OVERFLOWED',
        'ER_GIS_DATA_WRONG_ENDIANESS',
        'ER_POLYGON_TOO_LARGE',
        'ER_LONGITUDE_OUT_OF_RANGE',
        // import
        'ER_IMP_SCHEMA_DOES_NOT_EXIST',
        'ER_IMP_TABLE_ALREADY_EXISTS',
        'ER_IMP_NO_FILES_MATCHED',
        'ER_WRONG_FILE_NAME',
        'ER_INVALID_JSON_DATA',
        'ER_IMP_INCOMPATIBLE_SDI_VERSION',
        // prepared statements
        'ER_UNKNOWN_STMT_HANDLER',
        // configuration
        'ER_SLAVE_CONFIGURATION',
        'ER_NO_BINARY_LOGGING',
        'ER_DISABLED_STORAGE_ENGINE',
        'ER_INNODB_REDO_LOG_ARCHIVE_DIRS_INVALID',
        'ER_INNODB_REDO_LOG_ARCHIVE_LABEL_NOT_FOUND',
        'ER_INNODB_REDO_LOG_ARCHIVE_START_SUBDIR_PATH',
        'ER_INNODB_REDO_LOG_ARCHIVE_NO_SUCH_DIR',
        'ER_INNODB_REDO_LOG_ARCHIVE_DIR_CLASH',
        'ER_INNODB_REDO_LOG_ARCHIVE_ACTIVE',
        'ER_INNODB_REDO_LOG_ARCHIVE_INACTIVE',
        'ER_INNODB_REDO_LOG_ARCHIVE_SESSION',
        'ER_INNODB_REDO_LOG_ARCHIVE_FAILED',
        'ER_INNODB_REDO_LOG_ARCHIVE_FILE_CREATE',
        'ER_INNODB_REDO_ARCHIVING_ENABLED',
        'ER_INNODB_REDO_DISABLED',
        'ER_INNODB_REDO_LOG_ARCHIVE_START_TIMEOUT',
        'ER_INNODB_REDO_LOG_ARCHIVE_DIR_PERMISSIONS',
        // checks & limits
        'ER_OPTION_PREVENTS_STATEMENT',
        'ER_DATA_TOO_LONG',
        'ER_DATA_OUT_OF_RANGE',
        'ER_CHECK_CONSTRAINT_VIOLATED',
        'WARN_DATA_TRUNCATED',
        'ER_UNDO_RECORD_TOO_BIG',
        'ER_INNODB_AUTOEXTEND_SIZE_OUT_OF_RANGE',
        'ER_INNODB_INVALID_AUTOEXTEND_SIZE_VALUE',
        'ER_EXCEEDED_MV_KEYS_NUM',
        'ER_EXCEEDED_MV_KEYS_SPACE',
        // transactions & locks
        'ER_QUERY_INTERRUPTED',
        'ER_LOCK_DEADLOCK',
        'ER_LOCK_WAIT_TIMEOUT',
        'ER_TABLE_NOT_LOCKED_FOR_WRITE',
        'ER_TABLE_NOT_LOCKED',
        'ER_CANT_CHANGE_TX_CHARACTERISTICS',
        'ER_CANT_EXECUTE_IN_READ_ONLY_TRANSACTION',
        'ER_STATEMENT_NOT_ALLOWED_AFTER_START_TRANSACTION',
        'ER_CREATE_DB_WITH_READ_LOCK',
        'ER_DROP_DB_WITH_READ_LOCK',
        'ER_DB_DROP_EXISTS',
        'ER_CANT_UPDATE_WITH_READLOCK',
        'ER_LOCK_OR_ACTIVE_TRANSACTION',
        'ER_UNRESOLVED_TABLE_LOCK',
        'ER_DUPLICATE_TABLE_LOCK',
        'ER_LOCK_NOWAIT',
        'ER_CANT_INITIALIZE_UDF',
        'ER_LOCKING_SERVICE_WRONG_NAME',
        'ER_LOCKING_SERVICE_TIMEOUT',
        'ER_OPEN_AS_READONLY',
        'ER_CANT_DO_IMPLICIT_COMMIT_IN_TRX_WHEN_GTID_NEXT_IS_SET',
        'ER_CANT_SET_GTID_NEXT_WHEN_OWNING_GTID',
        'ER_GTID_UNSAFE_CREATE_SELECT',
        'ER_GNO_EXHAUSTED',
        'ER_CANT_SET_GTID_NEXT_TO_ANONYMOUS_WHEN_GTID_MODE_IS_ON',
        'ER_DA_RPL_GTID_TABLE_CANNOT_OPEN',
        'ER_CANT_SET_GTID_MODE',
        'ER_ERROR_ON_MODIFYING_GTID_EXECUTED_TABLE',
        'ER_TRANS_CACHE_FULL',
        'ER_CANT_ENFORCE_GTID_CONSISTENCY_WITH_ONGOING_GTID_VIOLATING_TX',
        'ER_BINLOG_ROW_MODE_AND_STMT_ENGINE',
        'ER_GTID_MODE_OFF',
        'ER_CLONE_IN_PROGRESS',
        'ER_PLUGIN_CANNOT_BE_UNINSTALLED',
        'ER_SCHEMA_READ_ONLY',
        'ER_INNODB_READ_ONLY',
        'ER_ENGINE_CANT_DROP_TABLE',
        'ER_CANT_LOCK',
        'ER_TOO_MANY_CONCURRENT_TRXS',
        'ER_CONCURRENT_PROCEDURE_USAGE',
        'ER_INNODB_MAX_ROW_VERSION',
        // XA
        'ER_XA_RBDEADLOCK',
        'ER_XAER_DUPID',
        'ER_XA_RBROLLBACK',
        'ER_XAER_NOTA',
        'ER_XAER_RMERR',
        'ER_XAER_RMFAIL',
        'ER_XAER_OUTSIDE',
        'ER_XAER_INVAL',
        'ER_XA_TEMP_TABLE',
        '$xa_error',
        // system tables
        'ER_CANT_LOCK_LOG_TABLE',
        'ER_CANT_RENAME_LOG_TABLE',
        'ER_BAD_LOG_STATEMENT',
        'ER_UNSUPORTED_LOG_ENGINE',
        // libs & plugins
        'ER_COMPONENTS_CANT_LOAD',
        'ER_CANT_OPEN_LIBRARY',
        'ER_COMPONENTS_CANT_SATISFY_DEPENDENCY',
        'ER_COMPONENTS_UNLOAD_NOT_LOADED',
        'ER_COMPONENTS_UNLOAD_CANT_UNREGISTER_SERVICE',
        'ER_COMPONENT_MANIPULATE_ROW_FAILED',
        'ER_PLUGIN_IS_NOT_LOADED',
        'ER_VTOKEN_PLUGIN_TOKEN_MISMATCH',
        'ER_VTOKEN_PLUGIN_TOKEN_NOT_FOUND',
        // replication & binlog
        'ER_NO_FORMAT_DESCRIPTION_EVENT_BEFORE_BINLOG_STATEMENT',
        'ER_ERROR_WHEN_EXECUTING_COMMAND',
        'ER_GTID_NEXT_TYPE_UNDEFINED_GTID',
        'ER_BINLOG_LOGGING_IMPOSSIBLE',
        'ER_NO_UNIQUE_LOGFILE',
        'ER_UNKNOWN_TARGET_BINLOG',
        'ER_CANT_OPEN_FILE',
        'ER_BINLOG_PURGE_FATAL_ERR',
        'ER_RESET_MASTER_TO_VALUE_OUT_OF_RANGE',
        'ER_RPL_ENCRYPTION_MASTER_KEY_RECOVERY_FAILED',
        'ER_BINLOG_MASTER_KEY_RECOVERY_OUT_OF_COMBINATION',
        'ER_RPL_ENCRYPTION_FAILED_TO_STORE_KEY',
        'ER_BINLOG_MASTER_KEY_ROTATION_FAIL_TO_OPERATE_KEY',
        'ER_RPL_ENCRYPTION_FAILED_TO_REMOVE_KEY',
        'ER_RPL_ENCRYPTION_FAILED_TO_FETCH_KEY',
        'ER_INSIDE_TRANSACTION_PREVENTS_SWITCH_BINLOG_FORMAT',
        'ER_INSIDE_TRANSACTION_PREVENTS_SWITCH_BINLOG_DIRECT',
        'ER_INSIDE_TRANSACTION_PREVENTS_SWITCH_SQL_LOG_BIN',
        'ER_STORED_FUNCTION_PREVENTS_SWITCH_BINLOG_FORMAT',
        'ER_STORED_FUNCTION_PREVENTS_SWITCH_BINLOG_DIRECT',
        'ER_STORED_FUNCTION_PREVENTS_SWITCH_SQL_LOG_BIN',
        'ER_NDB_REPLICATION_SCHEMA_ERROR',
        'ER_GET_TEMPORARY_ERRMSG',
        'ER_SLAVE_CHANNEL_SQL_THREAD_MUST_STOP',
        'ER_SLAVE_CHANNEL_IO_THREAD_MUST_STOP',
        'ER_RPL_ENCRYPTION_CANNOT_ROTATE_BINLOG_MASTER_KEY',
        'ER_SLAVE_RLI_INIT_REPOSITORY',
        'ER_CLIENT_PRIVILEGE_CHECKS_USER_CANNOT_BE_ANONYMOUS',
        'ER_CLIENT_PRIVILEGE_CHECKS_USER_DOES_NOT_EXIST',
        'ER_DONT_SUPPORT_REPLICA_PRESERVE_COMMIT_ORDER',
        'ER_SLAVE_MULTIPLE_CHANNELS_CMD',
        'ER_SLAVE_MAX_CHANNELS_EXCEEDED',
        'ER_SLAVE_IGNORE_SERVER_IDS',
        'ER_RPL_ASYNC_RECONNECT_AUTO_POSITION_OFF',
        'ER_RPL_ASYNC_RECONNECT_GTID_MODE_OFF',
        'ER_CHANGE_REPLICATION_SOURCE_NO_OPTIONS_FOR_GTID_ONLY',
        'ER_CHANGE_REP_SOURCE_CANT_DISABLE_REQ_ROW_FORMAT_WITH_GTID_ONLY',
        'ER_CHANGE_REP_SOURCE_CANT_DISABLE_AUTO_POSITION_WITH_GTID_ONLY',
        'ER_CHANGE_REP_SOURCE_CANT_DISABLE_GTID_ONLY_WITHOUT_POSITIONS',
        'ER_CHANGE_REP_SOURCE_CANT_DISABLE_AUTO_POS_WITHOUT_POSITIONS',
        'ER_CHANGE_REP_SOURCE_GR_CHANNEL_WITH_GTID_MODE_NOT_ON',
        'ER_CLIENT_GTID_UNSAFE_CREATE_DROP_TEMP_TABLE_IN_TRX_IN_SBR',
        'ER_ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS_REQUIRES_GTID_MODE_ON',
        'ER_CANT_USE_ANONYMOUS_TO_GTID_WITH_GTID_MODE_NOT_ON',
        'ER_CANT_COMBINE_ANONYMOUS_TO_GTID_AND_AUTOPOSITION',
        'ER_CANT_SET_SQL_AFTER_OR_BEFORE_GTIDS_WITH_ANONYMOUS_TO_GTID',
        'ER_DISABLE_AUTO_POSITION_REQUIRES_ASYNC_RECONNECT_OFF',
        'ER_DISABLE_GTID_MODE_REQUIRES_ASYNC_RECONNECT_OFF',
        'ER_BINLOG_ROW_ENGINE_AND_STMT_ENGINE',
        'ER_BINLOG_ROW_INJECTION_AND_STMT_ENGINE',
        'ER_BINLOG_UNSAFE_AND_STMT_ENGINE',
        'ER_BINLOG_ROW_INJECTION_AND_STMT_MODE',
        'ER_AUTO_POSITION_REQUIRES_GTID_MODE_NOT_OFF',
        'ER_CANT_USE_AUTO_POSITION_WITH_GTID_MODE_OFF',
        'ER_SLAVE_SQL_THREAD_MUST_STOP',
        'ER_SLAVE_CHANNEL_SQL_SKIP_COUNTER',
        'ER_BINLOG_FATAL_ERROR',
        'ER_TEMP_TABLE_PREVENTS_SWITCH_SESSION_BINLOG_FORMAT',
        '3',
        // group replication
        'ER_GROUP_REPLICATION_CONFIGURATION',
        'ER_GROUP_REPLICATION_RUNNING',
        'ER_SLAVE_CHANNEL_MUST_STOP',
        'ER_OPERATION_NOT_ALLOWED_ON_GR_SECONDARY',
        'ER_GROUP_REPLICATION_APPLIER_INIT_ERROR',
        'ER_GROUP_REPLICATION_STOP_APPLIER_THREAD_TIMEOUT',
        'ER_SLAVE_CHANNEL_OPERATION_NOT_ALLOWED',
        'ER_GRP_OPERATION_NOT_ALLOWED_GR_MUST_STOP',
        'ER_SLAVE_CHANNEL_DOES_NOT_EXIST',
        'ER_DA_GRP_RPL_STARTED_AUTO_REJOIN',
        'ER_TRANSACTION_ROLLBACK_DURING_COMMIT',
        'ER_GRP_RPL_UDF_ERROR',
        'ER_GROUP_REPLICATION_COMMAND_FAILURE',
        'ER_GROUP_REPLICATION_COMMUNICATION_LAYER_SESSION_ERROR',
        'ER_GROUP_REPLICATION_COMMUNICATION_LAYER_JOIN_ERROR',
        'ER_GRP_TRX_CONSISTENCY_BEGIN_NOT_ALLOWED',
        'ER_GRP_TRX_CONSISTENCY_NOT_ALLOWED',
        'ER_BEFORE_DML_VALIDATION_ERROR',
        'ER_ERROR_DURING_COMMIT',
        'ER_GROUP_REPLICATION_MAX_GROUP_SIZE',
        'ER_UDF_ERROR',
        'ER_DA_GRP_RPL_RECOVERY_ENDPOINT_FORMAT',
        'ER_DA_GRP_RPL_RECOVERY_ENDPOINT_INVALID',
        'ER_SLAVE_CHANNEL_DOES_NOT_EXIST',
        'ER_CANT_RESET_MASTER',
        'ER_GTID_MODE_CAN_ONLY_CHANGE_ONE_STEP_AT_A_TIME',
        'ER_UNABLE_TO_SET_OPTION',
        'ER_CHANGE_RPL_INFO_REPOSITORY_FAILURE',
        'ER_GROUP_REPLICATION_USER_MANDATORY_MSG',
        'ER_GROUP_REPLICATION_USER_EMPTY_MSG',
        'ER_GROUP_REPLICATION_PASSWORD_LENGTH',
        'ER_CANT_EXECUTE_COMMAND_WITH_ASSIGNED_GTID_NEXT',
        'ER_GRP_RPL_RECOVERY_CHANNEL_STILL_RUNNING',
        'ER_OPERATION_NOT_ALLOWED_WHILE_PRIMARY_CHANGE_IS_RUNNING',
        'ER_ANONYMOUS_TO_GTID_UUID_SAME_AS_VIEW_CHANGE_UUID',
        // engine & recovery
        'ER_INNODB_FORCED_RECOVERY',
        'ER_INDEX_CORRUPT',
        'ER_INNODB_FT_WRONG_DOCID_COLUMN',
        'ER_INNODB_FT_WRONG_DOCID_INDEX',
        'ER_INDEX_COLUMN_TOO_LONG',
        'ER_IMP_INCOMPATIBLE_CFG_VERSION',
        'ER_INNODB_COMPRESSION_FAILURE',
        // runtime & memory
        'ER_STACK_OVERRUN_NEED_MORE',
        'ER_STD_BAD_ALLOC_ERROR',
        'ER_TOO_BIG_SELECT',
        'ER_WARN_ALLOWED_PACKET_OVERFLOWED',
        'ER_OUT_OF_RESOURCES',
        'ER_NO_SUCH_THREAD',
        'ER_OUT_OF_SORTMEMORY',
        'ER_CTE_MAX_RECURSION_DEPTH',
        'ER_NET_PACKET_TOO_LARGE',
        'ER_SP_RECURSION_LIMIT',
        'ER_OUTOFMEMORY',
        // filesystem & network
        '1', // no directory
        '5', // io error
        '13', // too long file names or something
        '29', // file not found
        'ER_FILE_NOT_FOUND',
        'ER_ERROR_ON_WRITE',
        'ER_RECORD_FILE_FULL',
        'ER_DB_DROP_RMDIR',
        'ER_SCHEMA_DIR_MISSING',
        'ER_TABLESPACE_MISSING',
        'ER_SCHEMA_DIR_EXISTS',
        'ER_SCHEMA_DIR_UNKNOWN',
        'ER_ENGINE_CANT_DROP_MISSING_TABLE',
        'ER_ACCESS_DENIED_ERROR',
        'ER_TABLE_CORRUPT',
        'ER_COL_COUNT_DOESNT_MATCH_CORRUPTED_V2',
        'ER_NET_ERROR_ON_WRITE',
        'ER_NEW_ABORTING_CONNECTION',
        'ER_UDF_NO_PATHS',
        'ER_CREATE_FILEGROUP_FAILED',
        'ER_MISSING_TABLESPACE_FILE',
        'ER_CONNECT_TO_FOREIGN_DATA_SOURCE',
        'ER_TABLESPACE_DUP_FILENAME',
        'ER_TEMP_FILE_WRITE_FAILURE',
        'ER_IO_READ_ERROR',
        'ER_DROP_FILEGROUP_FAILED',
        // users & access
        'ER_TABLEACCESS_DENIED_ERROR',
        'ER_MUST_CHANGE_PASSWORD',
        'ER_CANNOT_USER',
        'ER_NO_SUCH_USER',
        'ER_DBACCESS_DENIED_ERROR',
        'ER_NO_REFERENCED_ROW',
        'ER_NO_REFERENCED_ROW_2',
        'ER_NONEXISTING_GRANT',
        'ER_REVOKE_GRANTS',
        'ER_COLUMNACCESS_DENIED_ERROR',
        'ER_PASSWORD_FORMAT',
        'ER_PASSWORD_ANONYMOUS_USER',
        'ER_PASSWORD_EXPIRE_ANONYMOUS_USER',
        'ER_CANT_CREATE_USER_WITH_GRANT', // ?
        'ER_DBACCESS_DENIED_ERROR',
        'ER_NO_SYSTEM_VIEW_ACCESS',
        'ER_PERSIST_ONLY_ACCESS_DENIED_ERROR',
        'ER_ACCESS_DENIED_NO_PASSWORD_ERROR',
        'ER_NOT_VALID_PASSWORD',
        'ER_PASSWORD_NO_MATCH',
        'ER_READ_ONLY_MODE',
        'ER_UNKNOWN_AUTHID',
        'ER_RENAME_ROLE',
        'ER_ROLE_NOT_GRANTED',
        'ER_USER_DOES_NOT_EXIST',
        'ER_ROLE_GRANTED_TO_ITSELF',
        'ER_AUDIT_API_ABORT',
        'ER_ACL_OPERATION_FAILED',
        'ER_USER_LIMIT_REACHED',
        'ER_CREDENTIALS_CONTRADICT_TO_HISTORY',
        'ER_BINLOG_CREATE_ROUTINE_NEED_SUPER',
        'ER_MANDATORY_ROLE',
        'ER_FAILED_ROLE_GRANT',
        'ER_SECOND_PASSWORD_CANNOT_BE_EMPTY',
        'ER_INCORRECT_CURRENT_PASSWORD',
        'ER_PASSWORD_CANNOT_BE_RETAINED_ON_PLUGIN_CHANGE', // possible
        'ER_CURRENT_PASSWORD_CANNOT_BE_RETAINED',
        'ER_CURRENT_PASSWORD_NOT_REQUIRED',
        'ER_DA_AUTH_ID_WITH_SYSTEM_USER_PRIV_IN_MANDATORY_ROLES',
        'ER_CANNOT_GRANT_SYSTEM_PRIV_TO_MANDATORY_ROLE',
        'ER_CMD_NEED_SUPER',
        'ER_MISSING_CURRENT_PASSWORD',
    ];

}
