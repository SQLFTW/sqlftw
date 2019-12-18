<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Sql\Charset;
use SqlFtw\Tests\Assert;

require '../../../bootstrap.php';


$data = PacketData::fromHex("
    0a 38 2e 30 2e 31 33 2d 34 00 fe 0c 02 00 68 67
    5b 2e 67 2b 62 0d 00 ff ff 21 02 00 ff c3 15 00
    00 00 00 00 00 00 00 00 00 7e 60 2d 42 20 28 4a
    38 1a 58 25 52 00 6d 79 73 71 6c 5f 6e 61 74 69
    76 65 5f 70 61 73 73 77 6f 72 64 00
");

$packet = Handshake::createFromData($data);

Assert::same($packet->getProtocolVersion(), 10);
Assert::same($packet->getServerVersion(), '8.0.13-4');
Assert::same($packet->getConnectionId(), 134398);
Assert::same($packet->getCharsetId(), Charset::get(Charset::UTF_8_OLD)->getId());
Assert::same($packet->getStatus(), 2);
Assert::same($packet->getCapabilities(), 0xc3ffffff);
Assert::same($packet->getAuthData(), "hg[.g+b\r~`-B (J8\032X%R\x00");
Assert::same($packet->getAuthPluginName(), 'mysql_native_password');

Assert::equal($packet->serialize(), $data);
