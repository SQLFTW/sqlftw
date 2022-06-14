<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Features;

use Dogma\StrictBehaviorMixin;

abstract class FeaturesList
{
    use StrictBehaviorMixin;

    protected const MIN = 10000;
    protected const MAX = 999999;

    /** @var array<array{string, int, int}> */
    public $features = [];

    /** @var array<array{string, int, int}> */
    public $reserved = [];

    /** @var array<array{string, int, int}> */
    public $nonReserved = [];

    /** @var array<array{string, int, int}> */
    public $operators = [];

    /** @var array<array{string, int, int}> */
    public $types = [];

    /** @var array<array{string, int, int, 3?: int}> */
    public $functions = [];

    /** @var array<array{string, int, int}> */
    public $variables = [];

}
