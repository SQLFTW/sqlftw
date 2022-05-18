<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Import;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;

class ImportCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $files;

    /**
     * @param non-empty-array<string> $files
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'IMPORT TABLE FROM ' . $formatter->formatStringList($this->files);
    }

}
