<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Tablespace;

use SqlFtw\Formatter\Formatter;

class DropTablespaceCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $engine;

    public function __construct(string $name, ?string $engine = null)
    {
        $this->name = $name;
        $this->engine = $engine;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP TABLESPACE ' . $formatter->formatName($this->name);
        if ($this->engine !== null) {
            $result .= 'ENGINE = ' . $formatter->formatName($this->engine);
        }

        return $result;
    }

}
