<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/paranoiq/dogma)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use Dogma\Enum\StringEnum;
use SqlFtw\Formatter\Formatter;
use function strtolower;

/**
 * Values passed to get() constructor are case-insensitive.
 * Other methods like equalsAny() are strict.
 */
abstract class SqlEnum extends StringEnum implements SqlSerializable
{

    /** @var array<class-string, array<string, string>> */
    private static $lowerValues = [];

    public static function validateValue(string &$value): bool
    {
        $class = static::class;

        // create lower-case index
        if (!isset(self::$lowerValues[$class])) {
            $values = [];
            foreach (self::getAllowedValues() as $val) {
                $values[strtolower($val)] = $val;
            }
            self::$lowerValues[$class] = $values;
        }

        $lower = strtolower($value);
        $values = self::$lowerValues[$class];
        if (isset($values[$lower])) {
            $value = $values[$lower];
        }

        return parent::validateValue($value);
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->getValue();
    }

    public function equalsAny(string ...$values): bool
    {
        foreach ($values as $value) {
            if ($this->equalsValue($value)) {
                return true;
            }
        }

        return false;
    }

}
