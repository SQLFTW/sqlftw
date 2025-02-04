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

class AlterAuthOption extends AlterUserAction
{

    public AuthOption $authOption;

    public ?string $replace;

    public bool $retainCurrentPassword;

    public function __construct(
        AuthOption $authOption,
        ?string $replace = null,
        bool $retainCurrentPassword = false
    ) {
        $this->authOption = $authOption;
        $this->replace = $replace;
        $this->retainCurrentPassword = $retainCurrentPassword;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->authOption->serialize($formatter);

        if ($this->replace !== null) {
            $result .= ' REPLACE ' . $formatter->formatString($this->replace);
        }
        if ($this->retainCurrentPassword) {
            $result .= ' RETAIN CURRENT PASSWORD';
        }

        return $result;
    }

}
