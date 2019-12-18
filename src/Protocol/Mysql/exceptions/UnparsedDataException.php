<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use SqlFtw\Protocol\ClientException;

class UnparsedDataException extends ClientException
{

    public function __construct(int $bytes, \Throwable $previous = null)
    {
        parent::__construct("There are $bytes bytes of unparsed data at the end of the packet", $previous);
    }

}
