<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Replication;

use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;

class BinlogDumpCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::BINLOG_DUMP;
    }

}
