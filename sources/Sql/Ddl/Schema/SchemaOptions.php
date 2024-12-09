<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Schema;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\Table\Option\ThreeStateValue;
use SqlFtw\Sql\SqlSerializable;
use function implode;

class SchemaOptions implements SqlSerializable
{

    public ?Charset $charset;

    public ?Collation $collation;

    public ?bool $encryption;

    public ?ThreeStateValue $readOnly;

    public function __construct(
        ?Charset $charset = null,
        ?Collation $collation = null,
        ?bool $encryption = null,
        ?ThreeStateValue $readOnly = null
    )
    {
        $this->charset = $charset;
        $this->collation = $collation;
        $this->encryption = $encryption;
        $this->readOnly = $readOnly;
    }

    public function serialize(Formatter $formatter): string
    {
        $parts = [];
        if ($this->charset !== null) {
            $parts[] = 'CHARACTER SET ' . $this->charset->serialize($formatter);
        }
        if ($this->collation !== null) {
            $parts[] = 'COLLATE ' . $this->collation->serialize($formatter);
        }
        if ($this->encryption !== null) {
            $parts[] = 'ENCRYPTION ' . ($this->encryption ? "'Y'" : "'N'");
        }
        if ($this->readOnly !== null) {
            $parts[] = 'READ ONLY ' . $this->readOnly->serialize($formatter);
        }

        return implode(' ', $parts);
    }

}
