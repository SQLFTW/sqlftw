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
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dml\Prepared\DeallocatePrepareCommand;
use SqlFtw\Sql\Dml\Prepared\ExecuteCommand;
use SqlFtw\Sql\Dml\Prepared\PrepareCommand;
use SqlFtw\Sql\Keyword;

class PreparedCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * {DEALLOCATE | DROP} PREPARE stmt_name
     */
    public function parseDeallocatePrepare(TokenList $tokenList): DeallocatePrepareCommand
    {
        $tokenList->expectAnyKeyword(Keyword::DEALLOCATE, Keyword::DROP);
        $tokenList->expectKeyword(Keyword::PREPARE);
        $name = $tokenList->expectName();
        $tokenList->expectEnd();

        return new DeallocatePrepareCommand($name);
    }

    /**
     * EXECUTE stmt_name
     *     [USING @var_name [, @var_name] ...]
     */
    public function parseExecute(TokenList $tokenList): ExecuteCommand
    {
        $tokenList->expectKeyword(Keyword::EXECUTE);
        $name = $tokenList->expectName();
        $variables = null;
        if ($tokenList->hasKeyword(Keyword::USING)) {
            $variables = [];
            do {
                /** @var string $variable */
                $variable = $tokenList->expect(TokenType::AT_VARIABLE)->value;
                $variables[] = $variable;
            } while ($tokenList->hasComma());
        }
        $tokenList->expectEnd();

        return new ExecuteCommand($name, $variables);
    }

    /**
     * PREPARE stmt_name FROM preparable_stmt
     */
    public function parsePrepare(TokenList $tokenList): PrepareCommand
    {
        $tokenList->expectKeyword(Keyword::PREPARE);
        $name = $tokenList->expectName();
        $tokenList->expectKeyword(Keyword::FROM);

        $variable = $tokenList->get(TokenType::AT_VARIABLE);
        if ($variable !== null) {
            /** @var string $statement */
            $statement = $variable->value;
        } else {
            $statement = $tokenList->expectString();
        }
        $tokenList->expectEnd();

        return new PrepareCommand($name, $statement);
    }

}
