<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Reset\ResetPersistCommand;
use SqlFtw\Sql\Keyword;

class ResetPersistCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * RESET PERSIST [[IF EXISTS] system_var_name]
     */
    public function parseResetPersist(TokenList $tokenList): ResetPersistCommand
    {
        $tokenList->consumeKeywords(Keyword::RESET, Keyword::PERSIST);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        if ($ifExists) {
            $variable = $tokenList->consumeName();
        } else {
            $variable = $tokenList->mayConsume(TokenType::NAME);
        }

        return new ResetPersistCommand($variable->value, $ifExists);
    }

}
