<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Spatial;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\StatementImpl;

class CreateSpatialReferenceSystemCommand extends StatementImpl implements SpatialReferenceSystemCommand
{

    public int $srid;

    public string $name;

    public string $definition;

    public ?string $organization;

    public ?int $identifiedBy;

    public ?string $description;

    public bool $orReplace;

    public bool $ifNotExists;

    public function __construct(
        int $srid,
        string $name,
        string $definition,
        ?string $organization = null,
        ?int $identifiedBy = null,
        ?string $description = null,
        bool $orReplace = false,
        bool $ifNotExists = false
    ) {
        if ($orReplace && $ifNotExists) {
            throw new InvalidDefinitionException('OR REPLACE and IF NOT EXISTS can not be both set.');
        }
        if ($organization === null && $identifiedBy !== null) {
            throw new InvalidDefinitionException('ORGANIZATION must be set WHEN IDENTIFIED BY set.');
        }

        $this->srid = $srid;
        $this->name = $name;
        $this->definition = $definition;
        $this->organization = $organization;
        $this->identifiedBy = $identifiedBy;
        $this->description = $description;
        $this->orReplace = $orReplace;
        $this->ifNotExists = $ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->orReplace) {
            $result .= 'OR REPLACE ';
        }

        $result .= 'SPATIAL REFERENCE SYSTEM ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }

        $result .= $this->srid . ' NAME ' . $formatter->formatString($this->name);

        if ($this->organization !== null) {
            $result .= ' ORGANIZATION ' . $formatter->formatString($this->organization);
            if ($this->identifiedBy !== null) {
                $result .= ' IDENTIFIED BY ' . $this->identifiedBy;
            }
        }

        $result .= ' DEFINITION ' . $formatter->formatString($this->definition);

        if ($this->description !== null) {
            $result .= ' DESCRIPTION ' . $formatter->formatString($this->description);
        }

        return $result;
    }

}
