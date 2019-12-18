<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Packets\Packet;

class SslConnectionRequest implements Packet
{
    use StrictBehaviorMixin;

    /** @var int(32) */
    private $capabilityFlags;

    /** @var int(32) */
    private $maxPacketSize;

    /** @var int(8) */
    private $charset;

}
