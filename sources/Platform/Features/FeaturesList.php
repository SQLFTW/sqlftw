<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Features;

use SqlFtw\Sql\EntityType;

abstract class FeaturesList
{

    protected const MIN = 10000;
    protected const MAX = 999999;

    /** @var list<array{string, int, int}> */
    public $features = [];

    /** @var list<array{string, int, int}> */
    public $reserved = [];

    /** @var list<array{string, int, int}> */
    public $nonReserved = [];

    /** @var list<array{string, int, int}> */
    public $operators = [];

    /** @var list<array{string, int, int}> */
    public $types = [];

    /** @var list<array{string, int, int, 3?: int}> */
    public $functions = [];

    /** @var list<array{string, int, int}> */
    public $variables = [];

    /** @var list<array{class-string, int, int}> */
    public $preparableCommands = [];

    /** @var array<EntityType::*, int> */
    public $maxLengths = [];

}
