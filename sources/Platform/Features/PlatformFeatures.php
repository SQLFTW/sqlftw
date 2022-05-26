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
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Feature;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MysqlVariable;
use function in_array;

abstract class PlatformFeatures
{
    use StrictBehaviorMixin;

    /**
     * @return string[]
     */
    abstract public function getReservedWords(): array;

    /**
     * @return string[]
     */
    abstract public function getNonReservedWords(): array;

    /**
     * @return string[]
     */
    abstract public function getOperatorKeywords(): array;

    /**
     * @return string[]
     */
    abstract public function getOperators(): array;

    /**
     * @return string[]
     */
    abstract public function getTypes(): array;

    /**
     * @return string[]
     */
    abstract public function getTypeAliases(): array;

    /**
     * @return string[]
     */
    abstract public function getBuiltInFunctions(): array;

    /**
     * @return string[]
     */
    abstract public function getSystemVariables(): array;

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
        return in_array($symbol, $this->getOperators(), true);
    }

    public function isType(string $word): bool
    {
        return in_array($word, $this->getTypes(), true);
    }

    public function available(Feature $feature): bool
    {
        if ($feature instanceof Keyword) {
            return $this->isKeyword($feature->getValue());
        } elseif ($feature instanceof Operator) {
            return $this->isOperator($feature->getValue());
        } elseif ($feature instanceof BuiltInFunction) {
            // todo: built in functions availability
            return false;
        } elseif ($feature instanceof MysqlVariable) {
            // todo: system variables availability
            return false;
        } elseif ($feature instanceof BaseType) {
            return $this->isType($feature->getValue());
        } else {
            return false;
        }
    }

}
