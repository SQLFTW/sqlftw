<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Tests\Assert;

require '../../../bootstrap.php';


$capabilities = 0x001aa28d;
$data = PacketData::fromHex("
    02 74 65 73 74
");

$command = InitDatabaseCommand::createFromData($data, $capabilities);

Assert::same($command->getHeader(), PacketHeader::INIT_DATABASE);
Assert::same($command->getDatabase(), 'test');

Assert::equal($command->serialize($capabilities), $data);
