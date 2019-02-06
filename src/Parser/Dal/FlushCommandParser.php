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
     * flush_option:
     *     DES_KEY_FILE
     *   | HOSTS
     *   | [log_type] LOGS
     *   | RELAY LOGS [channel_option]
     *   | OPTIMIZER_COSTS
     *   | PRIVILEGES
     *   | QUERY CACHE
     *   | STATUS
     *   | USER_RESOURCES
     *
     * log_type:
     *     BINARY
     *   | ENGINE
     *   | ERROR
     *   | GENERAL
     *   | RELAY
     *   | SLOW
     *
     * channel_option:
     *     FOR CHANNEL channel
     */
    public function parseFlush(TokenList $tokenList): FlushCommand
    {
        $tokenList->consumeKeyword(Keyword::FLUSH);
        $local = (bool) $tokenList->mayConsumeAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $options = [];
        $channel = null;
        $logs = [Keyword::BINARY, Keyword::ENGINE, Keyword::ERROR, Keyword::GENERAL, Keyword::RELAY, Keyword::SLOW];
        $other = [Keyword::DES_KEY_FILE, Keyword::HOSTS, Keyword::OPTIMIZER_COSTS, Keyword::PRIVILEGES, Keyword::QUERY, Keyword::STATUS, Keyword::USER_RESOURCES];
        do {
            $keyword = $tokenList->consumeAnyKeyword(...array_merge($logs, $other));
            if (Arr::contains($logs, $keyword)) {
                $tokenList->consumeKeyword(Keyword::LOGS);
                if ($keyword === Keyword::RELAY && $tokenList->mayConsumeKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                    $channel = $tokenList->consumeName();
                }
                $options[] = FlushOption::get($keyword . ' ' . Keyword::LOGS);
            } elseif ($keyword === Keyword::QUERY) {
                $tokenList->consumeKeyword(Keyword::CACHE);
                $options[] = FlushOption::get($keyword . ' ' . Keyword::CACHE);
            } else {
                $options[] = FlushOption::get($keyword);
            }
        } while ($tokenList->mayConsumeComma());

        return new FlushCommand($options, $channel, $local);
    }

    /**
     * FLUSH TABLES [tbl_name [, tbl_name] ...] [WITH READ LOCK | FOR EXPORT]
     */
    public function parseFlushTables(TokenList $tokenList): FlushTablesCommand
    {
        $tokenList->consumeKeywords(Keyword::FLUSH, Keyword::TABLES);
        $tables = [];
        $table = $tokenList->mayConsumeQualifiedName();
        if ($table !== null) {
            $tables[] = new QualifiedName(...$table);
            while ($tokenList->mayConsumeComma()) {
                $tables = new QualifiedName(...$tokenList->consumeQualifiedName());
            }
        }
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::WITH, Keyword::FOR);
        $withReadLock = $forExport = false;
        if ($keyword === Keyword::WITH) {
            $tokenList->consumeKeywords(Keyword::READ, Keyword::LOCK);
            $withReadLock = true;
        } else {
            $tokenList->consumeKeyword(Keyword::EXPORT);
            $forExport = true;
        }

        return new FlushTablesCommand($tables, $withReadLock, $forExport);
    }

}
