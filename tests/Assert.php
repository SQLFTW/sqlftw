<?php

namespace SqlFtw\Tests;

use Dogma\Debug\Callstack;
use Dogma\Debug\Debugger;
use Dogma\Re;
use Dogma\Tester\Assert as DogmaAssert;
use SqlFtw\Error\Error;
use SqlFtw\Error\Severity;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\LexerException;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use function array_merge;
use function class_exists;
use function gettype;
use function implode;
use function iterator_to_array;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * @phpstan-import-type PhpBacktraceItem from Callstack
 */
class Assert extends DogmaAssert
{

    /**
     * @return array<Token>
     */
    public static function tokens(string $sql, int $count, ?int $mode = null, ?int $extensions = null): array
    {
        $extensions ??= ClientSideExtension::ALLOW_DELIMITER_DEFINITION
            | ClientSideExtension::ALLOW_NUMBERED_QUESTION_MARK_PLACEHOLDERS
            | ClientSideExtension::ALLOW_NAMED_DOUBLE_COLON_PLACEHOLDERS;

        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL, $extensions, $mode);

        /** @var array<TokenList> $tokenLists */
        $tokenLists = iterator_to_array($suite->lexer->tokenize($sql));
        $tokens = [];
        foreach ($tokenLists as $tokenList) {
            $tokens = array_merge($tokens, $tokenList->getTokens());
        }

        self::count($tokens, $count);

        return $tokens;
    }

    public static function token(Token $token, int $type, ?string $value = null, ?int $position = null): void
    {
        if ($type !== $token->type) {
            $actualDesc = implode('|', TokenType::getByValue($token->type)->getConstantNames());
            $typeDesc = implode('|', TokenType::getByValue($type)->getConstantNames());
            parent::fail(sprintf('Type of token "%s" is %s (%d) and should be %s (%d).', $token->value, $actualDesc, $token->type, $typeDesc, $type));
        }
        if ($value !== $token->value) {
            parent::fail(sprintf('Token value is "%s" (%s) and should be "%s" (%s).', $token->value, gettype($token->value), $value, gettype($value)));
        }
        if ($position !== null && $position !== $token->start) {
            parent::fail(sprintf('Token starting position is %s and should be %s.', $token->start, $position));
        }
    }

    public static function invalidToken(Token $token, int $type, string $messageRegexp, ?int $position = null): void
    {
        if ($type !== $token->type) {
            $actualDesc = implode('|', TokenType::getByValue($token->type)->getConstantNames());
            $typeDesc = implode('|', TokenType::getByValue($type)->getConstantNames());
            parent::fail(sprintf('Type of token "%s" is %s (%d) and should be %s (%d).', $token->value, $actualDesc, $token->type, $typeDesc, $type));
        }
        if ($token->error === null || $token->error->severity === Severity::LEXER_ERROR) {
            parent::fail(sprintf('Token value is %s (%d) and should be a lexer error.', $token->value, gettype($token->value)));
        } else {
            $message = $token->error->message;
            if (Re::match($message, $messageRegexp) === null) {
                parent::fail(sprintf('Token exception message is "%s" and should match "%s".', $message, $messageRegexp));
            }
        }
        if ($position !== null && $position !== $token->start) {
            parent::fail(sprintf('Token starting position is %s and should be %s.', $token->start, $position));
        }
    }

    public static function tokenList(string $sql): TokenList
    {
        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

        return iterator_to_array($suite->lexer->tokenize($sql))[0];
    }

    public static function parseSerializeMany(string $query, ?string $expected = null): void
    {
        /** @var string $query */
        $query = preg_replace('/\\s+/', ' ', $query);
        $query = str_replace(['( ', ' )'], ['(', ')'], $query);

        if ($expected !== null) {
            /** @var string $expected */
            $expected = preg_replace('/\\s+/', ' ', $expected);
            $expected = str_replace(['( ', ' )'], ['(', ')'], $expected);
        } else {
            $expected = $query;
        }

        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

        $results = iterator_to_array($suite->parser->parse($query));

        $serialized = [];
        foreach ($results as $command) {
            if ($command instanceof InvalidCommand) {
                if (class_exists(Debugger::class)) {
                    Debugger::dump($command->getTokenList());
                }

                $message = Error::summarize($command->getErrors());

                self::fail($message);
                break;
            }
            $serialized[] = $suite->formatter->serialize($command);
        }
        $actual = implode("\n", $serialized);

        /** @var string $actual */
        $actual = preg_replace('/\\s+/', ' ', $actual);
        $actual = str_replace(['( ', ' )'], ['(', ')'], $actual);

        self::same($actual, $expected);
    }

    public static function parseSerialize(string $query, ?string $expected = null): Command
    {
        /** @var string $query */
        $query = preg_replace('/\\s+/', ' ', $query);
        $query = str_replace(['( ', ' )'], ['(', ')'], $query);

        if ($expected !== null) {
            /** @var string $expected */
            $expected = preg_replace('/\\s+/', ' ', $expected);
            $expected = str_replace(['( ', ' )'], ['(', ')'], $expected);
        } else {
            $expected = $query;
        }

        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);
        $suite->normalizer->quoteAllNames(false);


        $results = iterator_to_array($suite->parser->parse($query));
        if (count($results) > 1) {
            if (class_exists(Debugger::class)) {
                Debugger::dump($results);
                foreach ($results as $command) {
                    if ($command instanceof InvalidCommand) {
                        Debugger::dump($command->getErrors());
                    }
                }
            }
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];

        if ($command instanceof InvalidCommand) {
            if (class_exists(Debugger::class)) {
                Debugger::dump($command->getTokenList());
            }
            $errors = $command->getErrors();
            $message = '';
            foreach ($errors as $error) {
                $message .= "\n - " . $error->message;
            }
            self::fail($message);
        }

        $actual = $command->serialize($suite->formatter);

        /** @var string $actual */
        $actual = preg_replace('/\\s+/', ' ', $actual);
        $actual = str_replace(['( ', ' )'], ['(', ')'], $actual);

        self::same($actual, $expected);

        return $command;
    }

    public static function validCommand(string $query): Command
    {
        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

        $results = iterator_to_array($suite->parser->parse($query));
        if (count($results) > 1) {
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];
        $errors = $command->getErrors();

        if ($errors !== []) {
            Debugger::dump($command->getTokenList());
            $message = Error::summarize($errors);

            self::fail($message);
        }

        self::true(true);

        return $command;
    }

    public static function invalidCommand(string $query): Command
    {
        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

        $results = iterator_to_array($suite->parser->parse($query));
        if (count($results) > 1) {
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];
        $errors = $command->getErrors();

        if ($errors === []) {
            Debugger::dump($command->getTokenList());

            self::fail("Command should have failed to parse.");
        }

        self::true(true);

        return $command;
    }

    /**
     * @return list<Command>
     */
    public static function validCommands(string $sql): array
	{
        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

        $commands = [];
        try {
            /** @var Command $command */
            foreach ($suite->parser->parse($sql) as $command) {
                $commands[] = $command;
                $errors = $command->getErrors();

                if ($errors !== []) {
                    $message = Error::summarize($errors);
                    self::fail($message);
                }

                self::true(true);
            }
        } catch (LexerException $e) {
            throw $e;
        }

        self::true(true);

        return $commands;
    }

}
