<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Features;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Dal\SystemVariable;
use SqlFtw\Sql\Ddl\BaseType;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Feature;
use SqlFtw\Sql\Keyword;
use function in_array;

abstract class PlatformFeatures
{
    use StrictBehaviorMixin;

    /** @var string[] */
    public static $reservedWords = [];

    /** @var string[] */
    public static $nonReservedWords = [];

    /** @var string[] */
    public static $operatorKeywords = [];

    /** @var string[] */
    public static $operators = [];

    /** @var string[] */
    public static $types = [];

    /** @var string[] */
    public static $typeAliases = [];

    /** @var string[] */
    public static $builtInFunctions = [];

    /** @var string[] */
    public static $systemVariables = [];

    public function isKeyword(string $word): bool
    {
        return in_array($word, static::$reservedWords, true) || in_array($word, static::$nonReservedWords, true);
    }

    public function isReserved(string $word): bool
    {
        return in_array($word, static::$reservedWords, true);
    }

    public function isOperator(string $symbol): bool
    {
        return in_array($symbol, static::$operators);
    }

    public function isType(string $word): bool
    {
        return in_array($word, static::$types);
    }

    /**
     * @param \SqlFtw\Sql\Feature $feature
     * @return bool
     */
    public function available(Feature $feature): bool
    {
        if ($feature instanceof Keyword) {
            return $this->isKeyword($feature->getValue());
        } elseif ($feature instanceof Operator) {
            return $this->isOperator($feature->getValue());
        } elseif ($feature instanceof BuiltInFunction) {
            // todo: built in functions availability
            return false;
        } elseif ($feature instanceof SystemVariable) {
            // todo: system variables availability
            return false;
        } elseif ($feature instanceof BaseType) {
            return $this->isType($feature->getValue());
        } else {
            return false;
        }
    }

}
