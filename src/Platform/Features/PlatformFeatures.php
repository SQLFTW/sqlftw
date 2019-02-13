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

    public const RESERVED_WORDS = [];
    public const NON_RESERVED_WORDS = [];

    public const OPERATOR_KEYWORDS = [];
    public const OPERATORS = [];

    public const TYPES = []; ///
    public const TYPE_ALIASES = []; ///

    public const BUILT_IN_FUNCTIONS = [];
    public const SYSTEM_VARIABLES = []; ///

    /**
     * @return string[]
     */
    public function getReservedWords(): array
    {
        return static::RESERVED_WORDS;
    }

    /**
     * @return string[]
     */
    public function getNonReservedWords(): array
    {
        return static::NON_RESERVED_WORDS;
    }

    /**
     * @return string[]
     */
    public function getOperatorKeywords(): array
    {
        return static::OPERATOR_KEYWORDS;
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        return static::OPERATORS;
    }

    /**
     * @return string[]
     */
    public function getBuiltInFunctions(): array
    {
        return static::BUILT_IN_FUNCTIONS;
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return static::TYPES;
    }

    /**
     * @return string[]
     */
    public function getTypeAliases(): array
    {
        return static::TYPE_ALIASES;
    }

    public function isKeyword(string $word): bool
    {
        return in_array($word, $this->getReservedWords(), true) || in_array($word, $this->getNonReservedWords(), true);
    }

    public function isReserved(string $word): bool
    {
        return in_array($word, $this->getReservedWords(), true);
    }

    public function isOperator(string $symbol): bool
    {
        return in_array($symbol, $this->getOperators());
    }

    public function isType(string $word): bool
    {
        return in_array($word, $this->getTypes());
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
