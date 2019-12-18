<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use SqlFtw\Protocol\Mysql\ConnectionAttribute;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Sql\Charset;
use SqlFtw\Tests\Assert;

require '../../../bootstrap.php';


$data = PacketData::fromHex("
    8d a2 1a 00 00 00 00 c0 21 00 00 00 00 00 00 00
    00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
    66 6f 6f 00 14 44 9e 16 2e 3c b7 e9 26 6f d8 6d
    2d 7d a8 16 2d 35 e7 bc ff 00 6d 79 73 71 6c 5f
    6e 61 74 69 76 65 5f 70 61 73 73 77 6f 72 64 00
    15 0c 5f 63 6c 69 65 6e 74 5f 6e 61 6d 65 07 6d
    79 73 71 6c 6e 64
");

$packet = HandshakeResponse::createFromData($data);

Assert::same($packet->getCapabilities(), 0x001aa28d);
Assert::same($packet->getMaxPacketSize(), 3221225472);
Assert::same($packet->getCharsetId(), Charset::get(Charset::UTF_8_OLD)->getId());
Assert::same($packet->getUserName(), 'foo');
Assert::same($packet->getDatabase(), '');
Assert::same($packet->getAuthResponse(), "\x44\x9e\x16\x2e\x3c\xb7\xe9\x26\x6f\xd8\x6d\x2d\x7d\xa8\x16\x2d\x35\xe7\xbc\xff");
Assert::same($packet->getAuthPluginName(), 'mysql_native_password');
Assert::same($packet->getConnectionAttributes(), [
    ConnectionAttribute::CLIENT_NAME => 'mysqlnd',
]);

Assert::equal($packet->serialize(), $data);
