<?php

namespace SqlFtw\Tests\Analyzer\Context;

use SqlFtw\Analyzer\Context\FileIterator\PhpFilesIterator;
use SqlFtw\Platform\Platform;
use SqlFtw\Tests\Assert;
use SqlFtw\Tests\CurrenVersion;
use SqlFtw\Tests\ParserSuiteFactory;
use function iterator_to_array;
use function rd;
use function rl;

require __DIR__ . '/../../../bootstrap.php';

$code = file_get_contents(__DIR__ . '/PhpFileIteratorTestFile.php');
$values = include __DIR__ . '/PhpFileIteratorTestFile.php';

$strings = PhpFilesIterator::extractConstantStrings($code);
Assert::same($strings, $values);


$t = microtime(true);
$files = glob('/home/vlasta/shipmonk/shipmonk/backend/migrations/*');
rd($files);

$i = new PhpFilesIterator($files);
$migrations = iterator_to_array($i);
rd($migrations);
rd(count($migrations));

$suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);
$c = 0;
foreach ($migrations as $statements) {
    foreach ($statements as $file => $statement) {
        foreach ($suite->parser->parseAll($statement) as $command) {
            $errors = $command->getErrors();
            if ($errors !== []) {
                //rl($errors);
                //rd($statement);
            }
            $c++;
        }
    }
}

rd(microtime(true) - $t);
rd($c);