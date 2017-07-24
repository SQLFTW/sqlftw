<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Time\DateTime;
use SqlFtw\Platform\Settings;
use SqlFtw\Sql\SqlEnum;

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
 * - expectedFoo() - always throw an exception
 */
class TokenList
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Token[] */
    private $tokens;

    /** @var \SqlFtw\Platform\Settings */
    private $settings;

    /** @var int[] */
    private $autoSkip;

    /** @var int */
    private $position = 0;

    /**
     * @param \SqlFtw\Parser\Token[] $tokens
     */
    public function __construct(array $tokens, Settings $settings)
    {
        $this->tokens = $tokens;
        $this->settings = $settings;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function isFinished(): bool
    {
        return $this->position === count($this->tokens) - 1;
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
        $token = $this->get();
        while (in_array($token->type, $this->autoSkip)) {
            $this->position++;
            $token = $this->get();
        }
    }

    private function get(int $offset = 0): Token
    {
        return $this->tokens[$this->position + $offset];
    }

    public function getNext(int &$position): Token
    {
        ///
    }

    /**
     * @param int $tokenType
     * @param mixed|null $value
     * @return \SqlFtw\Parser\Token
     */
    public function consume(int $tokenType, $value = null): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[++$this->position];
        if (!($token->type & $tokenType)) {
            throw new \SqlFtw\Parser\UnexpectedTokenException([$tokenType], $token->type);
        }
        if ($value !== null && $token->value !== $value) {
            throw new \SqlFtw\Parser\UnexpectedTokenValueException($tokenType, [$value], $token->value);
        }
        return $token;
    }

    /**
     * @param int $tokenType
     * @param string|int|float|bool|null $value
     * @return \SqlFtw\Parser\Token|null
     */
    public function mayConsume(int $tokenType, $value = null): ?Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position + 1];
        if ($token->type & $tokenType) {
            $this->position++;
            return $token;
        } else {
            return null;
        }
    }

    public function consumeAny(int ...$tokenTypes): Token
    {
        $this->doAutoSkip();
        $token = $this->tokens[++$this->position];
        foreach ($tokenTypes as $tokenType) {
            if ($token->type & $tokenType) {
                return $token;
            }
        }

        throw new \SqlFtw\Parser\UnexpectedTokenException($tokenTypes, $token->type);
    }

    public function mayConsumeComma(): bool
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position + 1];
        if ($token->type & TokenType::COMMA) {
            $this->position++;
            return true;
        } else {
            return false;
        }
    }

    public function consumeName(?string $name = null): string
    {
        return $this->consume(TokenType::NAME, $name)->value;
    }

    public function mayConsumeName(): string
    {
        $token = $this->mayConsume(TokenType::NAME);

        return $token !== null ? $token->value : null;
    }

    public function consumeString(): string
    {
        return $this->consume(TokenType::STRING)->value;
    }

    public function mayConsumeString(): string
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
        ///
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
        $number = $this->consume(TokenType::NUMBER)->value;
        if (!is_int($number)) {
            ///
        }
        return $number;
    }

    public function mayConsumeInt(): ?int
    {
        ///
    }

    public function consumeBool(): bool
    {
        /// TRUE, FALSE, ON, OFF, 1, 0, Y, N, T, F
        return false;
    }

    public function consumeDateTime(): DateTime
    {
        ///
        return new DateTime();
    }

    public function consumeOperator(?string $operator = null): string
    {
        return $this->consume(TokenType::OPERATOR, $operator)->value;
    }

    public function mayConsumeOperator(?string $operator = null): ?string
    {
        $token = $this->mayConsume(TokenType::OPERATOR, $operator);

        return $token ? $token->value : null;
    }

    /**
     * @param string ...$operators
     * @return string
     */
    public function consumeAnyOperator(...$operators): string
    {
        ///
    }

    /**
     * @param string ...$operators
     * @return string|null
     */
    public function mayConsumeAnyOperator(...$operators): ?string
    {
        ///
    }

    public function consumeKeyword(string $keyword): string
    {
        $this->doAutoSkip();
        $token = $this->tokens[$this->position++];
        if ($token->type & TokenType::KEYWORD) {
            throw new \SqlFtw\Parser\UnexpectedTokenException(TokenType::KEYWORD, $token->type);
        }
        if ($token->value !== $keyword) {
            throw new \SqlFtw\Parser\UnexpectedKeywordException($keyword, $token->value);
        }

        return $token->value;
    }

    public function mayConsumeKeyword(string $keyword): ?string
    {
        ///
        return '';
    }

    /**
     * Returns keywords concatenated with ' '
     */
    public function consumeKeywords(string ...$keywords): string
    {
        ///
        return '';
    }

    public function mayConsumeKeywords(string ...$keywords): ?string
    {
        ///
        return '';
    }

    public function consumeAnyKeyword(string ...$keywords): string
    {
        ///
        return '';
    }

    public function mayConsumeAnyKeyword(string ...$keywords): ?string
    {
        ///
        return '';
    }

    public function consumeEnum(string $className): SqlEnum
    {
        return call_user_func([$className, 'get'], $this->consumeAnyKeyword(call_user_func($className, 'getAvailableValues')));
    }

    public function mayConsumeEnum(string $className): ?SqlEnum
    {
        $token = $this->mayConsumeAnyKeyword(call_user_func($className, 'getAvailableValues'));
        if ($token === null) {
            return null;
        } else {
            return call_user_func([$className, 'get'], $token);
        }
    }

    public function seek(int $type, int $maxOffset): Token
    {
        ///
        return new Token(1, '');
    }

    public function seekKeyword(string $keyword, int $maxOffset): string
    {
        ///
        return '';
    }

    /**
     * @param string[] $keywords
     * @param int $maxOffset
     * @return string
     */
    public function seekAnyKeyword(array $keywords, int $maxOffset): string
    {
        ///
        return '';
    }

    public function mayConsumeAtVariable(): ?string
    {
        ///
        return null;
    }

    /**
     * @return string[]|null[] (string $name, string|null $database)
     */
    public function consumeQualifiedName(): array
    {
        ///
        return [];
    }

    /**
     * @return string[]|null[]|null (string|null $database, string $name)
     */
    public function mayConsumeQualifiedName(): ?array
    {
        ///
        return [];
    }

    /**
     * @return string[]|null[]|null (string|null $database, string|null $tableOrAlias, string $name)
     */
    public function consumeColumnName(): array
    {
        ///
        return [];
    }

    /**
     * @return string[]|null[] (string $name, string|null $host)
     */
    public function consumeUserName(): array
    {
        ///
        return [];
    }

    public function expectEnd(): void
    {
        ///
        $this->finished = true;
    }

    /**
     * @throws \SqlFtw\Parser\UnexpectedTokenException
     */
    public function unexpected(): void
    {
        /// fail
    }

    /**
     * @param string $description
     * @throws \Exception
     */
    public function expected(string $description): void
    {
        /// fail
    }

    /**
     * @param $value
     * @throws \SqlFtw\Parser\UnexpectedTokenValueException
     */
    public function expectedValue($value): void
    {
        /// fail
    }

    /**
     * @param string[] ...$keywords
     * @throws \SqlFtw\Parser\UnexpectedKeywordException
     */
    public function expectedAnyKeyword(string ...$keywords): void
    {
        /// fail
    }

}
