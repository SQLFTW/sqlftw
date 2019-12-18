<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

interface Packet
{

    /**
     * @param \SqlFtw\Protocol\Mysql\Packets\PacketData $data
     * @param int $capabilities
     * @return self
     */
    public static function createFromData(PacketData $data, int $capabilities);

    public function serialize(int $capabilities): PacketData;

}
