<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

interface Command extends Packet
{

    public function getHeader(): int;

    /**
     * @return string[] packet classes
     */
    public function returns(): array;

}
