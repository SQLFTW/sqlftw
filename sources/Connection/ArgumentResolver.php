<?php

namespace SqlFtw\Connection;

use SqlFtw\Platform\Normalizer\Normalizer;
use SqlFtw\Sql\Expression\BoolLiteral;
use SqlFtw\Sql\Expression\IntLiteral;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\NumericLiteral;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;
use function abs;
use function array_key_exists;
use function floatval;
use function implode;
use function intval;
use function is_bool;
use function is_float;
use function is_int;
use function is_nan;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_replace_callback;
use function str_starts_with;
use function strlen;
use function strpos;
use function strval;
use function unpack;
use function user_error;
use const E_USER_WARNING;
use const PREG_UNMATCHED_AS_NULL;

class ArgumentResolver
{

    private const INT8_MIN = -128;
    private const INT8_MAX = 127;
    private const INT16_MIN = -32768;
    private const INT16_MAX = 32767;
    private const INT24_MIN = -8388608;
    private const INT24_MAX = 8388607;
    private const INT32_MIN = -2147483648;
    private const INT32_MAX = 2147483647;

    private const UINT8_MAX = 255;
    private const UINT16_MAX = 65535;
    private const UINT24_MAX = 16777215;
    private const UINT32_MAX = 4294967295;

    private const FLOAT32_INT_MAX = (2**24) - 1;
    private const FLOAT64_INT_MAX = (2**53) - 1;

    private static float $FLOAT32_MAX = 3.4028234663852885981e38;

    /**
     * Placeholders & parameter binding:
     * - PDOStatement::bindValue(string|int $param, $value, $type): named or positional (":foo", "?"), one by one (https://www.php.net/manual/en/pdostatement.bindvalue.php)
     * - PDOStatement::bindParam(string|int $param, &$value, $type): named or positional (":foo", "?"), one by one (https://www.php.net/manual/en/pdostatement.bindparam.php)
     * - SQLite3Stmt::bindValue(string|int $param, $value, $type): named or positional (":foo", "?"), one by one (https://www.php.net/manual/en/sqlite3stmt.bindvalue.php)
     * - SQLite3Stmt::bindParam(string|int $param, &$value, $type): named or positional (":foo", "?"), one by one (https://www.php.net/manual/en/sqlite3stmt.bindparam.php)
     * - mysqli_stmt_bind_param($res, $types, &...$values): positional only ("?, ?"), all at once (https://www.php.net/manual/en/mysqli-stmt.bind-param.php)
     * - pg_execute($res, $statement, array $params): positional only ("$1, $2"), all at once (https://www.php.net/manual/en/function.pg-execute.php)
     * - sqlsrv_prepare($conn, string $sql, array $params, array $options): positional only ("?, ?"), all at once (https://www.php.net/manual/en/function.sqlsrv-prepare.php)
     * - oci_bind_by_name($stmt, string $param, mixed &$var, $max_length, $type): named only (":foo"), one by one (https://www.php.net/manual/en/function.oci-bind-by-name.php)
     * - oci_bind_array_by_name($stmt, string $param, array &$var, $max_array_length, $max_item_length, $type): named only (":foo"), one by one (https://www.php.net/manual/en/function.oci-bind-array-by-name.php)
     * - db2_bind_param($stmt, int $param, string $variable_name, $param_type, $data_type, $precision, $scale): positional only ("?, ?"), one by one (https://www.php.net/manual/en/function.db2-bind-param.php)
     * - ibase_execute($query, mixed ...$values): positional only ("?, ?"), one by one (https://www.php.net/manual/en/function.ibase-execute.php)
     * - cubrid_bind($request, int $param, $value, $type): positional only ("?, ?"), one by one (https://www.php.net/manual/en/function.cubrid-bind.php, also: cubrid_lob2_bind())
     */
    public const ARGUMENT_STYLE_COLON_NAME = 1;
    public const ARGUMENT_STYLE_QUESTION = 2;
    public const ARGUMENT_STYLE_DOLLAR = 4;

