<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dml\Call\CallCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class CallCommandParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * CALL sp_name([parameter[, ...]])
     *
     * CALL sp_name[()]
     */
    public function parseCall(TokenList $tokenList): CallCommand
    {
        $tokenList->expectKeyword(Keyword::CALL);

        $name = new QualifiedName(...$tokenList->expectQualifiedName());
        $params = null;
        if ($tokenList->hasSymbol('(')) {
            $params = [];
            if (!$tokenList->hasSymbol(')')) {
                do {
                    $params[] = $this->expressionParser->parseExpression($tokenList);
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }
        }

        return new CallCommand($name, $params);
    }

}
