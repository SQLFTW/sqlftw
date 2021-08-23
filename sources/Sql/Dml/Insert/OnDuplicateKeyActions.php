<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\SqlSerializable;
use function implode;

class OnDuplicateKeyActions implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode[] */
    private $expressions;

    /**
     * @param ExpressionNode[] $expressions (string $column => ExpressionNode $value)
     */
    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ON DUPLICATE KEY UPDATE ';

        $result .= implode(Arr::mapPairs($this->expressions, static function (string $column, ExpressionNode $expression) use ($formatter): string {
            return $formatter->formatName($column) . ' = ' . $expression->serialize($formatter);
        }));

        return $result;
    }

}
