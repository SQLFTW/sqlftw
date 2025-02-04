<?php

namespace SqlFtw\Tests;

use Dogma\Debug\Callstack;
use Dogma\Debug\Debugger;
use Dogma\Re;
use Dogma\Tester\Assert as DogmaAssert;
use SqlFtw\Analyzer\Rules\RuleFactory;
use SqlFtw\Error\Error;
use SqlFtw\Error\Severity;
use SqlFtw\Parser\InvalidCommand;
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
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * @phpstan-import-type PhpBacktraceItem from Callstack
 */
class Assert extends DogmaAssert
{

    public static RuleFactory $ruleFactory;

    private ?Platform $platform = null;

    private ?string $platformName = null;

    private ?string $platformVersion = null;

    private ?int $extensions = null;

    private ?int $mode = null;

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
        $tokenLists = $suite->lexer->tokenizeAll($sql);
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
        if ($token->error === null || $token->error->severity !== Severity::LEXER_ERROR) {
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

        return $suite->lexer->tokenizeAll($sql)[0];
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

        $results = $suite->parser->parseAll($query);

        $serialized = [];
        foreach ($results as $command) {
            if ($command instanceof InvalidCommand) {
                if (class_exists(Debugger::class)) {
                    Debugger::dump($command->tokenList);
                }

                $message = Error::summarize($command->errors);

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

    public static function parseSerialize(string $query, ?string $expected = null, ?int $extensions = null): Command
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

        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL, $extensions);
        $suite->normalizer->quoteAllNames(false);


        $results = $suite->parser->parseAll($query);
        if (count($results) > 1) {
            if (class_exists(Debugger::class)) {
                Debugger::dump($results);
                foreach ($results as $command) {
                    if ($command instanceof InvalidCommand) {
                        Debugger::dump($command->errors);
                    }
                }
            }
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];

        if ($command instanceof InvalidCommand) {
            if (class_exists(Debugger::class)) {
                Debugger::dump($command->tokenList);
            }

            $message = Error::summarize($command->errors);

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

        $results = $suite->parser->parseAll($query);
        if (count($results) > 1) {
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];

        if ($command->errors !== []) {
            Debugger::dump($command->tokenList);
            $message = Error::summarize($command->errors);

            self::fail($message);
        }

        self::true(true);

        return $command;
    }

    public static function invalidCommand(string $query): Command
    {
        $suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

        $results = $suite->parser->parseAll($query);
        if (count($results) > 1) {
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];

        if ($command->errors === []) {
            Debugger::dump($command->tokenList);
            Debugger::dump($command);

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
        /** @var Command $command */
        foreach ($suite->parser->parse($sql) as $command) {
            $commands[] = $command;

            if ($command->errors !== []) {
                $message = Error::summarize($command->errors);
                self::fail($message);
            }

            self::true(true);
        }

        self::true(true);

        return $commands;
    }

}
