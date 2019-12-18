<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

use Dogma\StrictBehaviorMixin;

class TransportPacket
{
    use StrictBehaviorMixin;

    private const MAX_LENGTH = 0xffffff;

    /** @var int(24) */
    private $length;

    /** @var int(8) */
    private $sequence;

    /** @var string */
    private $payload;

    public function serialize(): string
    {
        return ''; // todo
    }

}
