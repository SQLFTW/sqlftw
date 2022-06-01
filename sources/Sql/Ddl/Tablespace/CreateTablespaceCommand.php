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
use function assert;
use function is_bool;
use function is_string;

/**
 * @phpstan-import-type TablespaceOptionValue from TablespaceOption
 */
class CreateTablespaceCommand implements TablespaceCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var array<string, TablespaceOptionValue> */
    private $options;

    /** @var bool */
    private $undo;

    /**
     * @param array<string, TablespaceOptionValue> $options
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
     * @return array<string, TablespaceOptionValue>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isUndo(): bool
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
                assert(is_bool($value));
                $result .= $value ? ' WAIT' : ' NO_WAIT';
            } elseif ($name === TablespaceOption::ENGINE || $name === TablespaceOption::USE_LOGFILE_GROUP) {
                assert(is_string($value));
                $result .= ' ' . $name . ' ' . $formatter->formatName($value);
            } elseif ($name === TablespaceOption::FILE_BLOCK_SIZE) {
                $result .= ' ' . $name . ' = ' . $formatter->formatValue($value);
            } elseif ($name === TablespaceOption::ENCRYPTION) {
                assert(is_bool($value));
                $result .= ' ' . $name . ' ' . $formatter->formatString($value ? 'Y' : 'N');
            } else {
                $result .= ' ' . $name . ' ' . $formatter->formatValue($value);
            }
        }

        return $result;
    }

}
