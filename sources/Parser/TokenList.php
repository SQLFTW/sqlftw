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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Expression\BinaryLiteral;
use SqlFtw\Sql\Expression\HexadecimalLiteral;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\SizeLiteral;
use SqlFtw\Sql\Expression\ValueLiteral;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlEnum;
use SqlFtw\Sql\UserName;
use function array_values;
use function call_user_func;
use function count;
use function ctype_digit;
use function explode;
use function implode;
use function in_array;
use function is_bool;
use function is_float;
use function is_string;
use function ltrim;
use function preg_match;
use function trim;

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

    /** @var PlatformSettings */
    private $settings;

    /** @var Platform */
    private $platform;

    /** @var bool */
    private $whitespace;

    /** @var int */
    private $autoSkip = 0;

    /** @var int */
    private $position = 0;

    /**
     * @param non-empty-array<Token> $tokens
     */
    public function __construct(array $tokens, PlatformSettings $settings, bool $whitespace = true)
    {
        $this->tokens = $tokens;
        $this->settings = $settings;
        $this->platform = $settings->getPlatform();
        $this->whitespace = $whitespace;
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
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

    public function isFinished(): bool
    {
        $this->doAutoSkip();

        return $this->position >= count($this->tokens);
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function resetPosition(int $position = 0): self
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

    public function setAutoSkip(int $tokenType): void
    {
        $this->autoSkip = $tokenType;
    }

    private function doAutoSkip(): void
    {
        $token = $this->tokens[$this->position] ?? null;
        while ($token !== null && ($this->autoSkip & $token->type) !== 0) {
            $this->position++;
            $token = $this->tokens[$this->position] ?? null;
        }
    }

    /**
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this->tokens;
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
            if (($token->type & (TokenType::WHITESPACE | TokenType::COMMENT | TokenType::TEST_CODE)) === 0) {
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
            if (!$this->whitespace) {
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
            $this->doAutoSkip();
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
            $this->doAutoSkip();
            $token = $this->tokens[$this->position] ?? null;
            if ($token === null) {
                break;
            }
            $this->position++;
            if (($token->type & TokenType::KEYWORD) !== 0 && $token->value === $keyword) {
                $this->position = $position;

                return true;
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

    /**
     * @param mixed|null $value
     */
    public function expect(int $tokenType, $value = null): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & $tokenType) === 0) {
            throw InvalidTokenException::tokens([$tokenType], $value, $token, $this);
        }
        if ($value !== null && $token->value !== $value) {
            throw InvalidTokenException::tokens([$tokenType], $value, $token, $this);
        }
        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     * @param mixed $value
     */
    public function get(?int $tokenType = null, $value = null): ?Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null) {
            return null;
        }
        if ($tokenType !== null && ($token->type & $tokenType) === 0) {
            return null;
        }
        if ($value !== null && $token->value !== $value) {
            return null;
        }

        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     * @param mixed $value
     */
    public function has(int $tokenType, $value = null): bool
    {
        return (bool) $this->get($tokenType, $value);
    }

    /**
     * @param mixed $value
     */
    public function pass(int $tokenType, $value = null): void
    {
        $this->has($tokenType, $value);
    }

    public function expectAny(int ...$tokenTypes): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null) {
            foreach ($tokenTypes as $tokenType) {
                if (($token->type & $tokenType) !== 0) {
                    $this->position++;

                    return $token;
                }
            }
        }

        throw InvalidTokenException::tokens($tokenTypes, null, $token, $this);
    }

    // symbols ---------------------------------------------------------------------------------------------------------

    public function expectSymbol(string $symbol): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & TokenType::SYMBOL) === 0) {
            throw InvalidTokenException::tokens([TokenType::SYMBOL], $symbol, $token, $this);
        }
        if ($token->value !== $symbol) {
            throw InvalidTokenException::tokens([TokenType::SYMBOL], $symbol, $token, $this);
        }
        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     */
    public function hasSymbol(string $symbol): bool
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null && ($token->type & TokenType::SYMBOL) !== 0 && $token->value === $symbol) {
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
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null && ($token->type & TokenType::SYMBOL) !== 0 && $token->value === $symbol) {
            $this->position++;
        }
    }

    // operators -------------------------------------------------------------------------------------------------------

    public function expectOperator(string $operator): string
    {
        /** @var string $value */
        $value = $this->expect(TokenType::OPERATOR, $operator)->value;

        return $value;
    }

    /**
     * @phpstan-impure
     */
    public function hasOperator(string $operator): bool
    {
        $position = $this->position;

        $token = $this->get(TokenType::OPERATOR);
        if ($token === null) {
            return false;
        } elseif ($token->value === $operator) {
            return true;
        } else {
            $this->position = $position;

            return false;
        }
    }

    public function expectAnyOperator(string ...$operators): string
    {
        $operator = $this->expect(TokenType::OPERATOR);
        if (!in_array($operator->value, $operators, true)) {
            throw InvalidTokenException::tokens([TokenType::OPERATOR], $operators, $operator, $this);
        }

        return $operator->value;
    }

    public function getAnyOperator(string ...$operators): ?string
    {
        $position = $this->position;

        $operator = $this->get(TokenType::OPERATOR);
        if ($operator === null || !in_array($operator->value, $operators, true)) {
            $this->position = $position;

            return null;
        }

        return $operator->value;
    }

    // numbers ---------------------------------------------------------------------------------------------------------

    public function expectUnsignedInt(): int
    {
        /** @var int $int */
        $int = $this->expect(TokenType::UINT)->value;

        return $int;
    }

    public function getUnsignedInt(): ?int
    {
        $token = $this->get(TokenType::UINT);
        if ($token === null) {
            return null;
        }

        return (int) $token->value;
    }

    public function expectInt(): int
    {
        /** @var int $int */
        $int = $this->expect(TokenType::INT)->value;

        return $int;
    }

    public function expectIntLike(): Literal
    {
        $number = $this->expect(TokenType::NUMBER | TokenType::STRING | TokenType::HEXADECIMAL_LITERAL | TokenType::BINARY_LITERAL);
        $value = $number->value;
        if (is_float($value)) {
            throw new InvalidValueException('integer', $this);
        } elseif (($number->type & TokenType::STRING) !== 0 && is_string($value) && !ctype_digit($value)) {
            throw new InvalidValueException('integer', $this);
        }
        if (($number->type & TokenType::HEXADECIMAL_LITERAL) !== 0) {
            return new HexadecimalLiteral((string) $value);
        } elseif (($number->type & TokenType::BINARY_LITERAL) !== 0) {
            return new BinaryLiteral((string) $value);
        }

        return new ValueLiteral($value);
    }

    public function expectSize(): SizeLiteral
    {
        $token = $this->expect(TokenType::UINT | TokenType::NAME);
        if (($token->type & TokenType::UINT) !== 0) {
            return new SizeLiteral((string) $token->value);
        }

        $value = (string) $token->value;
        if (preg_match(SizeLiteral::REGEXP, $value) === 0) {
            throw new InvalidValueException('size', $this);
        }

        return new SizeLiteral($value);
    }

    public function expectBool(): bool
    {
        // TRUE, FALSE, ON, OFF, 1, 0, Y, N, T, F
        $value = $this->expect(TokenType::VALUE)->value;
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === 'Y' || $value === 'T' || $value === 'y' || $value === 't') {
            return true;
        } elseif ($value === 0 || $value === 'N' || $value === 'F' || $value === 'n' || $value === 'f' || $value === '') {
            return false;
        }

        throw new InvalidValueException("boolean", $this);
    }

    // strings ---------------------------------------------------------------------------------------------------------

    public function expectString(): string
    {
        /** @var string $value */
        $value = $this->expect(TokenType::STRING)->value;

        return $value;
    }

    public function getString(): ?string
    {
        $token = $this->get(TokenType::STRING);

        /** @var string|null $value */
        $value = $token !== null ? $token->value : null;

        return $value;
    }

    public function expectStringLike(): Literal
    {
        $token = $this->expect(TokenType::STRING | TokenType::HEXADECIMAL_LITERAL);
        $value = $token->value;
        if (($token->type & TokenType::HEXADECIMAL_LITERAL) !== 0) {
            return new HexadecimalLiteral((string) $value);
        }

        return new ValueLiteral($value);
    }

    public function expectNameOrString(): string
    {
        $token = $this->expectAny(TokenType::NAME, TokenType::STRING);
        /** @var string $value */
        $value = ($token->type & TokenType::KEYWORD) !== 0
            ? $token->original ?? $token->value // NAME|KEYWORD is automatically upper-cased
            : $token->value;

        return $value;
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function expectNameOrStringEnum(string $className): SqlEnum
    {
        $value = $this->expectNameOrString();

        try {
            return call_user_func([$className, 'get'], $value);
        } catch (InvalidEnumValueException $e) {
            $values = call_user_func([$className, 'getAllowedValues']);

            throw InvalidTokenException::tokens([TokenType::NAME], $values, $this->tokens[$this->position - 1], $this);
        }
    }

    // names ---------------------------------------------------------------------------------------------------------

    public function expectName(?string $name = null): string
    {
        $token = $this->expect(TokenType::NAME, $name);

        return $token->original ?? (string) $token->value;
    }

    public function expectAnyName(string ...$names): string
    {
        $name = strtoupper($this->expect(TokenType::NAME)->value);
        if (!in_array($name, $names, true)) {
            $this->missingAnyKeyword(...$names);
        }

        return $name;
    }

    public function getName(?string $name = null): ?string
    {
        $token = $this->get(TokenType::NAME, $name);
        if ($token !== null) {
            return $token->value; // @phpstan-ignore-line string!
        }

        return null;
    }

    public function expectNonKeywordName(?string $name = null): string
    {
        $token = $this->expect(TokenType::NAME, $name);
        if (($token->type & TokenType::KEYWORD) !== 0) {
            throw InvalidTokenException::tokens([TokenType::NAME], null, $token, $this);
        }

        return $token->original ?? (string) $token->value;
    }

    public function getNonKeywordName(?string $name = null): ?string
    {
        $position = $this->position;
        $token = $this->get(TokenType::NAME, $name);
        if ($token === null) {
            return null;
        } elseif (($token->type & TokenType::KEYWORD) !== 0) {
            $this->position = $position;

            return null;
        }

        return $token->value; // @phpstan-ignore-line string
    }

    public function hasNameOrKeyword(string $name): bool
    {
        return $this->hasKeyword($name) || (bool) $this->getName($name);
    }

    // keywords --------------------------------------------------------------------------------------------------------

    /**
     * @return never
     * @throws InvalidTokenException
     */
    public function missingAnyKeyword(string ...$keywords): void
    {
        $this->position--;
        $token = $this->get(TokenType::KEYWORD);

        throw InvalidTokenException::tokens([TokenType::KEYWORD], $keywords, $token, $this);
    }

    public function expectKeyword(string $keyword): string
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & TokenType::KEYWORD) === 0) {
            throw InvalidTokenException::tokens([TokenType::KEYWORD], $keyword, $token, $this);
        }
        if ($token->value !== $keyword) {
            throw InvalidTokenException::tokens([TokenType::KEYWORD], $keyword, $token, $this);
        }
        $this->position++;

        return $token->value;
    }

    private function getKeyword(string $keyword): ?Token
    {
        $position = $this->position;

        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || ($token->type & TokenType::KEYWORD) === 0) {
            $this->position = $position;

            return null;
        }
        if ($token->value !== $keyword) {
            $this->position = $position;

            return null;
        }
        $this->position++;

        return $token;
    }

    public function hasKeyword(string $keyword): bool
    {
        return (bool) $this->getKeyword($keyword);
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

    public function hasKeywords(string ...$keywords): bool
    {
        $position = $this->position;
        foreach ($keywords as $keyword) {
            if (!$this->hasAnyKeyword($keyword)) {
                $this->position = $position;

                return false;
            }
        }

        return true;
    }

    public function expectAnyKeyword(string ...$keywords): string
    {
        $keyword = $this->expect(TokenType::KEYWORD)->value;
        if (!in_array($keyword, $keywords, true)) {
            $this->missingAnyKeyword(...$keywords);
        }

        return $keyword;
    }

    public function getAnyKeyword(string ...$keywords): ?string
    {
        $position = $this->position;
        foreach ($keywords as $keyword) {
            $token = $this->getKeyword($keyword);
            if ($token !== null) {
                return $token->value; // @phpstan-ignore-line string
            }
        }
        $this->position = $position;

        return null;
    }

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
        return call_user_func([$className, 'get'], $this->expectAnyKeyword(...array_values(call_user_func([$className, 'getAllowedValues']))));
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T|null
     */
    public function getKeywordEnum(string $className): ?SqlEnum
    {
        $token = $this->getAnyKeyword(...array_values(call_user_func([$className, 'getAllowedValues'])));
        if ($token === null) {
            return null;
        } else {
            return call_user_func([$className, 'get'], $token);
        }
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function expectMultiKeywordsEnum(string $className): SqlEnum
    {
        $this->doAutoSkip();
        $start = $this->position;
        $values = call_user_func([$className, 'getAllowedValues']);
        foreach ($values as $value) {
            $this->position = $start;
            $keywords = explode(' ', $value);
            foreach ($keywords as $keyword) {
                if (!$this->hasKeyword($keyword)) {
                    continue 2;
                }
            }

            return call_user_func([$className, 'get'], $value);
        }

        throw InvalidTokenException::tokens([TokenType::KEYWORD], $values, $this->tokens[$this->position], $this);
    }

    /**
     * @template T of SqlEnum
     * @param class-string<T> $className
     * @return T
     */
    public function getMultiKeywordsEnum(string $className): ?SqlEnum
    {
        $start = $this->position;
        $values = call_user_func([$className, 'getAllowedValues']);
        foreach ($values as $value) {
            $this->position = $start;
            $keywords = explode(' ', $value);
            foreach ($keywords as $keyword) {
                if (!$this->hasKeyword($keyword)) {
                    continue 2;
                }
            }

            return call_user_func([$className, 'get'], $value);
        }

        return null;
    }

    // special values --------------------------------------------------------------------------------------------------

    public function expectQualifiedName(): QualifiedName
    {
        $first = $this->expectName();
        if ($this->hasSymbol('.')) {
            // a reserved keyword may follow after "." unescaped as we know it is a name context
            $keyword = $this->get(TokenType::KEYWORD);
            if ($keyword !== null) {
                /** @var string $second */
                $second = $keyword->value;
            } elseif ($this->hasOperator(Operator::MULTIPLY)) {
                $second = Operator::MULTIPLY;
            } else {
                $second = $this->expectName();
            }

            return new QualifiedName($second, $first);
        }

        return new QualifiedName($first);
    }

    public function getQualifiedName(): ?QualifiedName
    {
        $position = $this->position;

        $first = $this->getName();
        if ($first === null) {
            $this->position = $position;

            return null;
        }

        if ($this->hasSymbol('.')) {
            // a reserved keyword may follow after "." unescaped as we know it is a name context
            $secondToken = $this->get(TokenType::KEYWORD);
            if ($secondToken !== null) {
                /** @var string $second */
                $second = $secondToken->value;
            } else {
                $second = $this->expectName();
            }

            return new QualifiedName($second, $first);
        }

        return new QualifiedName($first);
    }

    public function expectUserName(): UserName
    {
        $name = $this->expectNameOrString();
        $token = $this->get(TokenType::AT_VARIABLE);
        if ($token !== null) {
            /** @var string $host */
            $host = $token->value;
            $token = ltrim($host, '@');
        }

        return new UserName($name, $token);
    }

    public function expectCharsetName(): Charset
    {
        if ($this->hasKeyword(Keyword::BINARY)) {
            return Charset::get(Charset::BINARY);
        } else {
            return $this->expectNameOrStringEnum(Charset::class);
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

    // end -------------------------------------------------------------------------------------------------------------

    public function expectEnd(): void
    {
        $this->doAutoSkip();
        // pass trailing ; when delimiter is something else
        while ($this->hasSymbol(';')) {
            $this->doAutoSkip();
        }

        if ($this->position < count($this->tokens)) {
            throw InvalidTokenException::tokens([TokenType::END], null, $this->tokens[$this->position], $this);
        }
    }

}
