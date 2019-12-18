<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Tablespace;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Keyword;

class CreateTablespaceCommand implements TablespaceCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var mixed[] */
    private $options;

    /** @var bool */
    private $undo;

    /**
     * @param string $name
     * @param mixed[] $options
     * @param bool $undo
     */
    public function __construct(string $name, array $options, bool $undo = false)
    {
        TablespaceOption::validate(Keyword::CREATE, $options);

        $this->name = $name;
        $this->options = $options;
        $this->undo = $undo;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getUndo(): bool
    {
        return $this->undo;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->undo) {
            $result .= 'UNDO ';
        }
        $result .= 'TABLESPACE ' . $formatter->formatName($this->name);

        foreach ($this->options as $name => $value) {
            if ($name === TablespaceOption::WAIT) {
                $result .= $value ? ' ' . $name : '';
            } elseif ($name === TablespaceOption::ENGINE || $name === TablespaceOption::USE_LOGFILE_GROUP) {
                $result .= ' ' . $name . ' ' . $formatter->formatName($value);
            } elseif ($name === TablespaceOption::FILE_BLOCK_SIZE) {
                $result .= ' ' . $name . ' = ' . $formatter->formatValue($value);
            } elseif ($name === TablespaceOption::ENCRYPTION) {
                $result .= ' ' . $name . ' ' . $formatter->formatString($value ? 'Y' : 'N');
            } else {
                $result .= ' ' . $name . ' ' . $formatter->formatValue($value);
            }
        }

        return $result;
    }

}
