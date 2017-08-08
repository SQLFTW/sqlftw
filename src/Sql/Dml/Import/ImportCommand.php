<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Import;

use SqlFtw\Formatter\Formatter;

class ImportCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string[] */
    private $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'IMPORT TABLES FROM ' . $formatter->formatStringList($this->files);
    }

}
