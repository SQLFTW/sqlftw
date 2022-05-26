<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\QualifiedName;

class ExchangePartitionAction implements PartitioningAction
{
    use StrictBehaviorMixin;

    /** @var string */
    private $partition;

    /** @var QualifiedName */
    private $table;

    /** @var bool|null */
    private $validation;

    public function __construct(string $partition, QualifiedName $table, ?bool $validation)
    {
        $this->partition = $partition;
        $this->table = $table;
        $this->validation = $validation;
    }

    public function getPartition(): string
    {
        return $this->partition;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getValidation(): ?bool
    {
        return $this->validation;
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
