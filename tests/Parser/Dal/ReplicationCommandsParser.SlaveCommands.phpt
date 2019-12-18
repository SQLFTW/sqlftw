<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// RESET MASTER
Assert::parse("RESET MASTER");
Assert::parse("RESET MASTER TO 12345");


// RESET SLAVE
Assert::parse("RESET SLAVE");
Assert::parse("RESET SLAVE ALL");
Assert::parse("RESET SLAVE FOR CHANNEL 'foo'");
Assert::parse("RESET SLAVE ALL FOR CHANNEL 'foo'");


// START GROUP_REPLICATION
Assert::parse("START GROUP_REPLICATION");


// START SLAVE
Assert::parse("START SLAVE");
Assert::parse("START SLAVE IO_THREAD");
Assert::parse("START SLAVE SQL_THREAD");
Assert::parse("START SLAVE IO_THREAD, SQL_THREAD");
Assert::parse("START SLAVE UNTIL SQL_BEFORE_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10");
Assert::parse("START SLAVE UNTIL SQL_AFTER_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10");
Assert::parse("START SLAVE UNTIL SQL_AFTER_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10-20:30-40:50-60");
Assert::parse("START SLAVE UNTIL SQL_AFTER_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10-20:30-40:50-60, 82c4b49c-b591-4249-8600-d2ba6a528791:70-80");
Assert::parse("START SLAVE USER='foo' PASSWORD='bar' DEFAULT_AUTH='baz' PLUGIN_DIR='dir'");
Assert::parse("START SLAVE FOR CHANNEL 'foo'");


// STOP GROUP_REPLICATION
Assert::parse("STOP GROUP_REPLICATION");


// STOP SLAVE
Assert::parse("STOP SLAVE");
Assert::parse("STOP SLAVE IO_THREAD");
Assert::parse("STOP SLAVE SQL_THREAD");
Assert::parse("STOP SLAVE IO_THREAD, SQL_THREAD");
Assert::parse("STOP SLAVE FOR CHANNEL 'foo'");
