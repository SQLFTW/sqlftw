<?php
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
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;

/**
 * Interface for SELECT, TABLE and VALUES commands, QueryExpression (UNION|EXCEPT|INTERSECT) and ParenthesizedQueryExpression
 *
 * @property non-empty-list<OrderByExpression>|null $orderBy
 * @property int|SimpleName|Placeholder|null $limit
 * @property int|SimpleName|Placeholder|null $offset
 * @property ?SelectInto $into
 */
interface Query extends DmlCommand
{

}
