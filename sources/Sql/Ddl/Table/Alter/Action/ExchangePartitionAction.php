<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class ExchangePartitionAction extends PartitioningAction
{

    public string $partition;

    public ObjectIdentifier $table;

    public ?bool $validation;

    public function __construct(string $partition, ObjectIdentifier $table, ?bool $validation)
    {
        $this->partition = $partition;
        $this->table = $table;
        $this->validation = $validation;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'EXCHANGE PARTITION ' . $formatter->formatName($this->partition)
            . ' WITH TABLE ' . $this->table->serialize($formatter);
        if ($this->validation !== null) {
            $result .= $this->validation ? ' WITH VALIDATION' : ' WITHOUT VALIDATION';
        }

        return $result;
    }

}
