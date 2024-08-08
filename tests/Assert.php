<?php declare(strict_types = 1);

namespace SqlFtw\Tests;

use Dogma\Debug\Callstack;
use Dogma\Debug\Debugger;
use Dogma\Re;
use Dogma\Tester\Assert as DogmaAssert;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\AnalyzerException;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\LexerException;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserConfig;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
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
    public static function tokens(string $sql, int $count, ?string $mode = null, ?int $extensions = null): array
    {
        $platform = Platform::get(Platform::MYSQL, '5.7');
        if ($extensions === null) {
            $extensions = ClientSideExtension::ALLOW_DELIMITER_DEFINITION
                | ClientSideExtension::ALLOW_NUMBERED_QUESTION_MARK_PLACEHOLDERS
                | ClientSideExtension::ALLOW_NAMED_DOUBLE_COLON_PLACEHOLDERS;
        }
        $config = new ParserConfig($platform, $extensions, true, true);
        $session = new Session($platform);
        if ($mode !== null) {
            $session->setMode($session->getMode()->add($mode));
        }
        $lexer = new Lexer($config, $session);

        /** @var array<TokenList> $tokenLists */
        $tokenLists = iterator_to_array($lexer->tokenize($sql));
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
        if ($position !== null && $position !== $token->position) {
            parent::fail(sprintf('Token starting position is %s and should be %s.', $token->position, $position));
        }
    }

    public static function invalidToken(Token $token, int $type, string $messageRegexp, ?int $position = null): void
    {
        if ($type !== $token->type) {
            $actualDesc = implode('|', TokenType::getByValue($token->type)->getConstantNames());
            $typeDesc = implode('|', TokenType::getByValue($type)->getConstantNames());
            parent::fail(sprintf('Type of token "%s" is %s (%d) and should be %s (%d).', $token->value, $actualDesc, $token->type, $typeDesc, $type));
        }
        if (!$token->exception instanceof LexerException) {
            parent::fail(sprintf('Token value is %s (%d) and should be a LexerException.', $token->value, gettype($token->value)));
        } else {
            $message = $token->exception->getMessage();
            if (Re::match($message, $messageRegexp) === null) {
                parent::fail(sprintf('Token exception message is "%s" and should match "%s".', $message, $messageRegexp));
            }
        }
        if ($position !== null && $position !== $token->position) {
            parent::fail(sprintf('Token starting position is %s and should be %s.', $token->position, $position));
        }
    }

    public static function tokenList(string $sql): TokenList
    {
        $platform = Platform::get(Platform::MYSQL, '5.7');
        $config = new ParserConfig($platform, 0, true, true);
        $session = new Session($platform);
        $lexer = new Lexer($config, $session);

        return iterator_to_array($lexer->tokenize($sql))[0];
    }

    public static function parseSerializeMany(
        string $query,
        ?string $expected = null,
        ?int $version = null
    ): void
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

        $parser = ParserHelper::createParser(null, $version);
        $formatter = new Formatter($parser->getPlatform(), $parser->getSession());

        $results = iterator_to_array($parser->parse($query));

        $serialized = [];
        foreach ($results as $command) {
            if ($command instanceof InvalidCommand) {
                if (class_exists(Debugger::class)) {
                    Debugger::dump($command->getTokenList());
                }
                $exception = $command->getException();
                $message = '';
                if ($exception instanceof AnalyzerException) {
                    foreach ($exception->getResults() as $failure) {
                        $message .= "\n - " . $failure->getMessage();
                    }
                }
                self::fail($exception->getMessage() . $message);
                break;
            }
            $serialized[] = $formatter->serialize($command);
        }
        $actual = implode("\n", $serialized);

        /** @var string $actual */
        $actual = preg_replace('/\\s+/', ' ', $actual);
        $actual = str_replace(['( ', ' )'], ['(', ')'], $actual);

        self::same($actual, $expected);
    }

    public static function parseSerialize(
        string $query,
        ?string $expected = null,
        ?int $version = null
    ): Command {
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

        $parser = ParserHelper::createParser(null, $version);
        $formatter = new Formatter($parser->getPlatform(), $parser->getSession());

        $results = iterator_to_array($parser->parse($query));
        if (count($results) > 1) {
            if (class_exists(Debugger::class)) {
                Debugger::dump($results);
                foreach ($results as $command) {
                    if ($command instanceof InvalidCommand) {
                        Debugger::dumpException($command->getException());
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
            $exception = $command->getException();
            $message = '';
            if ($exception instanceof AnalyzerException) {
                foreach ($exception->getResults() as $failure) {
                    $message .= "\n - " . $failure->getMessage();
                }
            }
            self::fail($exception->getMessage() . $message);
        }

        $actual = $command->serialize($formatter);

        /** @var string $actual */
        $actual = preg_replace('/\\s+/', ' ', $actual);
        $actual = str_replace(['( ', ' )'], ['(', ')'], $actual);

        self::same($actual, $expected);

        return $command;
    }

    public static function validCommand(string $query, ?Parser $parser = null): Command
    {
        $parser = $parser ?? ParserHelper::createParser();

        $results = iterator_to_array($parser->parse($query));
        if (count($results) > 1) {
            self::fail('More than one command found in given SQL code.');
        }
        $command = $results[0];

        if ($command instanceof InvalidCommand) {
            if (class_exists(Debugger::class)) {
                Debugger::dump($command->getTokenList());
            }
            throw $command->getException();
        }

        self::true(true);

        return $command;
    }

    /**
     * @return list<Command>
     */
    public static function validCommands(string $sql, ?Parser $parser = null): array
	{
        $parser = $parser ?? ParserHelper::createParser();

        $commands = [];
        try {
            /** @var Command $command */
            foreach ($parser->parse($sql) as $command) {
                $commands[] = $command;
                if ($command instanceof InvalidCommand) {
                    throw $command->getException();
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
