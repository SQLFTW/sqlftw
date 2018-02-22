<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Server;

use SqlFtw\Formatter\Formatter;

class AlterServerCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $host;

    /** @var string|null */
    private $database;

    /** @var string|null */
    private $user;

    /** @var string|null */
    private $password;

    /** @var string|null */
    private $socket;

    /** @var string|null */
    private $owner;

    /** @var int|null */
    private $port;

    public function __construct(
        string $name,
        ?string $host = null,
        ?string $database = null,
        ?string $user = null,
        ?string $password = null,
        ?string $socket = null,
        ?string $owner = null,
        ?int $port = null
    ) {
        $this->name = $name;
        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
        $this->socket = $socket;
        $this->owner = $owner;
        $this->port = $port;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSocket(): ?string
    {
        return $this->socket;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE SERVER ' . $formatter->formatName($this->name) . ' OPTIONS (';

        if ($this->host !== null) {
            $result .= 'HOST ' . $formatter->formatString($this->host) . ', ';
        }
        if ($this->database !== null) {
            $result .= 'DATABASE ' . $formatter->formatString($this->database) . ', ';
        }
        if ($this->user !== null) {
            $result .= 'USER ' . $formatter->formatString($this->user) . ', ';
        }
        if ($this->password !== null) {
            $result .= 'PASSWORD ' . $formatter->formatString($this->password) . ', ';
        }
        if ($this->socket !== null) {
            $result .= 'SOCKET ' . $formatter->formatString($this->socket) . ', ';
        }
        if ($this->owner !== null) {
            $result .= 'OWNER ' . $formatter->formatString($this->owner) . ', ';
        }
        if ($this->port !== null) {
            $result .= 'PORT ' . $this->port . ', ';
        }

        return rtrim(rtrim($result, ' '), ',') . ')';
    }

}
