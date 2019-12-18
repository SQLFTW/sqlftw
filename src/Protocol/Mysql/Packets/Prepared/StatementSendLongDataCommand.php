<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Prepared;

use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;

class StatementSendLongDataCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::STATEMENT_SEND_LONG_DATA;
    }

}
