<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use function sprintf;

class SchemaDoesNotExistException extends ReflectionException
{

    /** @var string */
    private $name;

    public function __construct(string $name, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Schema `%s` does not exist.', $name), $previous);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
