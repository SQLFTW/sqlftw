<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\InvalidValueException as InvalidEnumValueException;
use Dogma\Str;
use Dogma\StrictBehaviorMixin;
use InvalidArgumentException;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\Entity;
use SqlFtw\Sql\Expression\BinaryLiteral;
use SqlFtw\Sql\Expression\HexadecimalLiteral;
use SqlFtw\Sql\Expression\IntLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SizeLiteral;
use SqlFtw\Sql\Expression\StringLiteral;
use SqlFtw\Sql\Expression\StringValue;
use SqlFtw\Sql\Expression\Value;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;
use SqlFtw\Sql\UserName;
use function array_slice;
use function array_values;
use function call_user_func;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function ltrim;
use function preg_match;
use function rtrim;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function ucfirst;

/**
 * Holds list of lexer tokens and a pointer to current token
 *
 * Method names explanation:
 * - seekFoo() - seeks token forward without consuming it
 * - hasFoo() - consume token if exists and return bool
 * - hasFoos() - consume all tokens if exist and return bool
 * - hasAnyFoo - consume one token and return bool
 * - getFoo() - consume token if exists and return it
 * - getFoos() - consume all tokens if exist and return it (serialized)
 * - getAnyFoo - consume one token if exists and return it
 * - passFoo() - consume token if exists, return nothing
 * - passFoos() - consume all tokens if they exist, return nothing
 * - passAnyFoo() - consume one token if exists, return nothing
 * - expectFoo() - consume token or throw an exception
 * - expectFoos() - consume all tokens or throw an exception
 * - expectAnyFoo() - consume one token or throw an exception
 * - missingFoo() - always throws an exception (just formats the error message)
 */
