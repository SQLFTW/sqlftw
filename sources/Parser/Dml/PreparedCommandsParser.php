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
        $tokenList->consumeAnyKeyword(Keyword::DEALLOCATE, Keyword::DROP);
        $tokenList->consumeKeyword(Keyword::PREPARE);
        $name = $tokenList->consumeName();
        $tokenList->expectEnd();

        return new DeallocatePrepareCommand($name);
    }

    /**
     * EXECUTE stmt_name
     *     [USING @var_name [, @var_name] ...]
     */
    public function parseExecute(TokenList $tokenList): ExecuteCommand
    {
        $tokenList->consumeKeyword(Keyword::EXECUTE);
        $name = $tokenList->consumeName();
        $variables = null;
        if ($tokenList->mayConsumeKeyword(Keyword::USING)) {
            $variables = [];
            do {
                /** @var string $variable */
                $variable = $tokenList->consume(TokenType::AT_VARIABLE)->value;
                $variables[] = $variable;
            } while ($tokenList->mayConsumeComma());
        }
        $tokenList->expectEnd();

        return new ExecuteCommand($name, $variables);
    }

    /**
     * PREPARE stmt_name FROM preparable_stmt
     */
    public function parsePrepare(TokenList $tokenList): PrepareCommand
    {
        $tokenList->consumeKeyword(Keyword::PREPARE);
        $name = $tokenList->consumeName();
        $tokenList->consumeKeyword(Keyword::FROM);

        $variable = $tokenList->mayConsume(TokenType::AT_VARIABLE);
        if ($variable !== null) {
            /** @var string $statement */
            $statement = $variable->value;
        } else {
            $statement = $tokenList->consumeString();
        }
        $tokenList->expectEnd();

        return new PrepareCommand($name, $statement);
    }

}
