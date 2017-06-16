<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Names;

use SqlFtw\SqlFormatter\SqlFormatter;

class QualifiedName implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $databaseName;

    public function __construct(string $name, ?string $databaseName = null)
    {
        $this->name = $name;
        $this->databaseName = $databaseName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return $this->databaseName !== null
            ? $formatter->formatName($this->databaseName) . '.' . $formatter->formatName($this->name)
            : $formatter->formatName($this->name);
    }

}
