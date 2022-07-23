<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Schema;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class AlterSchemaCommand extends Statement implements SchemaCommand
{

    /** @var string|null */
    private $name;

    /** @var SchemaOptions */
    private $options;

    public function __construct(?string $name, SchemaOptions $options)
    {
        $this->name = $name;
        $this->options = $options;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getOptions(): SchemaOptions
    {
        return $this->options;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER SCHEMA';
        if ($this->name !== null) {
            $result .= ' ' . $formatter->formatName($this->name);
        }

        return $result . ' ' . $this->options->serialize($formatter);
    }

}
