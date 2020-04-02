<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routines;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\DataType;
use SqlFtw\Sql\SqlSerializable;

class ProcedureParam implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var DataType */
    private $type;

    /** @var InOutParamFlag|null */
    private $inOutFlag;

    public function __construct(string $name, DataType $type, ?InOutParamFlag $inOutFlag = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->inOutFlag = $inOutFlag;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): DataType
    {
        return $this->type;
    }

    public function getInOutFlag(): ?InOutParamFlag
    {
        return $this->inOutFlag;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->inOutFlag !== null) {
            $result .= $this->inOutFlag->serialize($formatter) . ' ';
        }
        $result .= $formatter->formatName($this->name) . ' ' . $this->type->serialize($formatter);

        return $result;
    }

}
