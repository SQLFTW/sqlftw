<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Connection;

use Closure;
use LogicException;
use PDO;
use PDOException;
use SensitiveParameter;
use function rd;

class PdoConnection implements Connection
{

    private string $dsn;

    private string $user;

    private string $password;

    /** @var array<scalar>|null */
    private ?array $pdoOptions;

    private ?PDO $pdo = null;

    private ?Closure $onConnect;

    public function __construct(
        string $dsn,
        ?string $user = null,
        #[SensitiveParameter]
        ?string $password = null,
        ?array $pdoOptions = null,
        ?Closure $onConnect = null
    ) {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->pdoOptions = $pdoOptions;
        $this->onConnect = $onConnect;
    }

    private function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->pdoOptions);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if ($this->onConnect !== null) {
                ($this->onConnect)($this);
            }
        }

        return $this->pdo;
    }

    /**
     * @throws ConnectionException
     */
    public function query(string $query): PdoResult
    {
        try {
            $connection = $this->getConnection();
            $result = $connection->query($query);
            foreach (range(0, $result->columnCount()) as $c) {
                $meta = $result->getColumnMeta($c);
                rd($meta);
            }

            return new PdoResult($result);
        } catch (PDOException $e) {
            throw ConnectionException::fromPdoException($e);
        }
    }

    public function execute(string $statement): int
    {
        try {
            $result = $this->getConnection()->exec($statement);
            if ($result === false) {
                throw new LogicException("PDO::exec() result should not be false with PDO::ERRMODE_EXCEPTION.");
            }

            return $result;
        } catch (PDOException $e) {
            throw ConnectionException::fromPdoException($e);
        }
    }

}
