<?php declare(strict_types=1);

namespace SqlFtw\Protocol;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;

class Connection
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $host;

    /** @var string|null */
    private $ip;

    /** @var int|null */
    private $port;

    /** @var string|null */
    private $socketName;

    /** @var resource|null */
    private $socket;

    public function connectHost(string $host, int $port): void
    {
        Check::intBounds($port, 16, false);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = $host;
        } else {
            $ip = gethostbyname($host);
            if ($ip === $host) {
                // todo throw
            }
        }
        $this->host = $host;

        $this->connect($ip, $port, null);
    }

    private function connect(
        ?string $ip,
        ?int $port,
        ?string $socketName
    ): void
    {
        if ($this->socket !== null) {
            // todo throw
        }
        $this->ip = $ip;
        $this->port = $port;
        $this->socketName = $socketName;

        // connect
        if ($socketName !== null) {
            $socket = socket_create(AF_UNIX, SOCK_STREAM, SOL_SOCKET);
        } elseif (strpos($ip, ':') !== false) {
            $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        } else {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        }
        if ($socket === false) {
            // todo throw
        }

        if ($socketName !== null) {
            $connect = socket_connect($socket, $socketName);
        } else {
            $connect = socket_connect($socket, $ip, $port);
        }


        $this->socket = $socket;
    }

}
