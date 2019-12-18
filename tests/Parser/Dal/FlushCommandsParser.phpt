<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// FLUSH
Assert::parse("FLUSH NO_WRITE_TO_BINLOG DES_KEY_FILE", "FLUSH LOCAL DES_KEY_FILE");
Assert::parse("FLUSH LOCAL HOSTS");
Assert::parse("FLUSH BINARY LOGS");
Assert::parse("FLUSH ENGINE LOGS");
Assert::parse("FLUSH ERROR LOGS");
Assert::parse("FLUSH GENERAL LOGS");
Assert::parse("FLUSH RELAY LOGS");
Assert::parse("FLUSH SLOW LOGS");
Assert::parse("FLUSH RELAY LOGS FOR CHANNEL foo", "FLUSH RELAY LOGS FOR CHANNEL 'foo'");
Assert::parse("FLUSH OPTIMIZER_COSTS");
Assert::parse("FLUSH PRIVILEGES");
Assert::parse("FLUSH QUERY CACHE");
Assert::parse("FLUSH STATUS");
Assert::parse("FLUSH USER_RESOURCES");
Assert::parse("FLUSH PRIVILEGES, STATUS");
