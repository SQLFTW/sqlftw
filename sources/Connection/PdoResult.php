<?php

namespace SqlFtw\Connection;

use Iterator;
use IteratorIterator;
use PDO;
use PDOStatement;
use const PHP_VERSION_ID;

class PdoResult implements Result
{

    private PDOStatement $result;

    public function __construct(PDOStatement $result)
    {
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $this->result = $result;
    }

    /**
     * @return list<array<string, scalar>>
     */
    public function all(): array
    {
        if ($this->result->columnCount() === 0) {
            return [];
        } else {
            return $this->result->fetchAll();
        }
    }

    /**
     * @return Iterator<array<string, scalar>>
     */
    public function getIterator(): Iterator
    {
        if (PHP_VERSION_ID >= 80000) {
            return $this->result->getIterator();
        } else {
            return new IteratorIterator($this->result);
        }
    }

    public function rowCount(): int
    {
        return $this->result->rowCount();
    }

    public function columnCount(): int
    {
        return $this->result->columnCount();
    }

}
