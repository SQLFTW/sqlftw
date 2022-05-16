<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Server;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class DropSpatialReferenceSystemCommand implements ServerCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $srid;

    /** @var bool */
    private $ifExists;

    public function __construct(int $srid, bool $ifExists = false)
    {
        $this->srid = $srid;
        $this->ifExists = $ifExists;
    }

    public function getSrid(): string
    {
        return $this->srid;
    }

    public function getIfExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DROP SPATIAL REFERENCE SYSTEM ' . ($this->ifExists ? 'IF EXISTS ' : '') . $this->srid;
    }

}
