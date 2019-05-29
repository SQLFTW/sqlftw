<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\SqlEnum;
use function array_values;
use function call_user_func;
use function count;
use function implode;
use function in_array;
use function is_bool;
use function is_int;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Holds list of lexer tokens and a pointer to current token
 *
 * Method names explanation:
 * - consumeFoo() - has to consume given object or throw an exception
 * - consumeFoos() - has to consume all given objects at once or throw an exception. partial match is not an option
 * - consumeAnyFoo() - has to consume any of given objects or throw an exception
 * - mayConsumeFoo() - has to consume given object or return null without moving pointer
 * - mayConsumeFoos() - has to consume all given objects at once or return null without moving pointer. partial match is not an option
 * - mayConsumeAnyFoo - has to consume any of given objects ot return null without moving pointer
 * - seekFoo() - seeks given object between following tokens without moving pointer
 * - expectFoo() - throws an exception when expectation is not fulfilled
 * - expectedFoo() - always throws an exception
 */
class TokenList
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Token[] */
    private $tokens;

    /** @var \SqlFtw\Platform\PlatformSettings */
    private $settings;

    /** @var bool */
    private $whitespace;

    /** @var int */
    private $autoSkip;

    /** @var int */
    private $position = 0;

    /**
     * @param \SqlFtw\Parser\Token[] $tokens
     * @param \SqlFtw\Platform\PlatformSettings $settings
     * @param bool $whitespace
     */
    public function __construct(array $tokens, PlatformSettings $settings, bool $whitespace = true)
    {
        $this->tokens = $tokens;
        $this->settings = $settings;
        $this->whitespace = $whitespace;
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
    }

    public function isFinished(): bool
    {
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
        } else {
            $this->position = $position;
        }

        return $this;
    }

    public function addAutoSkip(TokenType $tokenType): void
    {
        $this->autoSkip |= $tokenType->getValue();
    }

    private function doAutoSkip(): void
    {
        $token = $this->tokens[$this->position] ?? null;
        while ($token !== null && ($this->autoSkip & $token->type)) {
            $this->position++;
            $token = $this->tokens[$this->position] ?? null;
        }
    }

    /**
     * @param int $position
     * @param int $count
     * @return \SqlFtw\Parser\Token[]
     */
    public function getTokens(int $position, int $count): array
    {
        $tokens = [];
        for ($n = 0; $n < $count; $n++) {
            if (isset($this->tokens[$position + $n])) {
                $tokens[] = $this->tokens[$position + $n];
            }
        }
        return $tokens;
    }

    /**
     * @param int $tokenType
     * @param mixed|null $value
     * @return \SqlFtw\Parser\Token
     */
    public function consume(int $tokenType, $value = null): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || !($token->type & $tokenType)) {
            throw new UnexpectedTokenException([$tokenType], $value, $token, $this);
        }
        if ($value !== null && $token->value !== $value) {
            throw new UnexpectedTokenException([$tokenType], $value, $token, $this);
        }
        $this->position++;

        return $token;
    }

    /**
     * @param int $tokenType
     * @return \SqlFtw\Parser\Token|null
     */
    public function mayConsume(int $tokenType): ?Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null && ($token->type & $tokenType)) {
            $this->position++;
            return $token;
        } else {
            return null;
        }
    }

    public function consumeAny(int ...$tokenTypes): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null) {
            foreach ($tokenTypes as $tokenType) {
                if ($token->type & $tokenType) {
                    $this->position++;

                    return $token;
                }
            }
        }

        throw new UnexpectedTokenException($tokenTypes, null, $token, $this);
    }

    public function mayConsumeComma(): bool
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token !== null && ($token->type & TokenType::COMMA)) {
            $this->position++;

            return true;
        } else {
            return false;
        }
    }

    public function consumeName(?string $name = null): string
    {
        return $this->consume(TokenType::NAME, $name)->original;
    }

    public function mayConsumeName(): ?string
    {
        $token = $this->mayConsume(TokenType::NAME);

        return $token !== null ? $token->original : null;
    }

    public function consumeString(): string
    {
        /** @var string $value */
        $value = $this->consume(TokenType::STRING)->value;

        return $value;
    }

    public function mayConsumeString(): ?string
    {
        $token = $this->mayConsume(TokenType::STRING);

        return $token !== null ? $token->value : null;
    }

    public function consumeNameOrString(): string
    {
        return $this->consumeAny(TokenType::NAME, TokenType::STRING)->value;
    }

    public function mayConsumeNameOrKeyword(string $name): ?string
    {
        $result = $this->mayConsumeKeyword($name);
        if ($result !== null) {
            return $result;
        }
        return $this->mayConsumeName();
    }

    /**
     * @return int|float|string
     */
    public function consumeNumber()
    {
        return $this->consume(TokenType::NUMBER)->value;
    }

    /**
     * @return int|float|string|null
     */
    public function mayConsumeNumber()
    {
        $token = $this->mayConsume(TokenType::NUMBER);

        return $token !== null ? $token->value : null;
    }

    public function consumeInt(): int
    {
        $number = $this->mayConsume(TokenType::NUMBER);
        if ($number !== null) {
            if (is_int($number->value)) {
                return $number->value;
            } else {
                throw new UnexpectedTokenException([TokenType::NUMBER], 'integer', $number, $this);
            }
        }
        $number = $this->mayConsumeString();
        if ($number !== null && preg_match('/^[0-9]+$/', $number)) {
            return (int) $number;
        }
        $this->consume(TokenType::NUMBER);
        exit;
    }

    public function mayConsumeInt(): ?int
    {
        $position = $this->position;
        try {
            return $this->consumeInt();
        } catch (UnexpectedTokenException $e) {
            $this->position = $position;
            return null;
        }
    }

    public function consumeBool(): bool
    {
        // TRUE, FALSE, ON, OFF, 1, 0, Y, N, T, F
        $value = $this->consume(TokenType::VALUE)->value;
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === 'Y' || $value === 'T') {
            return true;
        } elseif ($value === 0 || $value === 'N' || $value === 'F') {
            return false;
        }
        throw new ParserException(sprintf('Boolean-like value expected. "%s" found.', $value));
    }

    public function consumeOperator(string $operator): string
    {
        return $this->consume(TokenType::OPERATOR, $operator)->value;
    }

    public function mayConsumeOperator(string $operator): ?string
    {
        $token = $this->mayConsume(TokenType::OPERATOR);
        if ($token === null) {
            return null;
        }

        return $token->value === $operator ? $token->value : null;
    }

    /**
     * @param string ...$operators
     * @return string
     */
    public function consumeAnyOperator(string ...$operators): string
    {
        $operator = $this->consume(TokenType::OPERATOR);
        if (!in_array($operator, $operators)) {
            throw new UnexpectedTokenException([TokenType::OPERATOR], $operators, $operator, $this);
        }
        return $operator->value;
    }

    /**
     * @param string ...$operators
     * @return string|null
     */
    public function mayConsumeAnyOperator(string ...$operators): ?string
    {
        try {
            return $this->consumeAnyOperator(...$operators);
        } catch (UnexpectedTokenException $e) {
            return null;
        }
    }

    public function consumeKeyword(string $keyword): string
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position] ?? null;
        if ($token === null || !($token->type & TokenType::KEYWORD)) {
            throw new UnexpectedTokenException([TokenType::KEYWORD], $keyword, $token, $this);
        }
        if ($token->value !== $keyword) {
            throw new UnexpectedTokenException([TokenType::KEYWORD], $keyword, $token, $this);
        }
        $this->position++;

        return $token->value;
    }

    public function mayConsumeKeyword(string $keyword): ?string
    {
        try {
            $keyword = $this->consumeKeyword($keyword);
            return $keyword;
        } catch (UnexpectedTokenException $e) {
            return null;
        }
    }

    public function consumeKeywords(string ...$keywords): string
    {
        foreach ($keywords as $keyword) {
            $this->consumeKeyword($keyword);
        }
        return implode(' ', $keywords);
    }

    public function mayConsumeKeywords(string ...$keywords): ?string
    {
        $position = $this->position;
        try {
            return $this->consumeKeywords(...$keywords);
        } catch (UnexpectedTokenException $e) {
            $this->position = $position;
            return null;
        }
    }

    public function consumeAnyKeyword(string ...$keywords): string
    {
        $keyword = $this->consume(TokenType::KEYWORD)->value;
        if (!in_array($keyword, $keywords)) {
            $this->expectedAnyKeyword(...$keywords);
        }
        return $keyword;
    }

    public function mayConsumeAnyKeyword(string ...$keywords): ?string
    {
        $position = $this->position;
        try {
            return $this->consumeAnyKeyword(...$keywords);
        } catch (UnexpectedTokenException $e) {
            $this->position = $position;
            return null;
        }
    }

    public function consumeKeywordEnum(string $className): SqlEnum
    {
        return call_user_func([$className, 'get'], $this->consumeAnyKeyword(...array_values(call_user_func([$className, 'getAllowedValues']))));
    }

    public function consumeNameOrStringEnum(string $className): SqlEnum
    {
        $values = call_user_func([$className, 'getAllowedValues']);
        $value = $this->consumeNameOrString();
        if (in_array($value, $values)) {
            return call_user_func([$className, 'get'], $value);
        }
        throw new UnexpectedTokenException([TokenType::NAME], $values, $this->tokens[$this->position], $this);
    }

    public function mayConsumeKeywordEnum(string $className): ?SqlEnum
    {
        $token = $this->mayConsumeAnyKeyword(...array_values(call_user_func([$className, 'getAllowedValues'])));
        if ($token === null) {
            return null;
        } else {
            return call_user_func([$className, 'get'], $token);
        }
    }

    public function seek(int $type, int $maxOffset): ?Token
    {
        // todo: seek()
        throw new NotImplementedException('seek');
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
            if (($token->type & TokenType::KEYWORD) && $token->value === $keyword) {
                $this->position = $position;
                return true;
            }
        }
        $this->position = $position;

        return false;
    }

    /**
     * @return string[]|null[] (string $name, string|null $database)
     */
    public function consumeQualifiedName(): array
    {
        $first = $this->consumeName();
        if ($this->mayConsume(TokenType::DOT)) {
            // a reserved keyword may follow after "." unescaped as we know it is a name context
            $second = $this->mayConsume(TokenType::KEYWORD);
            if ($second !== null) {
                $second = $second->value;
            } else {
                $second = $this->mayConsumeName();
            }

            return [$second, $first];
        }
        return [$first, null];
    }

    /**
     * @return string[]|null[]|null (string|null $database, string $name)
     */
    public function mayConsumeQualifiedName(): ?array
    {
        try {
            return $this->consumeQualifiedName();
        } catch (ParserException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null[] (string $name, string|null $host)
     */
    public function consumeUserName(): array
    {
        $name = $this->consumeString();
        $symbol = $this->consume(TokenType::SYMBOL);
        if ($symbol->value !== '@') {
            $this->expected('@');
        }
        $host = $this->consumeString();

        return [$name, $host];
    }

    public function expectEnd(): void
    {
        // todo: expectEnd()
        throw new NotImplementedException('expectEnd');
    }

    public function expected(string $description): void
    {
        throw new ParserException($description);
    }

    /**
     * @param string ...$keywords
     * @throws \SqlFtw\Parser\UnexpectedTokenException
     */
    public function expectedAnyKeyword(string ...$keywords): void
    {
        $this->position--;
        $token = $this->mayConsume(TokenType::KEYWORD);

        throw new UnexpectedTokenException([TokenType::KEYWORD], $keywords, $token, $this);
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

}
