<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class DropTriggerCommand extends Command implements TriggerCommand
{

    public ObjectIdentifier $trigger;

    public bool $ifExists;

    public function __construct(ObjectIdentifier $trigger, bool $ifExists = false)
    {
        $this->trigger = $trigger;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP TRIGGER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $this->trigger->serialize($formatter);

        return $result;
    }

}
