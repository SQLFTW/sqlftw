<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use Dogma\StaticClassMixin;

final class AutoRepair
{
    use StaticClassMixin;

    public const NOT_POSSIBLE = null;
    public const POSSIBLE = false;
    public const REPAIRED = true;

}
