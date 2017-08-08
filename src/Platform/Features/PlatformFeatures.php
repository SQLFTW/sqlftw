<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Features;

abstract class PlatformFeatures
{
    use \Dogma\StrictBehaviorMixin;

    public const RESERVED_WORDS = [];

    public const NON_RESERVED_WORDS = [];

    public const OPERATOR_KEYWORDS = [];

    public const FUNCTIONS = [];

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
    public function getFunctions(): array
    {
        return static::FUNCTIONS;
    }

    public function isKeyword(string $word): bool
    {
        return in_array($word, $this->getReservedWords()) || in_array($word, $this->getNonReservedWords());
    }

    public function isReserved(string $word): bool
    {
        return in_array($word, $this->getReservedWords());
    }

}
