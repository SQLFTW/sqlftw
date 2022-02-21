<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\Re;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Ddl\Index\CreateIndexCommand;
use SqlFtw\Sql\Ddl\Index\DropIndexCommand;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\Index\IndexAlgorithm;
use SqlFtw\Sql\Ddl\Table\Index\IndexColumn;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexOptions;
use SqlFtw\Sql\Ddl\Table\Index\IndexType;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;

class IndexCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX index_name
     *     [index_type]
     *     ON tbl_name (index_col_name, ...)
     *     [index_option]
     *     [algorithm_option | lock_option] ...
     *
     * index_col_name:
     *     col_name [(length)] [ASC | DESC]
     *
     * index_option:
     *     KEY_BLOCK_SIZE [=] value
     *   | index_type
     *   | WITH PARSER parser_name
     *   | COMMENT 'string'
     *   | {VISIBLE | INVISIBLE}
     *
     * index_type:
     *     USING {BTREE | HASH}
     *
     * algorithm_option:
     *     ALGORITHM [=] {DEFAULT|INPLACE|COPY}
     *
     * lock_option:
     *     LOCK [=] {DEFAULT|NONE|SHARED|EXCLUSIVE}
     */
    public function parseCreateIndex(TokenList $tokenList): CreateIndexCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);

        $index = $this->parseIndexDefinition($tokenList);

        $alterAlgorithm = $alterLock = null;
        while ($keyword = $tokenList->getAnyKeyword(Keyword::ALGORITHM, Keyword::LOCK)) {
            if ($keyword === Keyword::ALGORITHM) {
                /** @var AlterTableAlgorithm $alterAlgorithm */
                $alterAlgorithm = $tokenList->expectKeywordEnum(AlterTableAlgorithm::class);
            } elseif ($keyword === Keyword::LOCK) {
                /** @var AlterTableLock $alterLock */
                $alterLock = $tokenList->expectKeywordEnum(AlterTableLock::class);
            }
        }
        $tokenList->expectEnd();

        return new CreateIndexCommand($index, $alterAlgorithm, $alterLock);
    }

    public function parseIndexDefinition(TokenList $tokenList, bool $inTable = false): IndexDefinition
    {
        $keyword = $tokenList->getAnyKeyword(Keyword::UNIQUE, Keyword::FULLTEXT, Keyword::SPATIAL);
        if ($keyword === Keyword::UNIQUE) {
            $type = IndexType::get($keyword . ' INDEX');
        } elseif ($keyword !== null) {
            $type = IndexType::get($keyword . ' INDEX');
        } else {
            $type = IndexType::get(IndexType::INDEX);
        }
        $tokenList->expectAnyKeyword(Keyword::INDEX, Keyword::KEY);
        if ($inTable) {
            $name = $tokenList->getName();
        } else {
            $name = $tokenList->expectName();
        }

        $algorithm = null;
        if ($tokenList->hasKeyword(Keyword::USING)) {
            $algorithm = $tokenList->expectKeywordEnum(IndexAlgorithm::class);
        }

        $table = null;
        if (!$inTable) {
            $tokenList->expectKeyword(Keyword::ON);
            $table = new QualifiedName(...$tokenList->expectQualifiedName());
        }
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        $columns = [];
        do {
            $column = $tokenList->expectName();
            $length = null;
            if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                $length = $tokenList->expectInt();
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }
            /** @var Order $order */
            $order = $tokenList->getKeywordEnum(Order::class);
            $columns[] = new IndexColumn($column, $length, $order);
        } while ($tokenList->hasComma());
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        $keyBlockSize = $withParser = $mergeThreshold = $comment = $visible = null;
        $keywords = [Keyword::USING, Keyword::KEY_BLOCK_SIZE, Keyword::WITH, Keyword::COMMENT, Keyword::VISIBLE, Keyword::INVISIBLE];
        while ($keyword = $tokenList->getAnyKeyword(...$keywords)) {
            if ($keyword === Keyword::USING) {
                /** @var IndexAlgorithm $algorithm */
                $algorithm = $tokenList->expectKeywordEnum(IndexAlgorithm::class);
            } elseif ($keyword === Keyword::KEY_BLOCK_SIZE) {
                $keyBlockSize = $tokenList->expectInt();
            } elseif ($keyword === Keyword::WITH) {
                $tokenList->expectKeyword(Keyword::PARSER);
                $withParser = $tokenList->expectName();
            } elseif ($keyword === Keyword::COMMENT) {
                $commentString = $tokenList->expectString();
                // parse "COMMENT 'MERGE_THRESHOLD=40';"
                $match = Re::match($commentString, '/^MERGE_THRESHOLD=([0-9]+)$/');
                if ($match !== null) {
                    $mergeThreshold = (int) $match[1];
                } else {
                    $comment = $commentString;
                }
            } elseif ($keyword === Keyword::VISIBLE) {
                $visible = true;
            } elseif ($keyword === Keyword::INVISIBLE) {
                $visible = false;
            }
        }

        $options = $keyBlockSize !== null || $withParser !== null || $mergeThreshold !== null || $comment !== null || $visible !== null
            ? new IndexOptions($keyBlockSize, $withParser, $mergeThreshold, $comment, $visible)
            : null;

        return new IndexDefinition($name, $type, $columns, $algorithm, $options, $table);
    }

    /**
     * DROP INDEX index_name ON tbl_name
     *     [algorithm_option | lock_option] ...
     *
     * algorithm_option:
     *     ALGORITHM [=] {DEFAULT|INPLACE|COPY}
     *
     * lock_option:
     *     LOCK [=] {DEFAULT|NONE|SHARED|EXCLUSIVE}
     */
    public function parseDropIndex(TokenList $tokenList): DropIndexCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::INDEX);
        $name = $tokenList->expectName();
        $tokenList->expectKeyword(Keyword::ON);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());
        $algorithm = null;
        if ($tokenList->hasKeyword(Keyword::ALGORITHM)) {
            $tokenList->passEqual();
            /** @var AlterTableAlgorithm $algorithm */
            $algorithm = $tokenList->expectKeywordEnum(AlterTableAlgorithm::class);
        }
        $lock = null;
        if ($tokenList->hasKeyword(Keyword::LOCK)) {
            $tokenList->passEqual();
            /** @var AlterTableLock $lock */
            $lock = $tokenList->expectKeywordEnum(AlterTableLock::class);
        }
        $tokenList->expectEnd();

        return new DropIndexCommand($name, $table, $algorithm, $lock);
    }

}
