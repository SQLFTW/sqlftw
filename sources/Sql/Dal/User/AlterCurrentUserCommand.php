<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\StatementImpl;

class AlterCurrentUserCommand extends StatementImpl implements UserCommand
{

    public ?AuthOption $option;

    public ?string $replace;

    public bool $retainCurrentPassword;

    public bool $discardOldPassword;

    public bool $ifExists;

    public function __construct(
        ?AuthOption $option,
        ?string $replace = null,
        bool $retainCurrentPassword = false,
        bool $discardOldPassword = false,
        bool $ifExists = false
    ) {
        $this->option = $option;
        $this->replace = $replace;
        $this->retainCurrentPassword = $retainCurrentPassword;
        $this->discardOldPassword = $discardOldPassword;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER USER ' . ($this->ifExists ? 'IF EXISTS ' : '') . 'USER()';

        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }
        if ($this->replace !== null) {
            $result .= ' REPLACE ' . $formatter->formatString($this->replace);
        }
        if ($this->retainCurrentPassword) {
            $result .= ' RETAIN CURRENT PASSWORD';
        }
        if ($this->discardOldPassword) {
            $result .= ' DISCARD OLD PASSWORD';
        }

        return $result;
    }

}
