<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Throwable;
use function sprintf;

class TriggerAlreadyExistsException extends ReflectionException
{

    /** @var string */
    private $name;

    /** @var string */
    private $schema;

    public function __construct(string $name, string $schema, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Trigger `%s`.`%s` already exists.', $schema, $name), $previous);

        $this->name = $name;
        $this->schema = $schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

}
