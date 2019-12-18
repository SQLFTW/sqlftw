<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Connection;
use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\Response\Error;
use SqlFtw\Protocol\Mysql\Packets\Response\Success;
use SqlFtw\Protocol\Mysql\Packets\Text\QueryCommand;

class MysqlClient
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Protocol\Connection */
    private $connection;

    /** @var int */
    private $capabilities;

    public function connect(
        Connection $connection,
        ?string $database,
        string $charset,
        string $userName,
        string $password
    ): void
    {
        $this->connection = $connection;

        // receive handshake
        $handshake = $connection->receive();

        // todo: send ssl request

        // todo: connect ssl

        // send handshake response

        // todo: send switch auth

        // todo: receive switch auth response

        // todo: send extra auth data

        // receive ok/error
    }

    public function query(string $query, ?callable $fileUploadCallback = null): QueryResponse
    {
        $queryCommand = new QueryCommand($query);
        $this->send($queryCommand);

        $data = $this->receivePacket();
        $header = $data->readVarUint();
        $data->resetPosition();

        if ($header === PacketHeader::OK) {
            return Success::createFromData($data, $this->capabilities);
        } elseif ($header === PacketHeader::ERROR) {
            return Error::createFromData($data, $this->capabilities);
        } elseif ($header === PacketHeader::LOCAL_INFILE_REQUEST) {
            // todo
            throw new NotImplementedException('');
        } else {
            $packet = $this->receivePacket();
            while (true) {
                exit;
            }
        }
    }

    private function receiveResult(): QueryResult
    {

    }

    private function send(Command $command): void
    {
        /////
    }

    private function receivePacket(): PacketData
    {

    }

}
