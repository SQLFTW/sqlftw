<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use SqlFtw\Formatter\Formatter;

class DiagnosticsItem implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $target;

    /** @var \SqlFtw\Sql\Ddl\Compound\InformationItem */
    private $item;

    public function __construct(string $target, InformationItem $item)
    {
        $this->target = $target;
        $this->item = $item;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getItem(): InformationItem
    {
        return $this->item;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatName($this->target) . ' = ' . $this->item->serialize($formatter);
    }

}
