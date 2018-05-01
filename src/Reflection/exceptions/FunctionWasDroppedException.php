<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

class FunctionWasDroppedException extends \SqlFtw\Reflection\FunctionDoesNotExistException
{

    /** @var \SqlFtw\Reflection\FunctionReflection */
    private $reflection;

    public function __construct(FunctionReflection $reflection, ?\Throwable $previous = null)
    {
        $name = $reflection->getName();

        ReflectionException::__construct(sprintf(
            'Function `%s`.`%s` was dropped by previous command.',
            $name->getSchema(),
            $name->getName()
        ), $previous);

        $this->reflection = $reflection;
    }

    public function getReflection(): FunctionReflection
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