class TokenList
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<Token> */
    private $tokens;

    /** @var ParserSettings */
    private $settings;

    /** @var Platform */
    private $platform;

    /** @var array<string, int> */
    private $maxLengths;

    /** @var bool */
    private $invalid;

    // parser state ----------------------------------------------------------------------------------------------------

    /** @var int */
    private $autoSkip;

    /** @var int */
    private $position = 0;

    /** @var bool Are we inside a function or procedure definition? */
    private $inRoutine = false;

    /** @var bool Are we inside a UNION expression? */
    private $inUnion = false;

    /** @var bool Are we inside a prepared statement declaration? */
    private $inPrepared = false;

    /** @var int Are we inside a subquery? */
    private $inSubquery = 0;

    /** @var bool Should we expect a delimiter after the command? (command directly embedded into another command) */
    private $embedded = false;

    /**
     * @param non-empty-array<Token> $tokens
     */
    public function __construct(array $tokens, ParserSettings $settings, int $autoSkip = 0, bool $invalid = false)
    {
        $this->tokens = $tokens;
        $this->settings = $settings;
        $this->platform = $settings->getPlatform();
        $this->maxLengths = $settings->getMaxLengths();
        $this->autoSkip = $autoSkip;
        $this->invalid = $invalid;
    }

    /**
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getSettings(): ParserSettings
    {
        return $this->settings;
    }

    public function getAutoSkip(): int
    {
        return $this->autoSkip;
    }

    public function setAutoSkip(int $autoSkip): void
    {
        $this->autoSkip = $autoSkip;
    }

    public function invalid(): bool
    {
        return $this->invalid;
    }

    public function inRoutine(): bool
    {
        return $this->inRoutine;
    }

    public function setInRoutine(bool $value): void
    {
        $this->inRoutine = $value;
    }

    public function inUnion(): bool
    {
        return $this->inUnion;
    }

    public function setInUnion(bool $value): void
    {
        $this->inUnion = $value;
    }

    public function inPrepared(): bool
    {
        return $this->inPrepared;
    }

    public function setInPrepared(bool $value): void
    {
        $this->inPrepared = $value;
    }

    public function inSubquery(): bool
    {
        return $this->inSubquery > 0;
    }

    public function setInSubquery(bool $value): void
    {
        $this->inSubquery += $value ? 1 : -1;
    }

    public function embedded(): bool
    {
        return $this->embedded;
    }

    public function setEmbedded(bool $value): void
    {
        $this->embedded = $value;
    }

    public function using(?string $platform = null, ?int $minVersion = null, ?int $maxVersion = null): bool
    {
        return $this->platform->matches($platform, $minVersion, $maxVersion);
    }

    public function check(string $feature, ?int $minVersion = null, ?int $maxVersion = null, ?string $platform = null): void
    {
        if (!$this->platform->matches($platform, $minVersion, $maxVersion)) {
            throw new InvalidVersionException($feature, $this);
        }
    }

    public function finish(): void
    {
        $this->position = count($this->tokens);
    }

    public function isFinished(): bool
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }

        if ($this->position >= count($this->tokens)) {
            return true;
        }

        // check that all remaining tokens can be ignored
        for ($n = $this->position; $n < count($this->tokens); $n++) {
            $token = $this->tokens[$n];
            if (($token->type & $this->autoSkip) !== 0) {
                continue;
            } elseif (($token->type & T::SYMBOL) !== 0 && $token->value === ';') {
                // trailing ;
                continue;
            } elseif (($token->type & T::DELIMITER) !== 0) {
                // trailing delimiter
                continue;
            } else {
                return false;
            }
        }

        return true;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function rewind(int $position = 0): self
    {
        if ($position < 0) {
            $this->position += $position;
        } elseif ($position > count($this->tokens)) {
            $this->position = count($this->tokens) - 1;
        } else {
            $this->position = $position;
        }

        return $this;
    }

    private function doAutoSkip(): void
    {
        $token = $this->tokens[$this->position] ?? null;
        while ($token !== null && ($this->autoSkip & $token->type) !== 0) {
            $this->position++;
            $token = $this->tokens[$this->position] ?? null;
        }
    }

    public function getStartOffset(): int
    {
        return $this->tokens[0]->position;
    }

    public function getEndOffset(): int
    {
        $token = end($this->tokens);
        $value = $token->original ?? $token->value;

        return $token->position + strlen($value);
    }

    public function slice(int $startOffset, int $endOffset): self
    {
        if ($startOffset >= $endOffset) {
            throw new InvalidArgumentException('Start offset should be smaller than end offset');
        }
        /** @var non-empty-array<Token> $tokens */
        $tokens = array_slice($this->tokens, $startOffset, $endOffset - $startOffset + 1);

        return new self($tokens, $this->settings, $this->autoSkip);
    }

    public function getLast(): Token
    {
        $position = $this->position;
        do {
            $position--;
            $token = $this->tokens[$position] ?? null;
        } while ($token !== null && ($this->autoSkip & $token->type) !== 0);

        return $token ?? $this->tokens[0];
    }

    public function getFirstSignificantToken(): ?Token
    {
        foreach ($this->tokens as $token) {
            if (($token->type & (T::WHITESPACE | T::COMMENT)) === 0) {
                return $token;
            }
        }

        return null;
    }

    public function serialize(): string
    {
        $result = '';
        foreach ($this->tokens as $token) {
            $result .= $token->original ?? $token->value;
            if (($this->autoSkip & T::WHITESPACE) === 0) {
                $result .= ' ';
            }
        }

        return trim($result);
    }

    // seek ------------------------------------------------------------------------------------------------------------

    public function seek(int $type, ?string $value = null, int $maxOffset = 1000): ?Token
    {
        $position = $this->position;
        for ($n = 0; $n < $maxOffset; $n++) {
            if ($this->autoSkip !== 0) {
                $this->doAutoSkip();
            }
            $token = $this->tokens[$this->position] ?? null;
            if ($token === null) {
                break;
            }
            $this->position++;
            if (($token->type & $type) !== 0 && ($value === null || $value === $token->value)) {
                $this->position = $position;

                return $token;
            }
        }
        $this->position = $position;

        return null;
    }

    public function seekKeyword(string $keyword, int $maxOffset): bool
    {
        $position = $this->position;
        for ($n = 0; $n < $maxOffset; $n++) {
            if ($this->autoSkip !== 0) {
                $this->doAutoSkip();
            }
            $token = $this->tokens[$this->position] ?? null;
            if ($token === null) {
                break;
            }
            $this->position++;
            if (($token->type & T::KEYWORD) !== 0 && strtoupper($token->value) === $keyword) {
                $this->position = $position;

                return true;
            }
        }
        $this->position = $position;

        return false;
    }

    public function seekKeywordBefore(string $keyword, string $beforeKeyword, int $maxOffset = 1000): bool
    {
        $position = $this->position;
        for ($n = 0; $n < $maxOffset; $n++) {
            if ($this->autoSkip !== 0) {
                $this->doAutoSkip();
            }
            $token = $this->tokens[$this->position] ?? null;
            if ($token === null) {
                break;
            }
            $this->position++;
            if (($token->type & T::KEYWORD) !== 0) {
                if (strtoupper($token->value) === $keyword) {
                    $this->position = $position;

                    return true;
                } elseif (strtoupper($token->value) === $beforeKeyword) {
                    $this->position = $position;

                    return false;
                }
            }
        }
        $this->position = $position;

        return false;
    }

    // general ---------------------------------------------------------------------------------------------------------

    /**
     * @return never
     */
    public function missing(string $description): void
    {
        throw new InvalidTokenException($description, $this);
    }

    public function expect(int $tokenType, int $tokenMask = 0): Token
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & $tokenType) === 0 || ($token->type & $tokenMask) !== 0) {
            throw InvalidTokenException::tokens($tokenType, $tokenMask, null, $token, $this);
        }
        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     */
    public function get(?int $tokenType = null, int $tokenMask = 0, ?string $value = null): ?Token
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null) {
            return null;
        }
        if ($tokenType !== null && ($token->type & $tokenType) === 0 || ($token->type & $tokenMask) !== 0) {
            return null;
        }
        if ($value !== null && strtolower($token->value) !== strtolower($value)) {
            return null;
        }

        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     */
    public function has(int $tokenType, ?string $value = null): bool
    {
        return $this->get($tokenType, 0, $value) !== null;
    }

    public function pass(int $tokenType, ?string $value = null): void
    {
        $this->get($tokenType, 0, $value);
    }

    // symbols ---------------------------------------------------------------------------------------------------------

    public function expectSymbol(string $symbol): Token
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & T::SYMBOL) === 0) {
            throw InvalidTokenException::tokens(T::SYMBOL, 0, $symbol, $token, $this);
        }
        if ($token->value !== $symbol) {
            throw InvalidTokenException::tokens(T::SYMBOL, 0, $symbol, $token, $this);
        }
        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     */
    public function hasSymbol(string $symbol): bool
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null && ($token->type & T::SYMBOL) !== 0 && $token->value === $symbol) {
            $this->position++;

            return true;
        } else {
            return false;
        }
    }

    /**
     * @phpstan-impure
     */
    public function passSymbol(string $symbol): void
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null && ($token->type & T::SYMBOL) !== 0 && $token->value === $symbol) {
            $this->position++;
        }
    }

    // operators -------------------------------------------------------------------------------------------------------

    public function expectOperator(string $operator): string
    {
        $token = $this->expect(T::OPERATOR);
        $upper = strtoupper($token->value);
        if ($upper !== $operator) {
            $this->position--;

            throw InvalidTokenException::tokens(T::OPERATOR, 0, $operator, $token, $this);
        }

        return $upper;
    }

    /**
     * @phpstan-impure
     */
    public function hasOperator(string $operator): bool
    {
        $position = $this->position;

        $token = $this->get(T::OPERATOR);
        if ($token === null) {
            return false;
        } elseif (strtoupper($token->value) === $operator) {
            return true;
        } else {
            $this->position = $position;

            return false;
        }
    }

    public function expectAnyOperator(string ...$operators): string
    {
        $token = $this->expect(T::OPERATOR);
        $upper = strtoupper($token->value);
        if (!in_array($upper, $operators, true)) {
            $this->position--;

            throw InvalidTokenException::tokens(T::OPERATOR, 0, $operators, $token, $this);
        }

        return $token->value;
    }

    public function getAnyOperator(string ...$operators): ?string
    {
        $position = $this->position;

        $token = $this->get(T::OPERATOR);
        if ($token === null || !in_array(strtoupper($token->value), $operators, true)) {
            $this->position = $position;

            return null;
        }

        return strtoupper($token->value);
    }

    // numbers ---------------------------------------------------------------------------------------------------------

    public function expectUnsignedInt(): string
    {
        return $this->expect(T::UINT)->value;
    }

    public function getUnsignedInt(): ?string
    {
        $token = $this->get(T::UINT);
        if ($token === null) {
            return null;
        }

        return $token->value;
    }

    public function expectInt(): string
    {
        return $this->expect(T::INT)->value;
    }

    public function expectIntLike(): Value
    {
        $number = $this->expect(T::INT | T::STRING | T::HEXADECIMAL_LITERAL | T::BINARY_LITERAL);
        $value = $number->value;
        if (($number->type & T::STRING) !== 0 && preg_match('~^(?:0|-[1-9][0-9]*)$~', $value) === 0) {
            throw new InvalidValueException('integer', $this);
        }

        if (($number->type & T::HEXADECIMAL_LITERAL) !== 0) {
            return new HexadecimalLiteral($value);
        } elseif (($number->type & T::BINARY_LITERAL) !== 0) {
            return new BinaryLiteral($value);
        } else {
            return new IntLiteral($value);
        }
    }

    public function expectSize(): SizeLiteral
    {
        $token = $this->expect(T::UINT | T::NAME);
        if (($token->type & T::UINT) !== 0) {
            return new SizeLiteral($token->value);
        }

        if (preg_match(SizeLiteral::REGEXP, $token->value) === 0) {
            throw new InvalidValueException('size', $this);
        }

        return new SizeLiteral($token->value);
    }

    public function expectBool(): bool
    {
        // TRUE, FALSE, ON, OFF, 1, 0, Y, N, T, F
        $value = $this->expect(T::VALUE)->value;

        if ($value === '1' || $value === 'Y' || $value === 'T' || $value === 'y' || $value === 't') {
            return true;
        } elseif ($value === '0' || $value === 'N' || $value === 'F' || $value === 'n' || $value === 'f' || $value === '') {
            return false;
        }

        throw new InvalidValueException("boolean", $this);
    }

    public function expectUuid(): string
    {
        $token = $this->expect(T::UUID | T::STRING);
        if (($token->type & T::STRING) !== 0) {
            if (preg_match(Lexer::UUID_REGEXP, $token->value) === 0) {
                throw new InvalidValueException('uuid', $this);
            }
        }

        return $token->value;
    }

    // strings ---------------------------------------------------------------------------------------------------------

    public function expectString(): string
    {
        return $this->expect(T::STRING)->value;
    }

    public function getString(): ?string
    {
        $token = $this->get(T::STRING);

        return $token !== null ? $token->value : null;
    }

    public function expectStringValue(): StringValue
    {
        $position = $this->position;
        $token = $this->expect(T::STRING | T::HEXADECIMAL_LITERAL | T::BINARY_LITERAL | T::UNQUOTED_NAME);

        // charset introducer
        $charset = null;
        if (($token->type & T::UNQUOTED_NAME) !== 0) {
            $charset = substr(strtolower($token->value), 1);
            if ($token->value[0] === '_' && Charset::isValid($charset)) {
                $charset = Charset::get($charset);
                $token = $this->expect(T::STRING | T::HEXADECIMAL_LITERAL | T::BINARY_LITERAL);
            } else {
                $charset = null;
                $this->position = $position;

                $token = $this->expect(T::STRING | T::HEXADECIMAL_LITERAL | T::BINARY_LITERAL);
            }
        }

        if (($token->type & T::HEXADECIMAL_LITERAL) !== 0) {
            return new HexadecimalLiteral($token->value, $charset);
        } elseif (($token->type & T::BINARY_LITERAL) !== 0) {
            return new BinaryLiteral($token->value, $charset);
        } else {
            /** @var non-empty-array<string> $values */
            $values = [$token->value];
            while (($next = $this->getString()) !== null) {
                $values[] = $next;
            }

            return new StringLiteral($values, $charset);
        }
    }

    public function getStringValue(): ?StringValue
    {
        $position = $this->position;
        $token = $this->get(T::STRING | T::HEXADECIMAL_LITERAL | T::BINARY_LITERAL | T::UNQUOTED_NAME);
        if ($token === null) {
            return null;
        }

        // charset introducer
        $charset = null;
        if (($token->type & T::UNQUOTED_NAME) !== 0) {
            $lower = substr(strtolower($token->value), 1);
            if ($token->value[0] === '_' && Charset::isValid($lower)) {
                $charset = Charset::get($lower);
                $token = $this->get(T::STRING | T::HEXADECIMAL_LITERAL | T::BINARY_LITERAL);
            } else {
                $this->position = $position;

                return null;
            }
        }
        if ($token === null) {
            $this->position = $position;

            return null;
        }

        if (($token->type & T::HEXADECIMAL_LITERAL) !== 0) {
            return new HexadecimalLiteral($token->value, $charset);
        } elseif (($token->type & T::BINARY_LITERAL) !== 0) {
            return new BinaryLiteral($token->value, $charset);
        } else {
            /** @var non-empty-array<string> $values */
            $values = [$token->value];
            while (($next = $this->getString()) !== null) {
                $values[] = $next;
            }

            return new StringLiteral($values, $charset);
        }
    }

    public function expectNonReservedNameOrString(): string
    {
        return $this->expect(T::NAME | T::STRING, T::RESERVED)->value;
    }

    public function getNonReservedNameOrString(): ?string
    {
        $token = $this->get(T::NAME | T::STRING, T::RESERVED);

        return $token !== null ? $token->value : null;
    }

    public function getNameOrStringEnumValue(string ...$values): ?string
    {
        $token = $this->get(T::NAME | T::STRING);
        if ($token === null) {
            return null;
        }

        $value = strtoupper($token->value);
        if (!in_array($value, $values, true)) {
            $this->position--;

            return null;
        }

        return $value;
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function expectNameOrStringEnum(string $className): SqlEnum
    {
        $value = $this->expectNonReservedNameOrString();

        try {
            /** @var T $enum */
            $enum = call_user_func([$className, 'get'], $value);

            return $enum;
        } catch (InvalidEnumValueException $e) {
            $this->position--;
            /** @var string[] $values */
            $values = call_user_func([$className, 'getAllowedValues']);

            throw InvalidTokenException::tokens(T::NAME | T::STRING, 0, $values, $this->tokens[$this->position - 1], $this);
        }
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function expectMultiNameEnum(string $className): SqlEnum
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $start = $this->position;
        /** @var string[] $values */
        $values = call_user_func([$className, 'getAllowedValues']);
        foreach ($values as $value) {
            $this->position = $start;
            $keywords = explode(' ', $value);
            foreach ($keywords as $keyword) {
                if (!$this->hasName($keyword)) {
                    continue 2;
                }
            }

            /** @var T $enum */
            $enum = call_user_func([$className, 'get'], $value);

            return $enum;
        }
        $this->position = $start;

        throw InvalidTokenException::tokens(T::NAME, 0, $values, $this->tokens[$this->position], $this);
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function getMultiNameEnum(string $className): ?SqlEnum
    {
        $start = $this->position;
        /** @var string[] $values */
        $values = call_user_func([$className, 'getAllowedValues']);
        foreach ($values as $value) {
            $this->position = $start;
            $keywords = explode(' ', $value);
            foreach ($keywords as $keyword) {
                if (!$this->hasName($keyword)) {
                    continue 2;
                }
            }

            /** @var T $enum */
            $enum = call_user_func([$className, 'get'], $value);

            return $enum;
        }

        return null;
    }

    // names ---------------------------------------------------------------------------------------------------------

    public function expectName(?string $object, ?string $name = null, int $mask = 0): string
    {
        $token = $this->expect(T::NAME, $mask);
        if ($name !== null && strtoupper($token->value) !== $name) {
            $this->position--;

            throw InvalidTokenException::tokens(T::NAME, 0, $name, $token, $this);
        }
        $this->validateName($object, $token->value);

        return $token->value;
    }

    public function expectAnyName(string ...$names): string
    {
        $token = $this->expect(T::NAME);
        $upper = strtoupper($token->value);
        if (!in_array($upper, $names, true)) {
            $this->missingAnyKeyword(...$names);
        }

        return $token->value;
    }

    public function getName(?string $object, ?string $name = null): ?string
    {
        $position = $this->position;
        $token = $this->get(T::NAME, 0, $name);
        if ($token !== null) {
            $this->validateName($object, $token->value);

            return $token->value;
        }
        $this->position = $position;

        return null;
    }

    public function getAnyName(string ...$names): ?string
    {
        $position = $this->position;
        $token = $this->get(T::NAME);
        if ($token === null) {
            return null;
        }
        $upper = strtoupper($token->value);
        if (in_array($upper, $names, true)) {
            return $token->value;
        }
        $this->position = $position;

        return null;
    }

    /**
     * @phpstan-impure
     */
    public function hasName(string $name): bool
    {
        return $this->getName(null, $name) !== null;
    }

    // todo: probably all calls to this should call expectNonReservedName() instead
    public function getNonKeywordName(?string $object, ?string $name = null): ?string
    {
        $token = $this->get(T::NAME, T::KEYWORD, $name);
        if ($token === null) {
            return null;
        }
        $this->validateName($object, $token->value);

        return $token->value;
    }

    public function expectNonReservedName(?string $object, ?string $name = null): string
    {
        $token = $this->expect(T::NAME, T::RESERVED);
        if ($name !== null && $token->value !== $name) {
            $this->position--;

            throw InvalidTokenException::tokens(T::NAME, T::RESERVED, $name, $token, $this);
        }
        $this->validateName($object, $token->value);

        return $token->value;
    }

    public function getNonReservedName(?string $object, ?string $name = null, int $mask = 0): ?string
    {
        $token = $this->get(T::NAME, T::RESERVED | $mask, $name);
        if ($token === null) {
            return null;
        }
        $this->validateName($object, $token->value);

        return $token->value;
    }

    private function validateName(?string $object, string $name): void
    {
        if ($object !== null && $name === '') {
            throw new ParserException('Name must not be empty.', $this);
        }
        if ($object !== null) {
            if (($object === Entity::SCHEMA || $object === Entity::TABLE || $object === Entity::COLUMN) && rtrim($name) !== $name) {
                throw new ParserException(ucfirst($object) . ' name must not contain right side white space.', $this);
            }
            if (Str::length($name) > $this->maxLengths[$object]) {
                throw new ParserException(ucfirst($object) . " name must not be at most {$this->maxLengths[$object]} characters long.", $this);
            }
        }
    }

    // keywords --------------------------------------------------------------------------------------------------------

    /**
     * @return never
     * @throws InvalidTokenException
     */
    public function missingAnyKeyword(string ...$keywords): void
    {
        $token = $this->get(T::KEYWORD);

        throw InvalidTokenException::tokens(T::KEYWORD, 0, $keywords, $token, $this);
    }

    public function expectKeyword(?string $keyword = null): string
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & T::KEYWORD) === 0) {
            throw InvalidTokenException::tokens(T::KEYWORD, 0, $keyword, $token, $this);
        }
        $value = strtoupper($token->value);
        if ($keyword !== null && $value !== $keyword) {
            throw InvalidTokenException::tokens(T::KEYWORD, 0, $keyword, $token, $this);
        }
        $this->position++;

        return $value;
    }

    public function getKeyword(?string $keyword = null): ?string
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & T::KEYWORD) === 0) {
            return null;
        }
        $value = strtoupper($token->value);
        if ($keyword !== null && $value !== $keyword) {
            return null;
        }
        $this->position++;

        return $value;
    }

    /**
     * @phpstan-impure
     */
    public function hasKeyword(string $keyword): bool
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & T::KEYWORD) === 0) {
            return false;
        }
        $value = strtoupper($token->value);
        if ($value !== $keyword) {
            return false;
        }
        $this->position++;

        return true;
    }

    public function passKeyword(string $keyword): void
    {
        $this->getKeyword($keyword);
    }

    public function expectKeywords(string ...$keywords): string
    {
        foreach ($keywords as $keyword) {
            $this->expectKeyword($keyword);
        }

        return implode(' ', $keywords);
    }

    /**
     * @phpstan-impure
     */
    public function hasKeywords(string ...$keywords): bool
    {
        $position = $this->position;
        foreach ($keywords as $keyword) {
            if (!$this->hasKeyword($keyword)) {
                $this->position = $position;

                return false;
            }
        }

        return true;
    }

    public function expectAnyKeyword(string ...$keywords): string
    {
        $keyword = strtoupper($this->expect(T::KEYWORD)->value);
        if (!in_array($keyword, $keywords, true)) {
            $this->missingAnyKeyword(...$keywords);
        }

        return $keyword;
    }

    public function getAnyKeyword(string ...$keywords): ?string
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & T::KEYWORD) === 0) {
            return null;
        }
        $value = strtoupper($token->value);
        if (!in_array($value, $keywords, true)) {
            return null;
        }
        $this->position++;

        return $value;
    }

    /**
     * @phpstan-impure
     */
    public function hasAnyKeyword(string ...$keywords): bool
    {
        $position = $this->position;
        foreach ($keywords as $keyword) {
            $token = $this->getKeyword($keyword);
            if ($token !== null) {
                return true;
            }
        }
        $this->position = $position;

        return false;
    }

    // keyword enums ---------------------------------------------------------------------------------------------------

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function expectKeywordEnum(string $className): SqlEnum
    {
        /** @var string[] $values */
        $values = call_user_func([$className, 'getAllowedValues']);

        /** @var T $enum */
        $enum = call_user_func([$className, 'get'], $this->expectAnyKeyword(...array_values($values)));

        return $enum;
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T|null
     */
    public function getKeywordEnum(string $className): ?SqlEnum
    {
        /** @var string[] $values */
        $values = call_user_func([$className, 'getAllowedValues']);
        $token = $this->getAnyKeyword(...array_values($values));
        if ($token === null) {
            return null;
        }

        /** @var T $enum */
        $enum = call_user_func([$className, 'get'], $token);

        return $enum;
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function expectMultiKeywordsEnum(string $className): SqlEnum
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        $start = $this->position;
        /** @var string[] $values */
        $values = call_user_func([$className, 'getAllowedValues']);
        foreach ($values as $value) {
            $this->position = $start;
            $keywords = explode(' ', $value);
            foreach ($keywords as $keyword) {
                if (!$this->hasKeyword($keyword)) {
                    continue 2;
                }
            }

            /** @var T $enum */
            $enum = call_user_func([$className, 'get'], $value);

            return $enum;
        }
        $this->position = $start;

        throw InvalidTokenException::tokens(T::KEYWORD, 0, $values, $this->tokens[$this->position], $this);
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function getMultiKeywordsEnum(string $className): ?SqlEnum
    {
        $start = $this->position;
        /** @var string[] $values */
        $values = call_user_func([$className, 'getAllowedValues']);
        foreach ($values as $value) {
            $this->position = $start;
            $keywords = explode(' ', $value);
            foreach ($keywords as $keyword) {
                if (!$this->hasKeyword($keyword)) {
                    continue 2;
                }
            }

            /** @var T $enum */
            $enum = call_user_func([$className, 'get'], $value);

            return $enum;
        }

        return null;
    }

    // special values --------------------------------------------------------------------------------------------------

    public function expectQualifiedName(): QualifiedName
    {
        $first = $this->expectNonReservedName(Entity::SCHEMA);
        if ($this->hasSymbol('.')) {
            if ($this->hasOperator(Operator::MULTIPLY)) {
                $second = Operator::MULTIPLY;
            } else {
                $second = $this->expectName(Entity::TABLE);
            }

            return new QualifiedName($second, $first);
        }

        return new QualifiedName($first);
    }

    public function getQualifiedName(): ?QualifiedName
    {
        $position = $this->position;

        $first = $this->getNonReservedName(Entity::SCHEMA);
        if ($first === null) {
            $this->position = $position;

            return null;
        }

        if ($this->hasSymbol('.')) {
            // a reserved keyword may follow after "." unescaped as we know it is a name context
            $secondToken = $this->get(T::KEYWORD);
            if ($secondToken !== null) {
                $second = $secondToken->value;
            } else {
                $second = $this->expectName(Entity::TABLE);
            }

            return new QualifiedName($second, $first);
        }

        return new QualifiedName($first);
    }

    public function expectUserName(): UserName
    {
        $name = $this->expectNonReservedNameOrString();
        // characters, not bytes
        if (mb_strlen($name) > $this->maxLengths[Entity::USER]) {
            throw new ParserException('Too long user name.', $this);
        }
        $host = null;
        $token = $this->get(T::AT_VARIABLE);
        if ($token !== null) {
            $host = ltrim($token->value, '@');
            if (strlen($host) > $this->maxLengths[Entity::HOST]) {
                throw new ParserException('Too long host name.', $this);
            }
        }

        return new UserName($name, $host);
    }

    public function expectCharsetName(): Charset
    {
        if ($this->hasKeyword(Keyword::BINARY)) {
            return Charset::get(Charset::BINARY);
        } elseif ($this->hasKeyword(Keyword::ASCII)) {
            return Charset::get(Charset::ASCII);
        } else {
            $charset = $this->getString();
            if ($charset === null) {
                $charset = $this->expectName(null);
            }
            if (!Charset::validateValue($charset)) {
                $values = Charset::getAllowedValues();

                throw InvalidTokenException::tokens(T::STRING | T::NAME, 0, $values, $this->tokens[$this->position - 1], $this);
            }

            return Charset::get($charset);
        }
    }

    public function expectCollationName(): Collation
    {
        if ($this->hasKeyword(Keyword::BINARY)) {
            return Collation::get(Collation::BINARY);
        } else {
            return $this->expectNameOrStringEnum(Collation::class);
        }
    }

    public function getCollationName(): ?Collation
    {
        if ($this->hasKeyword(Keyword::BINARY)) {
            return Collation::get(Collation::BINARY);
        } else {
            $position = $this->position;
            $value = $this->getNonReservedNameOrString();
            if ($value === null) {
                return null;
            }

            if (!Collation::validateValue($value)) {
                $this->position = $position;

                return null;
            }

            return Collation::get($value);
        }
    }

    public function expectStorageEngineName(): StorageEngine
    {
        $value = $this->expectNonReservedNameOrString();

        return new StorageEngine($value);
    }

    // end -------------------------------------------------------------------------------------------------------------

    public function expectEnd(): void
    {
        if ($this->autoSkip !== 0) {
            $this->doAutoSkip();
        }
        // pass trailing ; when delimiter is something else
        while ($this->hasSymbol(';')) {
            if ($this->autoSkip !== 0) {
                $this->doAutoSkip();
            }
        }

        if ($this->position < count($this->tokens)) {
            throw InvalidTokenException::tokens(T::END, 0, null, $this->tokens[$this->position], $this);
        }
    }

}
