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
use SqlFtw\Sql\Dal\Cache\CacheIndexCommand;
use SqlFtw\Sql\Dal\Cache\LoadIndexIntoCacheCommand;
use SqlFtw\Sql\Dal\Cache\TableIndexList;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\TableName;

/**
 * MySQL MyISAM tables only
 */
class CacheCommandsParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * CACHE INDEX
     *     tbl_index_list [, tbl_index_list] ...
     *     [PARTITION (partition_list | ALL)]
     *     IN key_cache_name
     *
     * tbl_index_list:
     *     tbl_name [[INDEX|KEY] (index_name[, index_name] ...)]
     *
     * partition_list:
     *     partition_name[, partition_name][, ...]
     */
    public function parseCacheIndex(TokenList $tokenList): CacheIndexCommand
    {
        $tokenList->consumeKeywords(Keyword::CACHE, Keyword::INDEX);

        $tableIndexLists = [];
        do {
            $table = new TableName(...$tokenList->consumeQualifiedName());
            $indexes = $this->parseIndexes($tokenList);

            $tableIndexLists[] = new TableIndexList($table, $indexes);
        } while ($tokenList->mayConsumeComma());

        $partitions = $this->parsePartitions($tokenList);

        $tokenList->consumeKeyword(Keyword::IN);
        $keyCache = $tokenList->consumeName();

        return new CacheIndexCommand($keyCache, $tableIndexLists, $partitions);
    }

    /**
     * LOAD INDEX INTO CACHE
     *     tbl_index_list [, tbl_index_list] ...
     *
     * tbl_index_list:
     *     tbl_name
     *     [PARTITION (partition_list | ALL)]
     *     [[INDEX|KEY] (index_name[, index_name] ...)]
     *     [IGNORE LEAVES]
     *
     * partition_list:
     *     partition_name[, partition_name][, ...]
     */
    public function parseLoadIndexIntoCache(TokenList $tokenList): LoadIndexIntoCacheCommand
    {
        $tokenList->consumeKeywords(Keyword::LOAD, Keyword::INDEX, Keyword::INTO, Keyword::CACHE);

        $tableIndexLists = [];
        do {
            $table = new TableName(...$tokenList->consumeQualifiedName());
            $partitions = $this->parsePartitions($tokenList);
            $indexes = $this->parseIndexes($tokenList);
            $ignoreLeaves = (bool) $tokenList->mayConsumeKeywords(Keyword::IGNORE, Keyword::LEAVES);

            $tableIndexLists[] = new TableIndexList($table, $indexes, $partitions, $ignoreLeaves);
        } while ($tokenList->mayConsumeComma());

        return new LoadIndexIntoCacheCommand($tableIndexLists);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return string[]|null
     */
    private function parseIndexes(TokenList $tokenList): ?array
    {
        $indexes = null;
        if ($tokenList->mayConsumeAnyKeyword(Keyword::INDEX, Keyword::KEY)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $indexes = [];
            do {
                $indexes[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        return $indexes;
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return string[]|bool|null
     */
    private function parsePartitions(TokenList $tokenList)
    {
        if (!$tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
            return null;
        }

        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        if ($tokenList->mayConsumeKeyword(Keyword::ALL)) {
            $partitions = true;
        } else {
            $partitions = [];
            do {
                $partitions[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
        }
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        return $partitions;
    }

}
