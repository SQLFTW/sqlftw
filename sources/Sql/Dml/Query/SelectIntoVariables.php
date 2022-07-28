<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\UserVariable;

class SelectIntoVariables implements SelectInto
{

    /** @var non-empty-array<UserVariable|SimpleName> */
    private $variables;

    /**
     * @param non-empty-array<UserVariable|SimpleName> $variables
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @return non-empty-array<UserVariable|SimpleName>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INTO ' . $formatter->formatSerializablesList($this->variables);
    }

}
