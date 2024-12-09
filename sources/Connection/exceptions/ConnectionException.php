<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Connection;

use PDOException;
use SqlFtw\Sql\Dml\Error\SqlState;
use SqlFtw\SqlFtwException;
use Throwable;

class ConnectionException extends SqlFtwException
{

    private ?string $sqlState;

    public function __construct(string $message, int $code = 0, ?string $sqlState = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous, $code);

        $this->sqlState = $sqlState;
    }

    public static function fromPdoException(PDOException $previous): self
    {
        [$sqlState, $code, $message] = $previous->errorInfo;
        $message = "{$message} ($code)";

        return new self($message, $code, $sqlState, $previous);
    }

    public function getSqlStateValue(): string
    {
        return $this->sqlState;
    }

    public function getSqlState(): ?SqlState
    {
        if ($this->sqlState !== null && SqlState::isValid($this->sqlState)) {
            return new SqlState($this->sqlState);
        }

        return null;
    }

}
