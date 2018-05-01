<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use SqlFtw\Sql\Keyword;

class EventState extends \SqlFtw\Sql\SqlEnum
{
    use \Dogma\StrictBehaviorMixin;

    public const ENABLE = Keyword::ENABLE;
    public const DISABLE = Keyword::DISABLE;
    public const DISABLE_ON_SLAVE = Keyword::DISABLE . ' ' . Keyword::ON . ' ' . Keyword::SLAVE;

}
