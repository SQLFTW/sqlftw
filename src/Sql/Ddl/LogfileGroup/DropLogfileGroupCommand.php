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

class DropLogfileGroupCommand implements LogfileGroupCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $engine;

    public function __construct(string $name, string $engine)
    {
        $this->name = $name;
        $this->engine = $engine;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DROP LOGFILE GROUP ' . $formatter->formatName($this->name) . ' ENGINE = ' . $formatter->formatName($this->engine);
    }

}
