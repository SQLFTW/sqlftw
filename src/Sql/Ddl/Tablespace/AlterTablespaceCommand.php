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

class AlterTablespaceCommand implements TablespaceCommand
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
        TablespaceOption::validate(Keyword::ALTER, $options);

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
        $result = 'ALTER ';
        if ($this->undo) {
            $result .= 'UNDO ';
        }
        $result .= 'TABLESPACE ' . $formatter->formatName($this->name);

        foreach ($this->options as $name => $value) {
            if ($name === TablespaceOption::WAIT) {
                $result .= $value ? "\n    " . $name : '';
            } elseif ($name === TablespaceOption::ENGINE || $name === TablespaceOption::RENAME_TO) {
                $result .= "\n    " . $name . ' ' . $formatter->formatName($value);
            } else {
                $result .= "\n    " . $name . ' ' . $formatter->formatValue($value);
            }
        }

        return $result;
    }

}
