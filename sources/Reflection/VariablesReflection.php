<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\StrictBehaviorMixin;

class VariablesReflection
{
    use StrictBehaviorMixin;

    /** @var array<string, mixed> */
    private $variables;

    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @return mixed|null
     */
    public function get(string $name)
    {
        return $this->variables[$name] ?? null;
    }

}
