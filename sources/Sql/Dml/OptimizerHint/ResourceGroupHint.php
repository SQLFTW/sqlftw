<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\OptimizerHint;

use SqlFtw\Formatter\Formatter;

class ResourceGroupHint extends OptimizerHint
{

    public string $type = OptimizerHintType::RESOURCE_GROUP;

    public string $resourceGroup;

    public function __construct(string $resourceGroup)
    {
        $this->resourceGroup = $resourceGroup;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'RESOURCE_GROUP(' . $this->resourceGroup . ')';
    }

}
