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

class ForeignKeyDoesNotExistException extends ReflectionException
{

    /** @var string */
    private $name;

    /** @var string */
    private $table;

    /** @var string */
    private $schema;

    public function __construct(string $name, string $table, string $schema, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Foreign key constraint `%s` in table `%s`.`%s` does not exist.', $name, $schema, $table), $previous);

        $this->name = $name;
        $this->table = $table;
        $this->schema = $schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

}
