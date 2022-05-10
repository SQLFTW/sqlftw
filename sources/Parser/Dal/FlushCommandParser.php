<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Flush\FlushCommand;
use SqlFtw\Sql\Dal\Flush\FlushOption;
use SqlFtw\Sql\Dal\Flush\FlushTablesCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use function array_merge;

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
        $logs = [Keyword::BINARY, Keyword::ENGINE, Keyword::ERROR, Keyword::GENERAL, Keyword::RELAY, Keyword::SLOW];
        $other = [Keyword::LOGS, Keyword::DES_KEY_FILE, Keyword::HOSTS, Keyword::OPTIMIZER_COSTS, Keyword::PRIVILEGES, Keyword::QUERY, Keyword::STATUS, Keyword::USER_RESOURCES];
        do {
            $keyword = $tokenList->expectAnyKeyword(...array_merge($logs, $other));
            if (Arr::contains($logs, $keyword)) {
                $tokenList->expectKeyword(Keyword::LOGS);
                if ($keyword === Keyword::RELAY && $tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                    $channel = $tokenList->expectName();
                }
                $options[] = FlushOption::get($keyword . ' ' . Keyword::LOGS);
            } elseif ($keyword === Keyword::QUERY) {
                $tokenList->expectKeyword(Keyword::CACHE);
                $options[] = FlushOption::get($keyword . ' ' . Keyword::CACHE);
            } else {
                $options[] = FlushOption::get($keyword);
            }
        } while ($tokenList->hasComma());

        return new FlushCommand($options, $channel, $local);
    }

    /**
     * FLUSH TABLES [tbl_name [, tbl_name] ...] [WITH READ LOCK | FOR EXPORT]
     */
    public function parseFlushTables(TokenList $tokenList): FlushTablesCommand
    {
        $tokenList->expectKeywords(Keyword::FLUSH, Keyword::TABLES);
        $tables = null;
        $table = $tokenList->getQualifiedName();
        if ($table !== null) {
            $tables = [new QualifiedName(...$table)];
            while ($tokenList->hasComma()) {
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

        return new FlushTablesCommand($tables, $withReadLock, $forExport);
    }

}
