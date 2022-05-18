<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Expression\OrderByExpression;

/**
 * Interface for SELECT, TABLE and VALUES commands, UnionExpression and ParenthesizedQueryExpression
 */
interface Query extends DmlCommand
{

    /**
     * @return non-empty-array<OrderByExpression>|null
     */
    public function getOrderBy(): ?array;

    public function getLimit(): ?int;

    public function getInto(): ?SelectInto;

}
