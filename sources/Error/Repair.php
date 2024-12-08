<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Error;

final class Repair
{

    public const YES = 1; // do repair / repaired
    public const NO = 2; // do not repair / cannot repair
    public const POSSIBLE = 3; // -- / can repair
    public const PARTIAL = 4; // -- / repaired partially (some rules)

}
