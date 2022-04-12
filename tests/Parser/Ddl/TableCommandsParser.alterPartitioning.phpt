<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ADD PARTITION
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1)");

// [VALUES {LESS THAN {(expr | value_list) | MAXVALUE} | IN (value_list)}]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 VALUES LESS THAN (1))");
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 VALUES LESS THAN (1, 2))");
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 VALUES LESS THAN MAXVALUE)");
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 VALUES IN (1))");
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 VALUES IN (1, 2))");

// [[STORAGE] ENGINE [=] engine_name]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 ENGINE = 'InnoDB')");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 STORAGE ENGINE = 'InnoDB')",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 ENGINE = 'InnoDB')" // [STORAGE]
);
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 ENGINE 'InnoDB')",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 ENGINE = 'InnoDB')" // [=]
);

// [COMMENT [=] 'comment_text' ]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 COMMENT = 'com1')");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 COMMENT 'com1')",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 COMMENT = 'com1')" // [=]
);

// [DATA DIRECTORY [=] 'data_dir']
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 DATA DIRECTORY = 'dir1')");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 DATA DIRECTORY 'dir1')",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 DATA DIRECTORY = 'dir1')" // [=]
);

// [INDEX DIRECTORY [=] 'index_dir']
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 INDEX DIRECTORY = 'dir1')");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 INDEX DIRECTORY 'dir1')",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 INDEX DIRECTORY = 'dir1')" // [=]
);

// [MAX_ROWS [=] max_number_of_rows]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 MAX_ROWS = 123)");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 MAX_ROWS 123)",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 MAX_ROWS = 123)" // [=]
);

// [MIN_ROWS [=] min_number_of_rows]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 MIN_ROWS = 123)");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 MIN_ROWS 123)",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 MIN_ROWS = 123)" // [=]
);

// [TABLESPACE [=] tablespace_name]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 TABLESPACE = 'tbs1')");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 TABLESPACE 'tbs1')",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 TABLESPACE = 'tbs1')" // [=]
);


// [(subpartition_definition [, subpartition_definition] ...)]
// SUBPARTITION logical_name
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1, SUBPARTITION sub2))");

// [[STORAGE] ENGINE [=] engine_name]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 ENGINE = 'InnoDB'))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 STORAGE ENGINE = 'InnoDB'))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 ENGINE = 'InnoDB'))" // [STORAGE]
);
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 ENGINE 'InnoDB'))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 ENGINE = 'InnoDB'))" // [=]
);

// [COMMENT [=] 'comment_text' ]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 COMMENT = 'com1'))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 COMMENT 'com1'))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 COMMENT = 'com1'))" // [=]
);

// [DATA DIRECTORY [=] 'data_dir']
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 DATA DIRECTORY = 'dir1'))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 DATA DIRECTORY 'dir1'))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 DATA DIRECTORY = 'dir1'))" // [=]
);

// [INDEX DIRECTORY [=] 'index_dir']
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 INDEX DIRECTORY = 'dir1'))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 INDEX DIRECTORY 'dir1'))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 INDEX DIRECTORY = 'dir1'))" // [=]
);

// [MAX_ROWS [=] max_number_of_rows]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 MAX_ROWS = 123))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 MAX_ROWS 123))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 MAX_ROWS = 123))" // [=]
);

// [MIN_ROWS [=] min_number_of_rows]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 MIN_ROWS = 123))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 MIN_ROWS 123))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 MIN_ROWS = 123))" // [=]
);

// [TABLESPACE [=] tablespace_name]
Assert::parse("ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 TABLESPACE = 'tbs1'))");
Assert::parse(
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 TABLESPACE 'tbs1'))",
    "ALTER TABLE tbl1 ADD PARTITION (PARTITION part1 (SUBPARTITION sub1 TABLESPACE = 'tbs1'))" // [=]
);


// DROP PARTITION
Assert::parse("ALTER TABLE tbl1 DROP PARTITION part1");

