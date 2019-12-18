<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Procedures;

use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;

class SetOptionCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::SET_OPTION;
    }

}
