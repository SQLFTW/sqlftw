<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Formatter\Formatter;

class QualifiedName implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $schema;

    public function __construct(string $name, ?string $schema = null)
    {
        $this->name = $name;
        $this->schema = $schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return [$this->name, $this->schema];
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->schema !== null
            ? $formatter->formatName($this->schema) . '.' . $formatter->formatName($this->name)
            : $formatter->formatName($this->name);
    }

}
