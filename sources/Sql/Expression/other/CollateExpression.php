<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Collation;

/**
 * expression COLLATE collation
 */
class CollateExpression implements RootNode
{
    use StrictBehaviorMixin;

    /** @var RootNode */
    private $expression;

    /** @var Collation */
    private $collation;

    public function __construct(RootNode $expression, Collation $collation)
    {
        $this->expression = $expression;
        $this->collation = $collation;
    }

    public function getExpression(): RootNode
    {
        return $this->expression;
    }

    public function getCollation(): Collation
    {
        return $this->collation;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->expression->serialize($formatter) . ' COLLATE ' . $this->collation->serialize($formatter);
    }

}
