<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Reset\ResetPersistCommand;
use SqlFtw\Sql\Keyword;

class ResetPersistCommandParser
{
    use StrictBehaviorMixin;

    /**
     * RESET PERSIST [[IF EXISTS] system_var_name]
     *
     * @param TokenList $tokenList
     * @return ResetPersistCommand
     */
    public function parseResetPersist(TokenList $tokenList): ResetPersistCommand
    {
        $tokenList->consumeKeywords(Keyword::RESET, Keyword::PERSIST);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        // todo: platform variables
        if ($ifExists) {
            $variable = $tokenList->consumeName();
        } else {
            $variable = $tokenList->mayConsumeName();
        }
        $tokenList->expectEnd();

        return new ResetPersistCommand($variable, $ifExists);
    }

}
