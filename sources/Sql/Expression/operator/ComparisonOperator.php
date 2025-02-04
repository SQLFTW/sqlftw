<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

/**
 * left = [ALL | ANY | SOME] right
 * left != [ALL | ANY | SOME] right
 * left <> [ALL | ANY | SOME] right
 * left < [ALL | ANY | SOME] right
 * left <= [ALL | ANY | SOME] right
 * left > [ALL | ANY | SOME] right
 * left >= [ALL | ANY | SOME] right
 * left <=> right
 * left [NOT] IN right
 */
class ComparisonOperator extends OperatorExpression
{

    public RootNode $left;

    public Operator $operator;

    /** @var 'ALL'|'ANY'|'SOME'|null */
    public ?string $quantifier;

    public RootNode $right;

    /**
     * @param 'ALL'|'ANY'|'SOME'|null $quantifier
     */
    public function __construct(RootNode $left, Operator $operator, ?string $quantifier, RootNode $right)
    {
        $operator->checkBinary();

        $this->left = $left;
        $this->operator = $operator;
        $this->quantifier = $quantifier;
        $this->right = $right;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->left->serialize($formatter) . ' '
            . $this->operator->serialize($formatter) . ' ' . ($this->quantifier !== null ? $this->quantifier . ' ' : '')
            . $this->right->serialize($formatter);
    }

}
