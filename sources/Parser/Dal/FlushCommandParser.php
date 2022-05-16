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
use SqlFtw\Sql\Dal\Flush\FlushCommand;
use SqlFtw\Sql\Dal\Flush\FlushOption;
use SqlFtw\Sql\Dal\Flush\FlushTablesCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class FlushCommandParser
{
    use StrictBehaviorMixin;

    /**
     * FLUSH [NO_WRITE_TO_BINLOG | LOCAL]
     *     flush_option [, flush_option] ...
     *
     * flush_option: {
     *     BINARY LOGS
     *   | ENGINE LOGS
     *   | ERROR LOGS
     *   | GENERAL LOGS
     *   | HOSTS
     *   | LOGS
     *   | PRIVILEGES
     *   | OPTIMIZER_COSTS
     *   | RELAY LOGS [FOR CHANNEL channel]
     *   | SLOW LOGS
     *   | STATUS
     *   | USER_RESOURCES
     * }
     */
    public function parseFlush(TokenList $tokenList): FlushCommand
    {
        $tokenList->expectKeyword(Keyword::FLUSH);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $options = [];
        $channel = null;
        do {
            $options[] = $option = $tokenList->expectMultiKeywordsEnum(FlushOption::class);
            if ($option->equalsValue(FlushOption::RELAY_LOGS) && $tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                $channel = $tokenList->expectNameOrString();
            }
        } while ($tokenList->hasSymbol(','));

        return new FlushCommand($options, $channel, $local);
    }

    /**
     * FLUSH [NO_WRITE_TO_BINLOG | LOCAL]
     *   TABLES [tbl_name [, tbl_name] ...] [WITH READ LOCK | FOR EXPORT]
     */
    public function parseFlushTables(TokenList $tokenList): FlushTablesCommand
    {
        $tokenList->expectKeyword(Keyword::FLUSH);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectAnyKeyword(Keyword::TABLES, Keyword::TABLE);

        $tables = null;
        $table = $tokenList->getQualifiedName();
        if ($table !== null) {
            $tables = [new QualifiedName(...$table)];
            while ($tokenList->hasSymbol(',')) {
                $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
            }
        }
        $keyword = $tokenList->getAnyKeyword(Keyword::WITH, Keyword::FOR);
        $withReadLock = $forExport = false;
        if ($keyword === Keyword::WITH) {
            $tokenList->expectKeywords(Keyword::READ, Keyword::LOCK);
            $withReadLock = true;
        } elseif ($keyword === Keyword::FOR) {
            $tokenList->expectKeyword(Keyword::EXPORT);
            $forExport = true;
        }

        return new FlushTablesCommand($tables, $withReadLock, $forExport, $local);
    }

}
