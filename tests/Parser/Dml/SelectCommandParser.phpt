<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// SELECT
//     [ALL | DISTINCT | DISTINCTROW ]
//     [HIGH_PRIORITY]
//     [STRAIGHT_JOIN]
//     [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
//     [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
//     select_expr [, select_expr ...]
Assert::parse("SELECT foo");
Assert::parse("SELECT foo, bar");
Assert::parse("SELECT foo.x, bar.y");
Assert::parse("SELECT foo.x.z, bar.y.z");
Assert::parse("SELECT @foo, @bar");
Assert::parse("SELECT @@global.foo, @@global.bar");
Assert::parse("SELECT foo(), bar()");
Assert::parse("SELECT foo.x(), bar.y()");
Assert::parse("SELECT *");
Assert::parse("SELECT foo.*, bar.*");
Assert::parse("SELECT db.foo.*, db.bar.*");

Assert::parse("SELECT ALL @foo, @bar");
Assert::parse("SELECT DISTINCT @foo, @bar");
Assert::parse("SELECT DISTINCTROW @foo, @bar");
Assert::parse("SELECT HIGH_PRIORITY @foo, @bar");
Assert::parse("SELECT STRAIGHT_JOIN @foo, @bar");
Assert::parse("SELECT SQL_SMALL_RESULT @foo, @bar");
Assert::parse("SELECT SQL_BIG_RESULT @foo, @bar");
Assert::parse("SELECT SQL_BUFFER_RESULT @foo, @bar");
Assert::parse("SELECT SQL_CACHE @foo, @bar");
Assert::parse("SELECT SQL_NO_CACHE @foo, @bar");
Assert::parse("SELECT SQL_CALC_FOUND_ROWS @foo, @bar");

//     [FROM table_references
//       [PARTITION partition_list]
Assert::parse("SELECT foo, bar FROM uno, duo");
Assert::parse("SELECT foo, bar FROM uno JOIN duo ON uno.x = duo.y");
Assert::parse("SELECT foo, bar FROM uno, duo PARTITION (x, y)");

//     [WHERE where_condition]
Assert::parse("SELECT foo, bar FROM uno, duo WHERE foo = 1 AND bar = 2");

//     [GROUP BY {col_name | expr | position}
//       [ASC | DESC], ... [WITH ROLLUP]]
Assert::parse("SELECT foo, bar FROM uno, duo GROUP BY foo, bar ASC, baz DESC");
Assert::parse("SELECT foo, bar FROM uno, duo GROUP BY foo IS NULL, bar - 10 ASC, baz < 1 DESC"); // todo: problem with "bar -10" (parsed as negative number)
Assert::parse("SELECT foo, bar FROM uno, duo GROUP BY 1, 2 ASC, 3 DESC");
Assert::parse("SELECT foo, bar FROM uno, duo GROUP BY 1, 2 ASC, 3 DESC WITH ROLLUP");

//     [HAVING where_condition]
Assert::parse("SELECT foo, bar FROM uno, duo HAVING foo = 1 AND bar = 2");

//     [WINDOW window_name AS (window_spec)
//       [, window_name AS (window_spec)] ...]
// todo

//     [ORDER BY {col_name | expr | position}
//       [ASC | DESC], ...]
Assert::parse("SELECT foo, bar FROM uno, duo ORDER BY foo, bar ASC, baz DESC");
Assert::parse("SELECT foo, bar FROM uno, duo ORDER BY foo IS NULL, bar - 10 ASC, baz < 1 DESC");
Assert::parse("SELECT foo, bar FROM uno, duo ORDER BY 1, 2 ASC, 3 DESC");

//     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
Assert::parse("SELECT foo, bar FROM uno, duo LIMIT 10");
Assert::parse("SELECT foo, bar FROM uno, duo LIMIT 10 OFFSET 20");
Assert::parse("SELECT foo, bar FROM uno, duo LIMIT 20, 10", "SELECT foo, bar FROM uno, duo LIMIT 10 OFFSET 20");

//     [INTO OUTFILE 'file_name'
//         [CHARACTER SET charset_name]
//         export_options
//       | INTO DUMPFILE 'file_name'
//       | INTO var_name [, var_name]]
Assert::parse("SELECT foo, bar FROM uno, duo INTO OUTFILE 'foo.txt'");
Assert::parse("SELECT foo, bar FROM uno, duo INTO OUTFILE 'foo.txt' CHARACTER SET utf8");
Assert::parse("SELECT foo, bar FROM uno, duo INTO OUTFILE 'foo.txt' CHARSET utf8", "SELECT foo, bar FROM uno, duo INTO OUTFILE 'foo.txt' CHARACTER SET utf8");
Assert::parse("SELECT foo, bar FROM uno, duo INTO OUTFILE 'foo.txt' CHARACTER SET 'utf8'", "SELECT foo, bar FROM uno, duo INTO OUTFILE 'foo.txt' CHARACTER SET utf8");
// todo: FileFormatParser.phpt for export_options
Assert::parse("SELECT foo, bar FROM uno, duo INTO DUMPFILE 'foo.txt'");
Assert::parse("SELECT foo, bar FROM uno, duo INTO @foo, @bar");

//     [FOR UPDATE | LOCK IN SHARE MODE]]
//     [FOR {UPDATE | SHARE} [OF tbl_name [, tbl_name] ...] [NOWAIT | SKIP LOCKED]
//       | LOCK IN SHARE MODE]]
Assert::parse("SELECT foo, bar FROM uno, duo LOCK IN SHARE MODE");
Assert::parse("SELECT foo, bar FROM uno, duo FOR UPDATE");
Assert::parse("SELECT foo, bar FROM uno, duo FOR UPDATE NOWAIT");
Assert::parse("SELECT foo, bar FROM uno, duo FOR UPDATE SKIP LOCKED");
Assert::parse("SELECT foo, bar FROM uno, duo FOR UPDATE OF foo, bar");
Assert::parse("SELECT foo, bar FROM uno, duo FOR UPDATE OF foo, bar NOWAIT");
Assert::parse("SELECT foo, bar FROM uno, duo FOR UPDATE OF foo, bar SKIP LOCKED");
Assert::parse("SELECT foo, bar FROM uno, duo FOR SHARE");
Assert::parse("SELECT foo, bar FROM uno, duo FOR SHARE NOWAIT");
Assert::parse("SELECT foo, bar FROM uno, duo FOR SHARE SKIP LOCKED");
Assert::parse("SELECT foo, bar FROM uno, duo FOR SHARE OF foo, bar");
Assert::parse("SELECT foo, bar FROM uno, duo FOR SHARE OF foo, bar NOWAIT");
Assert::parse("SELECT foo, bar FROM uno, duo FOR SHARE OF foo, bar SKIP LOCKED");
