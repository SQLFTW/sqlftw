<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;

class ProcessKillCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::PROCESS_KILL;
    }

}
