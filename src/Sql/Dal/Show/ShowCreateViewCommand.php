<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\SqlFormatter\SqlFormatter;

class ShowCreateViewCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var \SqlFtw\Sql\Names\QualifiedName */
    private $name;

    public function __construct(QualifiedName $name)
    {
        $this->name = $name;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'SHOW CREATE VIEW ' . $this->name->serialize($formatter);
    }

}
