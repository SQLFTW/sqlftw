<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;

class WindowFrame implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var WindowFrameUnits */
    public $units;

    /** @var WindowFrameType */
    public $startType;

    /** @var WindowFrameType|null */
    public $endType;

    /** @var ExpressionNode|null */
    public $startExpression;

    /** @var ExpressionNode|null */
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

        $result .= $this->startExpression !== null
            ? $this->startExpression->serialize($formatter) . ' ' . $this->startType->serialize($formatter)
            : $this->startType->serialize($formatter);

        if ($this->endType !== null) {
            $result = 'BETWEEN ' . $result . ' AND ';
            $result .= $this->endExpression !== null
                ? $this->endType->serialize($formatter) . ' ' . $this->endType->serialize($formatter)
                : $this->endType->serialize($formatter);
        }

        return $result;
    }

}
