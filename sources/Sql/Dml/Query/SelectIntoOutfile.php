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
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\FileFormat;

class SelectIntoOutfile extends SelectInto
{

    public string $fileName;

    public ?Charset $charset;

    public ?FileFormat $format;

    /**
     * @param SelectInto::POSITION_* $position
     */
    public function __construct(string $fileName, ?Charset $charset = null, ?FileFormat $format = null, int $position = self::POSITION_AFTER_LOCKING)
    {
        $this->fileName = $fileName;
        $this->charset = $charset;
        $this->format = $format;
        $this->position = $position;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INTO OUTFILE ' . $formatter->formatString($this->fileName);
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }
        if ($this->format !== null) {
            $result .= ' ' . $this->format->serialize($formatter);
        }

        return $result;
    }

}
