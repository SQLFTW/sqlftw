<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use SqlFtw\Protocol\ClientException;

class UnknownServerProtocolVersionException extends ClientException
{

    public function __construct(int $version, int $expected, \Throwable $previous = null)
    {
        parent::__construct("Unknown server protocol version $version. Expected version $expected.", $previous);
    }

}
