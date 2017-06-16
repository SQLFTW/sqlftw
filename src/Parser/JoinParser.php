<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use SqlFtw\Sql\Dml\TableReference;

class JoinParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * table_references:
     *     escaped_table_reference [, escaped_table_reference] ...
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dml\TableReference[]
     */
    public function parseTableReferences(TokenList $tokenList): array
    {
        $references = [];
        do {
            $references[] = $this->parseTableReference($tokenList);
        } while ($tokenList->mayConsumeComma());

        return $references;
    }

    /**
     * escaped_table_reference:
     *     table_reference
     *   | { OJ table_reference }
     *
     * table_reference:
     *     table_factor
     *   | join_table
     *
     * table_factor:
     *     tbl_name [PARTITION (partition_names)] [[AS] alias] [index_hint_list]
     *   | table_subquery [AS] alias [(col_list)]
     *   | ( table_references )
     *
     * join_table:
     *     table_reference [INNER | CROSS] JOIN table_factor [join_condition]
     *   | table_reference STRAIGHT_JOIN table_factor
     *   | table_reference STRAIGHT_JOIN table_factor ON conditional_expr
     *   | table_reference {LEFT|RIGHT} [OUTER] JOIN table_reference join_condition
     *   | table_reference NATURAL [INNER | {LEFT|RIGHT} [OUTER]] JOIN table_factor
     *
     * join_condition:
     *     ON conditional_expr
     *   | USING (column_list)
     *
     * index_hint_list:
     *     index_hint [, index_hint] ...
     *
     * index_hint:
     *     USE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] ([index_list])
     *   | IGNORE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
     *   | FORCE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
     *
     * index_list:
     *     index_name [, index_name] ...
     */
    public function parseTableReference(TokenList $tokenList): TableReference
    {

    }

}
