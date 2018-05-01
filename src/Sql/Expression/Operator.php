<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\Keyword;

class Operator extends \SqlFtw\Sql\SqlEnum implements \SqlFtw\Sql\Feature
{

    // assign
    public const ASSIGN = ':=';

    // boolean
    public const AND = Keyword::AND;
    public const OR = Keyword::OR;
    public const XOR = Keyword::XOR;
    public const NOT = Keyword::NOT;
    public const AMPERSANDS = '&&';
    public const PIPES = '||'; // OR or CONCAT
    public const EXCLAMATION = '!';

    // comparison
    public const EQUAL = '=';
    public const SAFE_EQUAL = '<=>';
    public const NON_EQUAL = '!=';
    public const LESS_OR_GREATER = '<>';
    public const LESS = '<';
    public const LESS_OR_EQUAL = '<=';
    public const GREATER = '>';
    public const GREATER_OR_EQUAL = '>=';
    public const BETWEEN = Keyword::BETWEEN;

    // arithmetic
    public const PLUS = '+';
    public const MINUS = '-';
    public const MULTIPLY = '*';
    public const DIVIDE = '/';
    public const DIV = Keyword::DIV;
    public const MOD = Keyword::MOD;
    public const MODULO = '%';

    // binary
    public const BIT_AND = '&';
    public const BIT_INVERT = '~';
    public const BIT_OR = '|';
    public const BIT_XOR = '^';
    public const LEFT_SHIFT = '<<';
    public const RIGHT_SHIFT = '>>';

    // test
    public const IS = Keyword::IS;
    public const LIKE = Keyword::LIKE;
    public const ESCAPE = Keyword::ESCAPE;
    public const REGEXP = Keyword::REGEXP;
    public const RLIKE = Keyword::RLIKE;
    public const SOUNDS = Keyword::SOUNDS;

    // case
    public const CASE = Keyword::CASE;
    public const WHEN = Keyword::WHEN;
    public const THEN = Keyword::THEN;
    public const ELSE = Keyword::ELSE;
    public const END = Keyword::END;

    // quantifiers
    public const IN = Keyword::IN;
    public const ANY = Keyword::ANY;
    public const SOME = Keyword::SOME;
    public const ALL = Keyword::ALL;
    public const EXISTS = Keyword::EXISTS;

    // collation
    public const BINARY = Keyword::BINARY;

    // JSON
    public const JSON_EXTRACT = '->';
    public const JSON_EXTRACT_UNQUOTE = '->>';

    public function isUnary(): bool
    {
        return in_array($this->getValue(), [
            self::PLUS,
            self::MINUS,
            self::EXCLAMATION,
            self::BIT_INVERT,
            self::EXISTS,
            self::BINARY,
        ]);
    }

    public function isBinary(): bool
    {
        return !in_array($this->getValue(), [
            self::EXCLAMATION,
            self::BIT_INVERT,
            self::EXISTS,
            self::BINARY,
            self::BETWEEN,
            self::ESCAPE,
        ]);
    }

    public function isTernaryLeft(): bool
    {
        return in_array($this->getValue(), [self::BETWEEN, self::LIKE]);
    }

    public function isTernaryRight(): bool
    {
        return in_array($this->getValue(), [self::AND, self::ESCAPE]);
    }

    /**
     * @throws \Dogma\InvalidValueException
     */
    public function checkUnary(): void
    {
        if (!$this->isUnary()) {
            throw new \Dogma\InvalidValueException($this->getValue(), 'unary operator');
        }
    }

    /**
     * @throws \Dogma\InvalidValueException
     */
    public function checkBinary(): void
    {
        if (!$this->isBinary()) {
            throw new \Dogma\InvalidValueException($this->getValue(), 'binary operator');
        }
    }

    /**
     * @throws \Dogma\InvalidValueException
     */
    public function checkTernaryLeft(): void
    {
        if (!$this->isTernaryLeft()) {
            throw new \Dogma\InvalidValueException($this->getValue(), 'ternary operator');
        }
    }

    /**
     * @throws \Dogma\InvalidValueException
     */
    public function checkTernaryRight(): void
    {
        if (!$this->isTernaryRight()) {
            throw new \Dogma\InvalidValueException($this->getValue(), 'ternary operator');
        }
    }

}
