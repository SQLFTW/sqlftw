<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\XaTransaction;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\StringValue;
use SqlFtw\Sql\SqlSerializable;

class Xid implements SqlSerializable
{

    /** @var StringValue */
    private $transactionId;

    /** @var StringValue|null */
    private $branchQualifier;

    /** @var int|null */
    private $formatId;

    public function __construct(StringValue $transactionId, ?StringValue $branchQualifier, ?int $formatId)
    {
        $this->transactionId = $transactionId;
        $this->branchQualifier = $branchQualifier;
        $this->formatId = $formatId;
    }

    public function getTransactionId(): StringValue
    {
        return $this->transactionId;
    }

    public function getBranchQualifier(): ?StringValue
    {
        return $this->branchQualifier;
    }

    public function getFormatId(): ?int
    {
        return $this->formatId;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->transactionId->serialize($formatter);
        if ($this->branchQualifier !== null) {
            $result .= ', ' . $this->branchQualifier->serialize($formatter);
            if ($this->formatId !== null) {
                $result .= ', ' . $this->formatId;
            }
        }

        return $result;
    }

}
