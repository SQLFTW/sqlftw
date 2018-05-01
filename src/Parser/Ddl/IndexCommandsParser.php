<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\Str;
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
use SqlFtw\Sql\Ddl\Table\Index\IndexOption;
use SqlFtw\Sql\Ddl\Table\Index\IndexOptions;
use SqlFtw\Sql\Ddl\Table\Index\IndexType;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;

class IndexCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX index_name
     *     [index_type]
     *     ON tbl_name (index_col_name,...)
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
        $tokenList->consumeKeyword(Keyword::CREATE);

        $index = $this->parseIndexDefinition($tokenList);

        $alterAlgorithm = $alterLock = null;
        while ($keyword = $tokenList->mayConsumeAnyKeyword(Keyword::ALGORITHM, Keyword::LOCK)) {
            if ($keyword === Keyword::ALGORITHM) {
                /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm $alterAlgorithm */
                $alterAlgorithm = $tokenList->consumeKeywordEnum(AlterTableAlgorithm::class);
            } elseif ($keyword === Keyword::LOCK) {
                /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock $alterLock */
                $alterLock = $tokenList->consumeKeywordEnum(AlterTableLock::class);
            }
        }

        return new CreateIndexCommand($index, $alterAlgorithm, $alterLock);
    }

    public function parseIndexDefinition(TokenList $tokenList, bool $inTable = false): IndexDefinition
    {
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::UNIQUE, Keyword::FULLTEXT, Keyword::SPATIAL);
        if ($keyword === Keyword::UNIQUE) {
            $type = IndexType::get($keyword . ' KEY');
        } elseif ($keyword !== null) {
            $type = IndexType::get($keyword . ' INDEX');
        } else {
            $type = IndexType::get(IndexType::INDEX);
        }
        $tokenList->consumeAnyKeyword(Keyword::INDEX, Keyword::KEY);
        if ($inTable) {
            $name = $tokenList->mayConsumeName();
        } else {
            $name = $tokenList->consumeName();
        }

        $algorithm = null;
        if ($tokenList->mayConsumeKeyword(Keyword::USING)) {
            $algorithm = $tokenList->consumeKeywordEnum(IndexAlgorithm::class);
        }

        $table = null;
        if (!$inTable) {
            $tokenList->consumeKeyword(Keyword::ON);
            $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        }
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        $columns = [];
        do {
            $column = $tokenList->consumeName();
            $length = null;
            if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                $length = $tokenList->consumeInt();
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
            /** @var \SqlFtw\Sql\Order $order */
            $order = $tokenList->mayConsumeKeywordEnum(Order::class);
            $columns[] = new IndexColumn($column, $length, $order);
        } while ($tokenList->mayConsumeComma());
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        $options = [];
        if (!$inTable) {
            $options = [IndexOption::TABLE => $table];
        }
        $keywords = [Keyword::USING, Keyword::KEY_BLOCK_SIZE, Keyword::WITH, Keyword::COMMENT, Keyword::VISIBLE];
        while ($keyword = $tokenList->mayConsumeAnyKeyword(...$keywords)) {
            if ($keyword === Keyword::USING) {
                $algorithm = $tokenList->consumeKeywordEnum(IndexAlgorithm::class);
            } elseif ($keyword === Keyword::KEY_BLOCK_SIZE) {
                $options[IndexOption::KEY_BLOCK_SIZE] = $tokenList->consumeInt();
            } elseif ($keyword === Keyword::WITH) {
                $tokenList->consumeKeyword(Keyword::PARSER);
                $options[IndexOption::WITH_PARSER] = $tokenList->consumeName();
            } elseif ($keyword === Keyword::COMMENT) {
                $comment = $tokenList->consumeString();
                // parse "COMMENT 'MERGE_THRESHOLD=40';"
                $match = Str::match($comment, '/^MERGE_THRESHOLD=([0-9]+)$/');
                if ($match) {
                    $options[IndexOption::MERGE_THRESHOLD] = (int) $match[1];
                } else {
                    $options[IndexOption::COMMENT] = $comment;
                }
            } elseif ($keyword === Keyword::VISIBLE) {
                $options[IndexOption::VISIBLE] = true;
            } elseif ($keyword === Keyword::INVISIBLE) {
                $options[IndexOption::VISIBLE] = false;
            }
        }
        if ($algorithm !== null) {
            $options[IndexOption::ALGORITHM] = $algorithm;
        }

        return new IndexDefinition($name, $type, $columns, new IndexOptions($options));
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
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::INDEX);
        $name = $tokenList->consumeName();
        $tokenList->consumeKeyword(Keyword::ON);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        $algorithm = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ALGORITHM)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm $algorithm */
            $algorithm = $tokenList->consumeKeywordEnum(AlterTableAlgorithm::class);
        }
        $lock = null;
        if ($tokenList->mayConsumeKeyword(Keyword::LOCK)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock $lock */
            $lock = $tokenList->consumeKeywordEnum(AlterTableLock::class);
        }

        return new DropIndexCommand($name, $table, $algorithm, $lock);
    }

}
