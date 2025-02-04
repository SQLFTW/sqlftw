<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class StartTransactionCommand extends Command implements TransactionCommand
{

    public ?bool $consistent;

    public ?bool $write;

    public function __construct(?bool $consistent = null, ?bool $write = null)
    {
        $this->consistent = $consistent;
        $this->write = $write;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'START TRANSACTION';
        if ($this->consistent !== null) {
            $result .= ' WITH CONSISTENT SNAPSHOT';
        }
        if ($this->consistent !== null && $this->write !== null) {
            $result .= ',';
        }
        if ($this->write !== null) {
            $result .= $this->write ? ' READ WRITE' : ' READ ONLY';
        }

        return $result;
    }

}
