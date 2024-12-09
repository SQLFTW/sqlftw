<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;

class SelectIntoDumpfile implements SelectInto
{

    public string $fileName;

    /** @var self::POSITION_* */
    public int $position;

    /**
     * @param SelectInto::POSITION_* $position
     */
    public function __construct(string $fileName, int $position = self::POSITION_AFTER_LOCKING)
    {
        $this->fileName = $fileName;
        $this->position = $position;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INTO DUMPFILE ' . $formatter->formatString($this->fileName);
    }

}