// DISCARD PARTITION {partition_names | ALL} TABLESPACE
Assert::parse("ALTER TABLE tbl1 DISCARD PARTITION part1 TABLESPACE");
Assert::parse("ALTER TABLE tbl1 DISCARD PARTITION part1, part2 TABLESPACE");
Assert::parse("ALTER TABLE tbl1 DISCARD PARTITION ALL TABLESPACE");

// IMPORT PARTITION {partition_names | ALL} TABLESPACE
Assert::parse("ALTER TABLE tbl1 IMPORT PARTITION part1 TABLESPACE");
Assert::parse("ALTER TABLE tbl1 IMPORT PARTITION part1, part2 TABLESPACE");
Assert::parse("ALTER TABLE tbl1 IMPORT PARTITION ALL TABLESPACE");

// TRUNCATE PARTITION {partition_names | ALL}
Assert::parse("ALTER TABLE tbl1 TRUNCATE PARTITION part1");
Assert::parse("ALTER TABLE tbl1 TRUNCATE PARTITION part1, part2");
Assert::parse("ALTER TABLE tbl1 TRUNCATE PARTITION ALL");

// COALESCE PARTITION number
Assert::parse("ALTER TABLE tbl1 COALESCE PARTITION 123");

// REORGANIZE PARTITION partition_names INTO (partition_definitions)
Assert::parse("ALTER TABLE tbl1 REORGANIZE PARTITION part1 INTO (PARTITION part2)");
Assert::parse("ALTER TABLE tbl1 REORGANIZE PARTITION part1, part2 INTO (PARTITION part3)");

// EXCHANGE PARTITION partition_name WITH TABLE tbl_name [{WITH|WITHOUT} VALIDATION]
Assert::parse("ALTER TABLE tbl1 EXCHANGE PARTITION part1 WITH TABLE tbl2");
Assert::parse("ALTER TABLE tbl1 EXCHANGE PARTITION part1 WITH TABLE tbl2 WITH VALIDATION");
Assert::parse("ALTER TABLE tbl1 EXCHANGE PARTITION part1 WITH TABLE tbl2 WITHOUT VALIDATION");

// ANALYZE PARTITION {partition_names | ALL}
Assert::parse("ALTER TABLE tbl1 ANALYZE PARTITION part1");
Assert::parse("ALTER TABLE tbl1 ANALYZE PARTITION part1, part2");
Assert::parse("ALTER TABLE tbl1 ANALYZE PARTITION ALL");

// CHECK PARTITION {partition_names | ALL}
Assert::parse("ALTER TABLE tbl1 CHECK PARTITION part1");
Assert::parse("ALTER TABLE tbl1 CHECK PARTITION part1, part2");
Assert::parse("ALTER TABLE tbl1 CHECK PARTITION ALL");

// OPTIMIZE PARTITION {partition_names | ALL}
Assert::parse("ALTER TABLE tbl1 OPTIMIZE PARTITION part1");
Assert::parse("ALTER TABLE tbl1 OPTIMIZE PARTITION part1, part2");
Assert::parse("ALTER TABLE tbl1 OPTIMIZE PARTITION ALL");

// REBUILD PARTITION {partition_names | ALL}
Assert::parse("ALTER TABLE tbl1 REBUILD PARTITION part1");
Assert::parse("ALTER TABLE tbl1 REBUILD PARTITION part1, part2");
Assert::parse("ALTER TABLE tbl1 REBUILD PARTITION ALL");

// REPAIR PARTITION {partition_names | ALL}
Assert::parse("ALTER TABLE tbl1 REPAIR PARTITION part1");
Assert::parse("ALTER TABLE tbl1 REPAIR PARTITION part1, part2");
Assert::parse("ALTER TABLE tbl1 REPAIR PARTITION ALL");

// REMOVE PARTITIONING
Assert::parse("ALTER TABLE tbl1 REMOVE PARTITIONING");

// UPGRADE PARTITIONING
Assert::parse("ALTER TABLE tbl1 UPGRADE PARTITIONING");
