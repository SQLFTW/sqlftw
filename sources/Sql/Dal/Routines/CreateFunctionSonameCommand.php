<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Routine;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Ddl\Routine\UdfReturnDataType;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class CreateFunctionSonameCommand extends Statement implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var string */
    private $libName;

    /** @var UdfReturnDataType */
    private $returnType;

    /** @var bool */
    private $aggregate;

    public function __construct(QualifiedName $name, string $libName, UdfReturnDataType $returnType, bool $aggregate)
    {
        $this->name = $name;
        $this->libName = $libName;
        $this->returnType = $returnType;
        $this->aggregate = $aggregate;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getLibName(): string
    {
        return $this->libName;
    }

    public function getReturnType(): UdfReturnDataType
    {
        return $this->returnType;
    }

    public function isAggregate(): bool
    {
        return $this->aggregate;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->aggregate) {
            $result .= 'AGGREGATE ';
        }

        $result .= 'FUNCTION ' . $this->name->serialize($formatter)
            . ' RETURNS ' . $this->returnType->serialize($formatter)
            . ' SONAME ' . $formatter->formatString($this->libName);

        return $result;
    }

}
