<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Packets\Packet;

class ExtraAuthenticationData implements Packet
{
    use StrictBehaviorMixin;

    /** @var int */
    private $type = 1;

    /** @var string */
    private $data;

}
