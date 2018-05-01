<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

class ViewWasDroppedException extends ViewDoesNotExistException
{

    /** @var \SqlFtw\Reflection\ViewReflection */
    private $reflection;

    public function __construct(ViewReflection $reflection, ?\Throwable $previous = null)
    {
        $name = $reflection->getName();

        ReflectionException::__construct(sprintf(
            'View `%s`.`%s` was dropped by previous command.',
            $name->getSchema(),
            $name->getName()
        ), $previous);

        $this->reflection = $reflection;
    }

    public function getReflection(): ViewReflection
    {
        return $this->reflection;
    }

    public function getName(): string
    {
        return $this->reflection->getName()->getName();
    }

    public function getSchema(): string
    {
        return $this->reflection->getName()->getSchema();
    }

}
