<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Analyzer\Context;

use SqlFtw\Connection\ArgumentResolver;
use SqlFtw\Connection\InvalidArgumentException;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Tests\Assert;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

require __DIR__ . '/../bootstrap.php';

$platform = Platform::get(Platform::MYSQL, '8.0');
$session = new Session($platform);
$normalizer = $session->getNormalizer();
$resolver = new ArgumentResolver($normalizer);

/**
 * @param array<mixed> $params
 */
$fails = static function (string $query, array $params, $message) use ($resolver): void {
    Assert::exception(static function () use ($query, $params, $message, $resolver): void {
        $resolver->resolve($query, $params);
    }, InvalidArgumentException::class);
};


names:
Assert::same($resolver->resolve('SELECT * FROM %name', ['tbl1']), 'SELECT * FROM `tbl1`');
Assert::same($resolver->resolve('SELECT * FROM %name', ['schema1.tbl1']), 'SELECT * FROM `schema1.tbl1`');
$fails('SELECT * FROM %?name', ['foo'], '');

Assert::same($resolver->resolve('SELECT * FROM %qname', ['tbl1']), 'SELECT * FROM `tbl1`');
Assert::same($resolver->resolve('SELECT * FROM %qname', ['schema1.tbl1']), 'SELECT * FROM `schema1`.`tbl1`');
$fails('SELECT * FROM %?qname', ['foo'], '');


bools:
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 IS %bool', [true]), 'SELECT * FROM tbl1 WHERE col1 IS TRUE');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 IS %bool', [false]), 'SELECT * FROM tbl1 WHERE col1 IS FALSE');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 IS %?bool', [null]), 'SELECT * FROM tbl1 WHERE col1 IS NULL');
$fails('SELECT * FROM %bool', [null], '');


ints:
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i', ['123']), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i', [123]), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i', [-123]), 'SELECT * FROM tbl1 WHERE col1 = -123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %?i', [null]), 'SELECT * FROM tbl1 WHERE col1 = NULL'); // sic!
$fails('SELECT * FROM %i', [null], '');

Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i8', [2**7 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 127');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i8', [-2**7]), 'SELECT * FROM tbl1 WHERE col1 = -128');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i16', [2**15 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 32767');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i16', [-2**15]), 'SELECT * FROM tbl1 WHERE col1 = -32768');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i24', [2**23 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 8388607');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i24', [-2**23]), 'SELECT * FROM tbl1 WHERE col1 = -8388608');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i32', [2**31 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 2147483647');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i32', [-2**31]), 'SELECT * FROM tbl1 WHERE col1 = -2147483648');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i64', [PHP_INT_MAX]), 'SELECT * FROM tbl1 WHERE col1 = 9223372036854775807');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %i64', [PHP_INT_MIN]), 'SELECT * FROM tbl1 WHERE col1 = -9223372036854775808');
$fails('SELECT * FROM %i8', [2**8], '');
$fails('SELECT * FROM %i8', [-2**8 - 1], '');
$fails('SELECT * FROM %i16', [2**16], '');
$fails('SELECT * FROM %i16', [-2**16 - 1], '');
$fails('SELECT * FROM %i24', [2**24], '');
$fails('SELECT * FROM %i24', [-2**24 - 1], '');
$fails('SELECT * FROM %i32', [2**32], '');
$fails('SELECT * FROM %i32', [-2**32 - 1], '');

Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui', ['123']), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui', [123]), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %?ui', [null]), 'SELECT * FROM tbl1 WHERE col1 = NULL'); // sic!
$fails('SELECT * FROM %ui', [-1], '');
$fails('SELECT * FROM %ui', [null], '');

Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui8', [2**8 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 255');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui16', [2**16 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 65535');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui24', [2**24 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 16777215');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui32', [2**32 - 1]), 'SELECT * FROM tbl1 WHERE col1 = 4294967295');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %ui64', [PHP_INT_MAX]), 'SELECT * FROM tbl1 WHERE col1 = 9223372036854775807');
$fails('SELECT * FROM %ui8', [2**8], '');
$fails('SELECT * FROM %ui8', [-2**8 - 1], '');
$fails('SELECT * FROM %ui16', [2**16], '');
$fails('SELECT * FROM %ui16', [-2**16 - 1], '');
$fails('SELECT * FROM %ui24', [2**24], '');
$fails('SELECT * FROM %ui24', [-2**24 - 1], '');
$fails('SELECT * FROM %ui32', [2**32], '');
$fails('SELECT * FROM %ui32', [-2**32 - 1], '');


floats:
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', ['123']), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', ['-123']), 'SELECT * FROM tbl1 WHERE col1 = -123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', ['12.3']), 'SELECT * FROM tbl1 WHERE col1 = 12.3');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', ['-12.3']), 'SELECT * FROM tbl1 WHERE col1 = -12.3');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', [123]), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', [-123]), 'SELECT * FROM tbl1 WHERE col1 = -123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', [12.3]), 'SELECT * FROM tbl1 WHERE col1 = 12.3');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f', [-12.3]), 'SELECT * FROM tbl1 WHERE col1 = -12.3');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %?f', [null]), 'SELECT * FROM tbl1 WHERE col1 = NULL'); // sic!
$fails('SELECT * FROM %f', [null], '');
$fails('SELECT * FROM %f', [NAN], '');
$fails('SELECT * FROM %f', [INF], '');
$fails('SELECT * FROM %f', [-INF], '');

Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f32', [3.402823466385288e38]), 'SELECT * FROM tbl1 WHERE col1 = 3.4028234663853E+38');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f32', [-3.402823466385288e38]), 'SELECT * FROM tbl1 WHERE col1 = -3.4028234663853E+38');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f64', [3.402823466385288e40]), 'SELECT * FROM tbl1 WHERE col1 = 3.4028234663853E+40');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %f64', [-3.402823466385288e40]), 'SELECT * FROM tbl1 WHERE col1 = -3.4028234663853E+40');
$fails('SELECT * FROM %f32', [3.402823466385288e40], '');
$fails('SELECT * FROM %f32', [-3.402823466385288e40], '');

Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %uf', ['123']), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %uf', ['12.3']), 'SELECT * FROM tbl1 WHERE col1 = 12.3');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %uf', [123]), 'SELECT * FROM tbl1 WHERE col1 = 123');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %uf', [12.3]), 'SELECT * FROM tbl1 WHERE col1 = 12.3');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %?uf', [null]), 'SELECT * FROM tbl1 WHERE col1 = NULL'); // sic!
$fails('SELECT * FROM %ui', [-0.1], '');
$fails('SELECT * FROM %ui', [null], '');

Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %uf32', [3.402823466385288e38]), 'SELECT * FROM tbl1 WHERE col1 = 3.4028234663853E+38');
Assert::same($resolver->resolve('SELECT * FROM tbl1 WHERE col1 = %uf64', [3.402823466385288e40]), 'SELECT * FROM tbl1 WHERE col1 = 3.4028234663853E+40');
$fails('SELECT * FROM %uf32', [3.402823466385288e40], '');
$fails('SELECT * FROM %uf32', [-0.1], '');


strings:


binaries:



