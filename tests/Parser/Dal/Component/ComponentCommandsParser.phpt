<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Platform\Platform;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = getParserFactory(Platform::get(Platform::MYSQL, '8.0'))->getParser();
$formatter = new Formatter($parser->getSettings());

// INSTALL COMPONENT
$query = 'INSTALL COMPONENT foo, bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// UNINSTALL COMPONENT
$query = 'UNINSTALL COMPONENT foo, bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
