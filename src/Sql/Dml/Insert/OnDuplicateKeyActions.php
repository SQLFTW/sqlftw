<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\Arr;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class OnDuplicateKeyActions implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $expressions;

    /**
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $expressions (string $column => ExpressionNode $value)
     */
    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ON DUPLICATE KEY UPDATE ';

        $result .= implode(Arr::mapPairs($this->expressions, function (string $column, ExpressionNode $expression) use ($formatter): string {
            return $formatter->formatName($column) . ' = ' . $expression->serialize($formatter);
        }));

        return $result;
    }

}
