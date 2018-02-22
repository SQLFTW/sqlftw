<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dml\Import\ImportCommand;
use SqlFtw\Sql\Keyword;

class ImportCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * IMPORT TABLE FROM sdi_file [, sdi_file] ...
     */
    public function parseImport(TokenList $tokenList): ImportCommand
    {
        $tokenList->consumeKeywords(Keyword::IMPORT, Keyword::TABLE, Keyword::FROM);

        $files = [];
        do {
            $files[] = $tokenList->consumeString();
        } while ($tokenList->mayConsumeComma());

        return new ImportCommand($files);
    }

}
