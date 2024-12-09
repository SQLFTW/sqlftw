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
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\StatementImpl;
use function is_bool;
use function is_int;

/**
 * @phpstan-import-type TablespaceOptionValue from TablespaceOption
 */
class AlterTablespaceCommand extends StatementImpl implements TablespaceCommand
{

    public string $tablespace;

    /** @var array<TablespaceOption::*, TablespaceOptionValue> */
    public $options;

    public bool $undo;

    /**
     * @param array<TablespaceOption::*, TablespaceOptionValue> $options
     */
    public function __construct(string $tablespace, array $options, bool $undo = false)
    {
        TablespaceOption::validate(Keyword::ALTER, $options);

        $this->tablespace = $tablespace;
        $this->options = $options;
        $this->undo = $undo;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER ';
        if ($this->undo) {
            $result .= 'UNDO ';
        }
        $result .= 'TABLESPACE ' . $formatter->formatName($this->tablespace);

        foreach ($this->options as $name => $value) {
            if (is_bool($value)) {
                if ($name === TablespaceOption::WAIT) {
                    $result .= $value ? ' WAIT' : ' NO_WAIT';
                } elseif ($name === TablespaceOption::ENCRYPTION) {
                    $result .= ' ' . $name . ' ' . $formatter->formatString($value ? 'Y' : 'N');
                }
            } elseif (is_int($value)) {
                $result .= ' ' . $name . ' ' . $value;
            } elseif ($value instanceof SqlSerializable) {
                $result .= ' ' . $name . ' ' . $value->serialize($formatter);
            } elseif ($name === TablespaceOption::RENAME_TO) {
                $result .= ' ' . $name . ' ' . $formatter->formatName($value);
            } elseif ($name === TablespaceOption::SET) {
                $result .= ' ' . $name . ' ' . $value;
            } else {
                $result .= ' ' . $name . ' ' . $formatter->formatValue($value);
            }
        }

        return $result;
    }

}
