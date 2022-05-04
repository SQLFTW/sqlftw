<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// https://dev.mysql.com/doc/refman/8.0/en/charset-collate.html

// With ORDER BY
Assert::parse("SELECT k FROM t1 ORDER BY k COLLATE latin1_german2_ci");

// With AS
Assert::parse("SELECT k COLLATE latin1_german2_ci AS k1 FROM t1 ORDER BY k1");

// With GROUP BY
Assert::parse("SELECT k FROM t1 GROUP BY k COLLATE latin1_german2_ci");

// With aggregate functions
Assert::parse("SELECT MAX(k COLLATE latin1_german2_ci) FROM t1");

// With DISTINCT
Assert::parse("SELECT DISTINCT k COLLATE latin1_german2_ci FROM t1");

// todo: _ charset declaration is not implemented yet
/*
// With WHERE
Assert::parse("SELECT * FROM t1 WHERE _latin1 'Müller' COLLATE latin1_german2_ci = k");
Assert::parse("SELECT * FROM t1 WHERE k LIKE _latin1 'Müller' COLLATE latin1_german2_ci");

// With HAVING
Assert::parse("SELECT k FROM t1 GROUP BY k HAVING k = _latin1 'Müller' COLLATE latin1_german2_ci");
*/
