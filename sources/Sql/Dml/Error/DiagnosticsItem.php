<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Error;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\IdentifierInterface;
use SqlFtw\Sql\Node;

class DiagnosticsItem extends Node
{

    public IdentifierInterface $target;

    public InformationItem $item;

    public function __construct(IdentifierInterface $target, InformationItem $item)
    {
        $this->target = $target;
        $this->item = $item;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->target->serialize($formatter) . ' = ' . $this->item->serialize($formatter);
    }

}
