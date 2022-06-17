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
use SqlFtw\Sql\Statement;

class DropTablespaceCommand extends Statement implements TablespaceCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $engine;

    /** @var bool */
    private $undo;

    public function __construct(string $name, ?string $engine = null, bool $undo = false)
    {
        $this->name = $name;
        $this->engine = $engine;
        $this->undo = $undo;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP ';
        if ($this->undo) {
            $result .= 'UNDO ';
        }
        $result .= 'TABLESPACE ' . $formatter->formatName($this->name);

        if ($this->engine !== null) {
            $result .= ' ENGINE ' . $formatter->formatName($this->engine);
        }

        return $result;
    }

}