    private const BOOL = 'bool'; // PDO::PARAM_BOOL (int)
    private const NULL = 'null'; // PDO::PARAM_NULL (int)
    private const INT = 'i'; // PDO::PARAM_INT (int)
    private const STRING = 's'; // PDO::PARAM_STR (int)
    private const BINARY = 'b'; // PDO::PARAM_LOB (int)
    // PDO::PARAM_STR_NATL (int) Flag to denote a string uses the national character set. Available since PHP 7.2.0
    // PDO::PARAM_STR_CHAR (int) Flag to denote a string uses the regular character set. Available since PHP 7.2.0
    // PDO::PARAM_STMT (int) Represents a recordset type. Not currently supported by any drivers.
    // PDO::PARAM_INPUT_OUTPUT (int) Specifies that the parameter is an INOUT parameter for a stored procedure. You must bitwise-OR this value with an explicit PDO::PARAM_* data type.

    private static array $types = [
        //          null,  size,  frac., array, description
        'name'  => [false, false, false, true, 'name'],
        'qname' => [false, false, false, true, 'qualified name'],
        'bool'  => [true,  false, false, true, 'boolean'],
        //'bit'   => [true,  false, false, true, 'boolean'],
        'i'     => [true,  true,  false, true, 'integer'],
        'ui'    => [true,  true,  false, true, 'unsigned integer'],
        'f'     => [true,  true,  false, true, 'float'],
        'uf'    => [true,  true,  false, true, 'unsigned float'],
        'n'     => [true,  true,  true,  true, 'numeric'], // aka decimal
        'un'    => [true,  true,  true,  true, 'unsigned numeric'],
        's'     => [true,  true,  false, true, 'string'],
        'b'     => [true,  true,  false, true, 'binary string'],
        /*
        'y'     => [true,  false, false, true, 'year'],
        'd'     => [true,  false, false, true, 'date'],
        't'     => [true,  true,  false, true, 'time'],
        'dt'    => [true,  true,  false, true, 'datetime'],
        'dtl'   => [true,  true,  false, true, 'datetime local'],
        'dtz'   => [true,  true,  false, true, 'datetime with time zone'],
        'ts'    => [true,  true,  false, true, 'timestamp'],
        'di'    => [true,  true,  false, true, 'date interval'], // https://dev.mysql.com/doc/refman/8.0/en/expressions.html#temporal-intervals
        'sql'   => [true,  true,  false, true, 'raw SQL'],
        */
    ];

    private Normalizer $normalizer;

    private string $typesRegexp;

    public function __construct(Normalizer $normalizer)
    {
        // exact IEEE 754 binary32 max value as binary64 value
        self::$FLOAT32_MAX = unpack('e', pack('h*', '0000000efffffe74'))[1];

        $this->normalizer = $normalizer;

        $types = implode('|', array_keys(self::$types));
        $this->typesRegexp = "~(?<=[\n\t ([])%" // %
            . "(\\?)?" // nullable
            . "({$types})" // types
            . "(?:(\\d+)(?:\\.(\\d+))?)?" // size.fraction // postgre 15+ can have a negative fraction (rounding)
            . "(?:(\\[)(?:(\\d+)(?:(\\.\\.)(\\d+)))?\\])?" // array [from ..(extent) to]
            . "(?::([a-zA-Z\\d_]))?" // :name
            . "~J";
    }


