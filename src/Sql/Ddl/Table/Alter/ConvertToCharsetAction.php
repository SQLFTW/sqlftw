<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\Sql\Charset;
use SqlFtw\SqlFormatter\SqlFormatter;

class ConvertToCharsetAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Charset */
    private $charset;

    /** @var string|null */
    private $collation;

    public function __construct(Charset $charset, ?string $collation)
    {
        $this->charset = $charset;
        $this->collation = $collation;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::CONVERT_TO_CHARACTER_SET);
    }

    public function getCharset(): Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'CONVERT TO CHARACTER SET ' . $this->charset->serialize($formatter);
        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $formatter->formatString($this->collation);
        }

        return $result;
    }

}
