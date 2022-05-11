<?php declare(strict_types = 1);

namespace SqlFtw\Tests;

use Dogma\Debug\Callstack;
use Dogma\Debug\Debugger;
use Dogma\Debug\Dumper;
use Dogma\Str;
use Dogma\Tester\Assert as DogmaAssert;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\LexerException;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\ParsingException;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;
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

    /**
     * @param mixed|null $value
     */
    public static function token(Token $token, int $type, $value = null, ?int $position = null): void
    {
        if ($type !== $token->type) {
            parent::fail(sprintf(
                'Type of token "%s" is %s (%d) and should be %s (%d).',
                $token->value,
                implode('|', TokenType::getByValue($token->type)->getConstantNames()),
                $token->type,
                implode('|', TokenType::getByValue($type)->getConstantNames()),
                $type
            ));
        }
        if ($value !== $token->value) {
            parent::fail(sprintf(
                'Token value is "%s" (%s) and should be "%s" (%s).',
                $token->value,
                gettype($token->value),
                $value,
                gettype($value)
            ));
        }
        if ($position !== null && $position !== $token->position) {
            parent::fail(sprintf('Token starting position is %s and should be %s.', $token->position, $position));
        }
    }

    public static function parse(
        string $query,
        ?string $expected = null,
        ?Parser $parser = null,
        ?Formatter $formatter = null
    ): void {
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

        $parser = $parser ?? ParserHelper::getParserFactory()->getParser();
        $formatter = $formatter ?? new Formatter($parser->getSettings());

        try {
            $actual = $parser->parseSingleCommand($query)->serialize($formatter);
        } catch (ParserException $e) {
            if (class_exists(Debugger::class)) {
                if ($e instanceof ParserException) {
                    Debugger::dump($e->getTokenList());
                }
            }
            self::fail($e->getMessage());
            return;
        }
        /** @var string $actual */
        $actual = preg_replace('/\\s+/', ' ', $actual);
        $actual = str_replace(['( ', ' )'], ['(', ')'], $actual);

        self::same($expected, $actual);
    }

    public static function validCommand(
        string $query,
        ?Parser $parser = null
    ): void {
        $parser = $parser ?? ParserHelper::getParserFactory()->getParser();

        try {
            $parser->parseSingleCommand($query);
        } catch (ParsingException $e) {
            if (class_exists(Dumper::class) && $e->backtrace !== null) {
                Debugger::send(1, Dumper::formatCallstack(Callstack::fromBacktrace($e->backtrace), 100, 1, 5, 100));
            }
            self::fail($e->getMessage());
            return;
        }

        self::true(true);
    }

    public static function validCommands(
        string $sql,
        ?Parser $parser = null
    ): void {
        $parser = $parser ?? ParserHelper::getParserFactory()->getParser();

        try {
            foreach ($parser->parse($sql) as $command) {
                if ($command instanceof InvalidCommand) {
                    $source = $command->getTokenList()->serialize();
                    // filtering "false" negatives
                    // todo: also should filter false positives
                    if (Str::contains($source, "--error ER_")) {
                        self::true(true);
                    } else {
                        Debugger::dump($command->getTokenList());
                        //Debugger::callstack(100, 1, 5, 100, $command->getException()->getTrace());
                        //self::fail('Invalid command');
                        throw $command->getException();
                    }
                } else {
                    self::true(true);
                }
            }
        } catch (LexerException $e) {
            if (class_exists(Debugger::class)) {
                if ($e instanceof ParserException) {
                    Debugger::dump($e->getTokenList());
                }
                if ($e->backtrace !== null) {
                    /** @var PhpBacktraceItem[] $trace */
                    $trace = $e->getTrace();
                    Debugger::callstack(100, 1, 5, 100, $trace);
                }
            }
            self::fail('Cannot tokenize commands');
            return;
        }

        self::true(true);
    }

}
