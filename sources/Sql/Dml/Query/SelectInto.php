<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Sql\SqlSerializable;

abstract class SelectInto implements SqlSerializable
{

    const POSITION_BEFORE_FROM = 1;
    const POSITION_BEFORE_LOCKING = 2;
    const POSITION_AFTER_LOCKING = 3;

    /** @var self::POSITION_* */
    protected $position;

    /**
     * @return self::POSITION_*
     */
    public function getPosition(): int
    {
        return $this->position;
    }

}
