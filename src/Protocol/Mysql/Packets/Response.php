<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

interface Response extends Packet
{

    public function getHeader(): int;

}
