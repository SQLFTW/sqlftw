<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class ShowCreateEventCommand extends ShowCommand
{

    public ObjectIdentifier $event;

    public function __construct(ObjectIdentifier $event)
    {
        $this->event = $event;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW CREATE EVENT ' . $this->event->serialize($formatter);
    }

}
