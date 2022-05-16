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
use SqlFtw\Parser\ExpressionParser;
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
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;

class IndexCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * https://dev.mysql.com/doc/refman/8.0/en/create-index.html
     * CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX index_name
     *     [index_type]
     *     ON tbl_name (key_part, ...)
     *     [index_option]
     *     [algorithm_option | lock_option] ...
     *
     * key_part: {
     *     col_name [(length)]
     *   | (expr) -- 8.0.13
     * } [ASC | DESC]
     *
     * index_option:
     *     KEY_BLOCK_SIZE [=] value
     *   | index_type
     *   | WITH PARSER parser_name
     *   | COMMENT 'string'
     *   | {VISIBLE | INVISIBLE}
     *   | ENGINE_ATTRIBUTE [=] 'string' -- 8.0.21
     *   | SECONDARY_ENGINE_ATTRIBUTE [=] 'string' -- 8.0.21
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

        if ($inTable) {
            $tokenList->getAnyKeyword(Keyword::INDEX, Keyword::KEY);
            $name = $tokenList->getName();
        } else {
            $tokenList->expectAnyKeyword(Keyword::INDEX, Keyword::KEY);
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

        $parts = $this->parseIndexParts($tokenList);

        $keyBlockSize = $withParser = $mergeThreshold = $comment = $visible = $engineAttribute = $secondaryEngineAttribute = null;
        // phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
        $keywords = [
            Keyword::USING, Keyword::KEY_BLOCK_SIZE, Keyword::WITH, Keyword::COMMENT, Keyword::VISIBLE,
            Keyword::INVISIBLE, Keyword::ENGINE_ATTRIBUTE, Keyword::SECONDARY_ENGINE_ATTRIBUTE,
        ];
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
            } elseif ($keyword === Keyword::ENGINE_ATTRIBUTE) {
                $tokenList->check(Keyword::ENGINE_ATTRIBUTE, 80021);
                $tokenList->passSymbol('=');
                $engineAttribute = $tokenList->expectString();
            } elseif ($keyword === Keyword::SECONDARY_ENGINE_ATTRIBUTE) {
                $tokenList->check(Keyword::SECONDARY_ENGINE_ATTRIBUTE, 80021);
                $tokenList->passSymbol('=');
                $secondaryEngineAttribute = $tokenList->expectString();
            }
        }

        $options = null;
        if ($keyBlockSize !== null
            || $withParser !== null
            || $mergeThreshold !== null
            || $comment !== null
            || $visible !== null
            || $engineAttribute !== null
            || $secondaryEngineAttribute !== null
        ) {
            $options = new IndexOptions($keyBlockSize, $withParser, $mergeThreshold, $comment, $visible, $engineAttribute, $secondaryEngineAttribute);
        }

        return new IndexDefinition($name, $type, $parts, $algorithm, $options, $table);
    }

    /**
     * @return array<IndexColumn|ExpressionNode>
     */
    private function parseIndexParts(TokenList $tokenList): array
    {
        $tokenList->expectSymbol('(');
        $parts = [];
        do {
            if ($tokenList->hasSymbol('(')) {
                $tokenList->check('functional indexes', 80013);
                $parts[] = $this->expressionParser->parseExpression($tokenList);
                $tokenList->expectSymbol(')');
            } else {
                $part = $tokenList->expectName();
                $length = null;
                if ($tokenList->hasSymbol('(')) {
                    $length = $tokenList->expectInt();
                    $tokenList->expectSymbol(')');
                }
                /** @var Order $order */
                $order = $tokenList->getKeywordEnum(Order::class);
                $parts[] = new IndexColumn($part, $length, $order);
            }
        } while ($tokenList->hasSymbol(','));
        $tokenList->expectSymbol(')');

        return $parts;
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
            $tokenList->passSymbol('=');
            /** @var AlterTableAlgorithm $algorithm */
            $algorithm = $tokenList->expectKeywordEnum(AlterTableAlgorithm::class);
        }
        $lock = null;
        if ($tokenList->hasKeyword(Keyword::LOCK)) {
            $tokenList->passSymbol('=');
            /** @var AlterTableLock $lock */
            $lock = $tokenList->expectKeywordEnum(AlterTableLock::class);
        }

        return new DropIndexCommand($name, $table, $algorithm, $lock);
    }

}
