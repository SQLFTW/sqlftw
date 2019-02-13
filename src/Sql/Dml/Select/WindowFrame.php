<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;

class WindowFrame implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\Select\WindowFrameUnits */
    public $units;

    /** @var \SqlFtw\Sql\Dml\Select\WindowFrameType */
    public $startType;

    /** @var \SqlFtw\Sql\Dml\Select\WindowFrameType|null */
    public $endType;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    public $startExpression;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    public $endExpression;

    public function __construct(
        WindowFrameUnits $units,
        WindowFrameType $startType,
        ?WindowFrameType $endType,
        ?ExpressionNode $startExpression,
        ?ExpressionNode $endExpression
    )
    {
        if ($startType->equalsAny(WindowFrameType::PRECEDING, WindowFrameType::FOLLOWING) xor $startExpression !== null) {
            throw new InvalidDefinitionException('Expression must be provided if and only if frame start type is PRECEDING or FOLLOWING.');
        }

        if (($endType !== null && $endType->equalsAny(WindowFrameType::PRECEDING, WindowFrameType::FOLLOWING)) xor $endExpression !== null) {
            throw new InvalidDefinitionException('Expression must be provided if and only if frame end type is PRECEDING or FOLLOWING.');
        }

        $this->units = $units;
        $this->startType = $startType;
        $this->endType = $endType;
        $this->startExpression = $startExpression;
        $this->endExpression = $endExpression;
    }

    public function getUnits(): WindowFrameUnits
    {
        return $this->units;
    }

    public function getStartType(): WindowFrameType
    {
        return $this->startType;
    }

    public function getEndType(): ?WindowFrameType
    {
        return $this->endType;
    }

    public function getStartExpression(): ?ExpressionNode
    {
        return $this->startExpression;
    }

    public function getEndExpression(): ?ExpressionNode
    {
        return $this->endExpression;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->units->serialize($formatter) . ' ';

        $result .= $this->startExpression
            ? $this->startExpression->serialize($formatter) . ' ' . $this->startType->serialize($formatter)
            : $this->startType->serialize($formatter);

        if ($this->endType !== null) {
            $result = 'BETWEEN ' . $result . ' AND ';
            $result .= $this->endExpression
                ? $this->endType->serialize($formatter) . ' ' . $this->endType->serialize($formatter)
                : $this->endType->serialize($formatter);
        }

        return $result;
    }

}
