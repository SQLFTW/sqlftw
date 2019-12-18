<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Tests\Assert;

require '../../../bootstrap.php';


$capabilities = 0x001aa28d;
$data = PacketData::fromHex("
    09
");

$command = StatisticsCommand::createFromData($data, $capabilities);

Assert::same($command->getHeader(), PacketHeader::STATISTICS);

Assert::equal($command->serialize($capabilities), $data);
