<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Tests\Assert;

require '../../../bootstrap.php';


$capabilities = 0x001aa28d;
$data = PacketData::fromHex("
    0e
");

$command = PingCommand::createFromData($data, $capabilities);

Assert::same($command->getHeader(), PacketHeader::PING);

Assert::equal($command->serialize($capabilities), $data);
