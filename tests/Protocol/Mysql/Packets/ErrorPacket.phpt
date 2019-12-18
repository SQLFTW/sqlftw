<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

use SqlFtw\Tests\Assert;

require '../../bootstrap.php';


$capabilities = 0x001aa28d;
$data = PacketData::fromHex("
    ff 48 04 23 48 59 30 30 30 4e 6f 20 74 61 62 6c
    65 73 20 75 73 65 64
");

$packet = ErrorPacket::createFromData($data, $capabilities);

Assert::same($packet->getErrorCode(), 1096);
Assert::same($packet->getErrorMessage(), 'No tables used');
Assert::same($packet->getSqlState(), 'HY000');

Assert::equal($packet->serialize($capabilities), $data);
