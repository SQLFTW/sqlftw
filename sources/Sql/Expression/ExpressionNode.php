<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\SqlSerializable;

/**
 * - If you can't change anything, because it's already happened, you may as well smell the flowers.
 * - What flowers?
 * - That's an expression.
 *
 * ExpressionNode hierarchy:
 *   - CaseExpression - CASE x THEN y ELSE z END
 *   - CollateExpression - expression COLLATE collation
 *   - CurlyExpression - {identifier expr}
 *   - DataType - e.g. CAST(expr AS type)
 *   - ExistsExpression - EXISTS (SELECT ...)
 *   - FunctionCall - e.g. AVG([DISTINCT] x) OVER ...
 *   - Identifier - e.g. `name`, @name, *
 *   - ListExpression - ..., ..., ...
 *   - Literal:
 *     - BinaryLiteral - e.g. 0b001101110
 *     - HexadecimalLiteral - e.g. 0x001F
 *     - IntervalLiteral - e.g. INTERVAL 6 DAYS
 *     - KeywordLiteral - e.g. DEFAULT, UNKNOWN, ON, OFF...
 *     - ValueLiteral - e.g. 1, 1.23, 'string', true, false, null...
 *   - MatchExpression - MATCH x AGAINST y
 *   - OperatorExpression:
 *     - BinaryOperator - e.g. x + y
 *     - TernaryOperator - e.g. x BETWEEN y AND z
 *     - UnaryOperator - e.g. NOT x
 *   - OrderByExpression - {col_name | expr | position} [ASC | DESC]
 *   - Parentheses - (...)
 *   - Placeholder - ?
 *   - RowExpression - ROW (...[, ...])
 *   - Subquery - (SELECT ...)
 */
interface ExpressionNode extends SqlSerializable
{

}
