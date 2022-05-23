<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\LogfileGroup;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;

class DropLogfileGroupCommand implements LogfileGroupCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var StorageEngine|null */
    private $engine;

    public function __construct(string $name, ?StorageEngine $engine)
    {
        $this->name = $name;
        $this->engine = $engine;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): ?StorageEngine
    {
        return $this->engine;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP LOGFILE GROUP ' . $formatter->formatName($this->name);
        if ($this->engine !== null) {
            $result .= ' ENGINE = ' . $this->engine->serialize($formatter);
        }

        return $result;
    }

}
