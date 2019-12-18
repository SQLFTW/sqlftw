<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

use SqlFtw\Tests\Assert;

require '../../bootstrap.php';


$capabilities = 0x001aa28d;
$data = PacketData::fromHex("
    00 00 00 02 00 00 00
");

$packet = OkPacket::createFromData($data, $capabilities);

Assert::same($packet->getHeader(), 0);
Assert::same($packet->getAffectedRows(), 0);
Assert::same($packet->getLastInsertId(), 0);
Assert::same($packet->getStatus(), 2);
Assert::same($packet->getWarnings(), 0);
Assert::same($packet->getInfo(), null);
Assert::same($packet->getSessionStateInfo(), null);

Assert::equal($packet->serialize($capabilities), $data);
