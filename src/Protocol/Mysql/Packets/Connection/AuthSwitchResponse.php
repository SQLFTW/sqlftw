<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Packets\Packet;

class AuthSwitchResponse implements Packet
{
    use StrictBehaviorMixin;

    /** @var string */
    private $authPluginResponse;

}
