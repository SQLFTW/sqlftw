<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Routines;

use SqlFtw\Sql\Ddl\Routines\UdfReturnDataType;
use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\SqlFormatter\SqlFormatter;

class CreateFunctionSonameCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\QualifiedName */
    private $name;

    /** @var string */
    private $libName;

    /** @var \SqlFtw\Sql\Ddl\Routines\UdfReturnDataType */
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

    public function serialize(SqlFormatter $formatter): string
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
