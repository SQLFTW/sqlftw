<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Reset;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;

class ResetPersistCommand implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $variable;

    /** @var bool */
    private $ifExists;

    public function __construct(?string $variable, bool $ifExists = false)
    {
        $this->variable = $variable;
        $this->ifExists = $ifExists;
    }

    public function getVariable(): ?string
    {
        return $this->variable;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RESET PERSIST';
        if ($this->ifExists) {
            $result .= ' IF EXISTS';
        }
        if ($this->variable !== null) {
            $result .= ' ' . $formatter->formatName($this->variable);
        }

        return $result;
    }

}
