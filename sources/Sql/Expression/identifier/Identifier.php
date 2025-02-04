<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

/**
 * e.g. `name`, @name, *
 */
abstract class Identifier extends RootNode implements IdentifierInterface
{

    public string $name; // @phpstan-ignore property.uninitialized

    abstract public function getFullName(): string;

}
