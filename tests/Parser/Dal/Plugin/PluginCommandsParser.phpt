<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// INSTALL PLUGIN
$query = "INSTALL PLUGIN foo SONAME 'library.so'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// UNINSTALL PLUGIN
$query = 'UNINSTALL PLUGIN foo';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
