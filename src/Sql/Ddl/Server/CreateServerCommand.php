<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Server;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function rtrim;

class CreateServerCommand implements ServerCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $wrapper;

    /** @var string|null */
    private $host;

    /** @var string|null */
    private $schema;

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
        string $wrapper,
        ?string $host = null,
        ?string $schema = null,
        ?string $user = null,
        ?string $password = null,
        ?string $socket = null,
        ?string $owner = null,
        ?int $port = null
    ) {
        $this->name = $name;
        $this->wrapper = $wrapper;
        $this->host = $host;
        $this->schema = $schema;
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

    public function getWrapper(): string
    {
        return $this->wrapper;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
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
        $result = 'CREATE SERVER ' . $formatter->formatName($this->name)
            . ' FOREIGN DATA WRAPPER ' . $formatter->formatString($this->wrapper)
            . ' OPTIONS (';

        if ($this->host !== null) {
            $result .= 'HOST ' . $formatter->formatString($this->host) . ', ';
        }
        if ($this->schema !== null) {
            $result .= 'DATABASE ' . $formatter->formatString($this->schema) . ', ';
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
