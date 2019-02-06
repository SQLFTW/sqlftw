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

class SchemaWasDroppedException extends SchemaDoesNotExistException
{

    /** @var \SqlFtw\Reflection\SchemaReflection */
    private $reflection;

    public function __construct(SchemaReflection $reflection, ?\Throwable $previous = null)
    {
        ReflectionException::__construct(sprintf(
            'Schema `%s` was dropped by previous command.',
            $reflection->getName()
        ), $previous);

        $this->reflection = $reflection;
    }

    public function getReflection(): SchemaReflection
    {
        return $this->reflection;
    }

    public function getName(): string
    {
        return $this->reflection->getName();
    }

}
