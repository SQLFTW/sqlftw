<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Node;

class FileFormat extends Node
{

    public ?string $fieldsTerminatedBy;

    public ?string $fieldsEnclosedBy;

    public ?string $fieldsEscapedBy;

    public bool $optionallyEnclosed;

    public ?string $linesStaringBy;

    public ?string $linesTerminatedBy;

    public function __construct(
        ?string $fieldsTerminatedBy = null,
        ?string $fieldsEnclosedBy = null,
        ?string $fieldsEscapedBy = null,
        bool $optionallyEnclosed = false,
        ?string $linesStaringBy = null,
        ?string $linesTerminatedBy = null
    ) {
        $this->fieldsTerminatedBy = $fieldsTerminatedBy;
        $this->fieldsEnclosedBy = $fieldsEnclosedBy;
        $this->fieldsEscapedBy = $fieldsEscapedBy;
        $this->optionallyEnclosed = $optionallyEnclosed;
        $this->linesStaringBy = $linesStaringBy;
        $this->linesTerminatedBy = $linesTerminatedBy;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->fieldsTerminatedBy !== null || $this->fieldsEnclosedBy !== null || $this->fieldsEscapedBy !== null) {
            $result .= ' FIELDS';
            if ($this->fieldsTerminatedBy !== null) {
                $result .= ' TERMINATED BY ' . $formatter->formatStringForceEscapeWhitespace($this->fieldsTerminatedBy);
            }
            if ($this->fieldsEnclosedBy !== null) {
                if ($this->optionallyEnclosed) {
                    $result .= ' OPTIONALLY';
                }
                $result .= ' ENCLOSED BY ' . $formatter->formatStringForceEscapeWhitespace($this->fieldsEnclosedBy);
            }
            if ($this->fieldsEscapedBy !== null) {
                $result .= ' ESCAPED BY ' . $formatter->formatStringForceEscapeWhitespace($this->fieldsEscapedBy);
            }
        }
        if ($this->linesStaringBy !== null || $this->linesTerminatedBy !== null) {
            $result .= ' LINES';
            if ($this->linesStaringBy !== null) {
                $result .= ' STARTING BY ' . $formatter->formatStringForceEscapeWhitespace($this->linesStaringBy);
            }
            if ($this->linesTerminatedBy !== null) {
                $result .= ' TERMINATED BY ' . $formatter->formatStringForceEscapeWhitespace($this->linesTerminatedBy);
            }
        }

        return $result;
    }

}
