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
use SqlFtw\Sql\Expression\StringValue;

class AlterAuthOption extends AuthOption implements AlterUserAction
{

    public ?string $replace;

    public bool $retainCurrentPassword;

    /**
     * @param StringValue|false|null $password
     */
    public function __construct(
        ?string $authPlugin,
        $password = null,
        ?StringValue $as = null,
        ?string $replace = null,
        bool $retainCurrentPassword = false,
        bool $oldHashedPassword = false
    ) {
        parent::__construct($authPlugin, $password, $as, null, $oldHashedPassword);

        $this->replace = $replace;
        $this->retainCurrentPassword = $retainCurrentPassword;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = parent::serialize($formatter);

        if ($this->replace !== null) {
            $result .= ' REPLACE ' . $formatter->formatString($this->replace);
        }
        if ($this->retainCurrentPassword) {
            $result .= ' RETAIN CURRENT PASSWORD';
        }

        return $result;
    }

}
