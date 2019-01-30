<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

class TriggerWasDroppedException extends TriggerDoesNotExistException
{

    /** @var \SqlFtw\Reflection\TriggerReflection */
    private $reflection;

    public function __construct(TriggerReflection $reflection, ?\Throwable $previous = null)
    {
        $name = $reflection->getName()->getName();
        $schema = $reflection->getName()->getSchema();

        parent::__construct($name, $schema, $previous);

        $this ->message = sprintf('Trigger `%s`.`%s` was dropped by previous command.', $schema, $name);
        $this->reflection = $reflection;
    }

    public function getReflection(): TriggerReflection
    {
        return $this->reflection;
    }

}
