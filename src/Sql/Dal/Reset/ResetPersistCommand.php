<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Reset;

use SqlFtw\SqlFormatter\SqlFormatter;

class ResetPersistCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $variable;

    /** @var bool */
    private $ifExists;

    public function __construct(string $variable, bool $ifExists = false)
    {
        $this->variable = $variable;
        $this->ifExists = $ifExists;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'RESET PERSIST ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatName($this->variable);

        return $result;
    }

}
