<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Handler;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class HandlerCloseCommand implements HandlerCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $table;

    public function __construct(QualifiedName $table)
    {
        $this->table = $table;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'HANDLER ' . $this->table->serialize($formatter) . ' CLOSE';
    }

}