    public function resolve(string $query, array $arguments): string
    {
        $counter = 0;
        $remaining = [];
        $cb = function (array $match) use ($arguments, &$remaining, &$counter): string {
            rd($match);
            [$all, $nullable, $type, $size, $fraction, $array, $from, $extent, $to, $name] = $match;
            if (!array_key_exists($type, self::$types)) {
                throw new InvalidArgumentException("Query argument type {$type} does exist.");
            }

            [$isNullable, $hasSize, $hasFraction, $hasArray, $description] = self::$types[$type];
            if ($nullable !== null && !$isNullable) {
                throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) is not nullable.");
            } elseif ($size !== null) {
                $size = intval($size);
                if (!$hasSize) {
                    throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) does not have size parameter.");
                } elseif (($type === 'i' || $type === 'ui') && $size !== 8 && $size !== 16 && $size !== 24 && $size !== 32 && $size !== 64) {
                    throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) size parameter must be 8, 16, 24, 32 or 64.");
                } elseif (($type === 'f' || $type === 'uf') && $size !== 32 && $size !== 64) {
                    throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) size parameter must be 32 or 64.");
                }
            } elseif ($fraction !== null) {
                $fraction = intval($fraction);
                if (!$hasFraction) {
                    throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) does not have fraction parameter.");
                }
            } elseif ($array !== null && !$hasArray) {
                throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) does not have an array parameter.");
            } elseif ($name !== null && ($type === 'name' || $type === 'qname')) {
                throw new InvalidArgumentException("Invalid query argument '{$all}'. Type {$type} ({$description}) does not have a name parameter.");
            }

            if ($name !== null) {
                $argument = $name;
                if (!array_key_exists($argument, $arguments)) {
                    throw new InvalidArgumentException("Query argument {$argument} not provided.");
                }
                $value = $arguments[$name];
            } else {
                $argument = $counter;
                if (!array_key_exists($argument, $arguments)) {
                    throw new InvalidArgumentException("Query argument {$argument} not provided.");
                }
                $value = $arguments[$counter];
            }
            $counter++;

            // nullable
            if ($value === null || $value instanceof NullLiteral) {
                if ($nullable === '?') {
                    return 'NULL';
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} cannot be null.");
                }
            }

            return $this->resolveValue($value, $argument, $type, $size, $fraction);
        };
        $result = preg_replace_callback($this->typesRegexp, $cb, $query, -1, $cnt, PREG_UNMATCHED_AS_NULL);

        return $result;
    }

    /**
     * @param mixed $value
     * @param string|int $argument
     * @throws InvalidArgumentException
     */
    private function resolveValue($value, $argument, string $type, ?int $size = null, ?int $fraction = null): string
    {
        switch ($type) {
            case 'name':
                if (is_string($value)) {
                    // ok
                } elseif ($value instanceof SimpleName) {
                    $value = $value->name;
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a string value or SimpleName.");
                }
                return $this->normalizer->formatName($value);
            case 'qname':
                if (is_string($value)) {
                    // ok
                } elseif ($value instanceof SimpleName) {
                    $value = $value->name;
                } elseif ($value instanceof QualifiedName) {
                    $value = $value->getFullName();
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a string value, SimpleName or QualifiedName.");
                }
                return $this->normalizer->formatQualifiedName($value);
            case 'bool':
                if (is_bool($value)) {
                    // ok
                } elseif ($value instanceof BoolLiteral) {
                    $value = $value->asBool();
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a boolean value or BoolLiteral.");
                }
                return $this->normalizer->formatBool($value);
            case 'i':
            case 'ui':
                if (is_int($value)) {
                    // ok
                } elseif ($value === strval(intval($value))) {
                    $value = intval($value);
                } elseif ($value instanceof IntLiteral) {
                    $value = $value->asInt();
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be an integer value or IntLiteral.");
                }
                if ($type === 'ui' && $value < 0) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a non-negative integer value. {$value} given.");
                }
                if ($size !== null) {
                    if ($type === 'i') {
                        if ($size === 8 && ($value < self::INT8_MIN || $value > self::INT8_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between " . self::INT8_MIN . " and " . self::INT8_MAX . ". {$value} given.");
                        } elseif ($size === 16 && ($value < self::INT16_MIN || $value > self::INT16_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between " . self::INT16_MIN . " and " . self::INT16_MAX . ". {$value} given.");
                        } elseif ($size === 24 && ($value < self::INT24_MIN || $value > self::INT24_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between " . self::INT24_MIN . " and " . self::INT24_MAX . ". {$value} given.");
                        } elseif ($size === 32 && ($value < self::INT32_MIN || $value > self::INT32_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between " . self::INT32_MIN . " and " . self::INT32_MAX . ". {$value} given.");
                        }
                    } else {
                        if ($size === 8 && ($value < 0 || $value > self::UINT8_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between 0 and " . self::UINT8_MAX . ". {$value} given.");
                        } elseif ($size === 16 && ($value < 0 || $value > self::UINT16_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between 0 and " . self::UINT16_MAX . ". {$value} given.");
                        } elseif ($size === 24 && ($value < 0 || $value > self::UINT24_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between 0 and " . self::UINT24_MAX . ". {$value} given.");
                        } elseif ($size === 32 && ($value < 0 || $value > self::UINT32_MAX)) {
                            throw new InvalidArgumentException("Query argument {$argument} must be an integer value between 0 and " . self::UINT32_MAX . ". {$value} given.");
                        }
                    }
                }
                return strval($value);
            case 'f':
            case 'uf':
                if (is_float($value)) {
                    // ok
                } elseif (is_int($value)) {
                    if ($size === 32 && abs($value) > self::FLOAT32_INT_MAX) {
                        user_error("Precision loss when casting integer bigger than 24 bits to float32.", E_USER_WARNING);
                    } elseif (abs($value) > self::FLOAT64_INT_MAX) {
                        user_error("Precision loss when casting integer bigger than 52 bits to float64.", E_USER_WARNING);
                    }
                    $value = floatval($value);
                } elseif (is_string($value) && is_numeric($value)) {
                    // todo: might be out of float64 range
                    $value = floatval($value);
                } elseif ($value instanceof NumericLiteral) {
                    $value = $value->asNumber();
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a float value, int value or NumericLiteral.");
                }
                if (is_nan($value)) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a valid float value. NAN given.");
                } elseif ($value === INF || $value === -INF) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a finite float value. {$value} given.");
                } elseif ($type === 'uf' && $value < 0.0) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a non-negative float value. {$value} given.");
                }
                if ($size === 32 && ($value < -self::$FLOAT32_MAX || $value > self::$FLOAT32_MAX)) {
                    throw new InvalidArgumentException("Query argument {$argument} must be an float value between " . self::$FLOAT32_MAX . " and -" . self::$FLOAT32_MAX . ". {$value} given.");
                }
                return strval($value);
            case 'n':
            case 'un':
                if (is_string($value) && is_numeric($value)) {
                    if (stripos($value, 'e')) {
                        throw new InvalidArgumentException("Query argument {$argument} must be a simple numeric string, int or NumericLiteral. Strings with scientific notation (e.g. '1e23') are not supported.");
                    }
                    // ok
                } elseif (is_int($value)) {
                    // ok
                    $value = strval($value);
                } elseif ($value instanceof NumericLiteral) {
                    $value = $value->value;
                } elseif (is_float($value)) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a string, int or NumericLiteral. Float is not allowed due to possible precision loss.");
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a string, int or NumericLiteral.");
                }
                if ($type === 'un' && str_starts_with($value, '-')) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a positive number. {$value} given.");
                }
                if ($size !== null) {
                    $abs = ltrim($value, '-+0');
                    [$before, $after] = explode('.', $abs . '.');
                    if (strlen($before) + strlen($after) > $size) {
                        throw new InvalidArgumentException("Query argument {$argument} has total maximum precision of {$size} digits. {$value} given.");
                    } elseif ($fraction !== null && strlen($after) > $fraction) {
                        throw new InvalidArgumentException("Query argument {$argument} has maximum precision of {$fraction} digits after decimal point. {$value} given.");
                    }
                }
                return $value;
            case 's':
                if (is_string($value)) {
                    // ok
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a string value.");
                }
                if (strpos($value, "\0") !== false) {
                    throw new InvalidArgumentException("Query argument {$argument} must be a text string value.");
                }
                // todo: check too long and bind them as native query params
                return $this->normalizer->formatString($value);
            case 'b':
                if (is_string($value)) {
                    // ok
                } else {
                    throw new InvalidArgumentException("Query argument {$argument} must be a string value.");
                }
                // todo: check too long and bind them as native query params
                return $this->normalizer->formatBinary($value);
            default:
                throw new InvalidArgumentException("Query argument type {$type} is not implemented.");
        }
    }

}
