<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\XaTransaction;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\HexadecimalLiteral;
use SqlFtw\Sql\Expression\IntLiteral;
use SqlFtw\Sql\Expression\IntValue;
use SqlFtw\Sql\Expression\StringValue;
use SqlFtw\Sql\Node;

class Xid extends Node
{

    public StringValue $transactionId;

    public ?StringValue $branchQualifier;

    /** @var IntLiteral|HexadecimalLiteral|null */
    public ?IntValue $formatId;

    /**
     * @param IntLiteral|HexadecimalLiteral|null $formatId
     */
    public function __construct(StringValue $transactionId, ?StringValue $branchQualifier, ?IntValue $formatId)
    {
        $this->transactionId = $transactionId;
        $this->branchQualifier = $branchQualifier;
        $this->formatId = $formatId;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->transactionId->serialize($formatter);
        if ($this->branchQualifier !== null) {
            $result .= ', ' . $this->branchQualifier->serialize($formatter);
            if ($this->formatId !== null) {
                $result .= ', ' . $this->formatId->serialize($formatter);
            }
        }

        return $result;
    }

}
