<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;

class ShowEngineCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Dal\Show\ShowEngineOption */
    private $option;

    public function __construct(string $name, ShowEngineOption $option)
    {
        $this->name = $name;
        $this->option = $option;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOption(): ShowEngineOption
    {
        return $this->option;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW ENGINE ' . $formatter->formatName($this->name) . ' ' . $this->option->serialize($formatter);
    }

}
