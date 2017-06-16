<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\XaTransaction;

use SqlFtw\SqlFormatter\SqlFormatter;

class Xid implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $transactionId;

    /** @var string|null */
    private $branchQualifier;

    /** @var int|null */
    private $formatId;

    public function __construct(string $transactionId, ?string $branchQualifier = null, ?int $formatId)
    {
        $this->transactionId = $transactionId;
        $this->branchQualifier = $branchQualifier;
        $this->formatId = $formatId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getBranchQualifier(): ?string
    {
        return $this->branchQualifier;
    }

    public function getFormatId(): ?int
    {
        return $this->formatId;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $formatter->formatString($this->transactionId);
        if ($this->branchQualifier !== null) {
            $result .= ', ' . $formatter->formatString($this->branchQualifier);
            if ($this->formatId !== null) {
                $result .= ', ' . $this->formatId;
            }
        }
        return $result;
    }

}
