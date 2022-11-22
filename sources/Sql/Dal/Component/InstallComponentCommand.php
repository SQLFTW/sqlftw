<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Component;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class InstallComponentCommand extends Statement implements ComponentCommand
{

    /** @var non-empty-list<string> */
    private $components;

    /**
     * @param non-empty-list<string> $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
    }

    /**
     * @return non-empty-list<string>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INSTALL COMPONENT ' . $formatter->formatNamesList($this->components);
    }

}
