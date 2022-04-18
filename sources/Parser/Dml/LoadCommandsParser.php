<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Dml\Load\LoadDataCommand;
use SqlFtw\Sql\Dml\Load\LoadPriority;
use SqlFtw\Sql\Dml\Load\LoadXmlCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class LoadCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var FileFormatParser */
    private $fileFormatParser;

    public function __construct(ExpressionParser $expressionParser, FileFormatParser $fileFormatParser)
    {
        $this->expressionParser = $expressionParser;
        $this->fileFormatParser = $fileFormatParser;
    }

    /**
     * LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
     *     [REPLACE | IGNORE]
     *     INTO TABLE tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [CHARACTER SET charset_name]
     *     [{FIELDS | COLUMNS}
     *         [TERMINATED BY 'string']
     *         [[OPTIONALLY] ENCLOSED BY 'char']
     *         [ESCAPED BY 'char']
     *     ]
     *     [LINES
     *         [STARTING BY 'string']
     *         [TERMINATED BY 'string']
     *     ]
     *     [IGNORE number {LINES | ROWS}]
     *     [(col_name_or_user_var, ...)]
     *     [SET col_name = expr, ...]
     */
    public function parseLoadData(TokenList $tokenList): LoadDataCommand
    {
        $tokenList->expectKeywords(Keyword::LOAD, Keyword::DATA);
        [$priority, $local, $file, $duplicateOption, $table, $partitions, $charset] = $this->parseOptions($tokenList, true);
        $format = $this->fileFormatParser->parseFormat($tokenList);
        [$ignoreRows, $fields, $setters] = $this->parseRowsAndFields($tokenList);

        return new LoadDataCommand($file, $table, $format, $charset, $fields, $setters, $ignoreRows, $priority, $local, $duplicateOption, $partitions);
    }

    /**
     * LOAD XML [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
     *     [REPLACE | IGNORE]
     *     INTO TABLE [db_name.]tbl_name
     *     [CHARACTER SET charset_name]
     *     [ROWS IDENTIFIED BY '<tagname>']
     *     [IGNORE number {LINES | ROWS}]
     *     [(field_name_or_user_var, ...)]
     *     [SET col_name = expr, ...]
     */
    public function parseLoadXml(TokenList $tokenList): LoadXmlCommand
    {
        $tokenList->expectKeywords(Keyword::LOAD, Keyword::XML);
        [$priority, $local, $file, $duplicateOption, $table, , $charset] = $this->parseOptions($tokenList, false);

        $rowsTag = null;
        if ($tokenList->hasKeywords(Keyword::ROWS, Keyword::IDENTIFIED, Keyword::BY)) {
            $rowsTag = $tokenList->expectString();
        }

        [$ignoreRows, $fields, $setters] = $this->parseRowsAndFields($tokenList);

        return new LoadXmlCommand($file, $table, $rowsTag, $charset, $fields, $setters, $ignoreRows, $priority, $local, $duplicateOption);
    }

    /**
     * @return mixed[]
     */
    private function parseOptions(TokenList $tokenList, bool $parsePartitions): array
    {
        $priority = $tokenList->getKeywordEnum(LoadPriority::class);
        $local = $tokenList->hasKeyword(Keyword::LOCAL);

        $tokenList->expectKeyword(Keyword::INFILE);
        $file = $tokenList->expectString();

        $duplicateOption = $tokenList->getKeywordEnum(DuplicateOption::class);

        $tokenList->expectKeywords(Keyword::INTO, Keyword::TABLE);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());

        $partitions = null;
        if ($parsePartitions && $tokenList->hasKeyword(Keyword::PARTITION)) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $partitions = [];
            do {
                $partitions[] = $tokenList->expectName();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        $charset = null;
        if ($tokenList->hasKeywords(Keyword::CHARACTER, Keyword::SET) || $tokenList->hasKeyword(Keyword::CHARSET)) {
            $charset = $tokenList->expectNameOrStringEnum(Charset::class);
        }

        return [$priority, $local, $file, $duplicateOption, $table, $partitions, $charset];
    }

    /**
     * @return mixed[]
     */
    private function parseRowsAndFields(TokenList $tokenList): array
    {
        $ignoreRows = null;
        if ($tokenList->hasKeyword(Keyword::IGNORE)) {
            $ignoreRows = $tokenList->expectInt();
            $tokenList->expectAnyKeyword(Keyword::LINES, Keyword::ROWS);
        }

        $fields = null;
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $fields = [];
            do {
                $fields[] = $tokenList->getName();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        $setters = null;
        if ($tokenList->hasKeyword(Keyword::SET)) {
            $setters = [];
            do {
                $field = $tokenList->expectName();
                $tokenList->expectOperator(Operator::EQUAL);
                $expression = $this->expressionParser->parseExpression($tokenList);
                $setters[$field] = $expression;
            } while ($tokenList->hasComma());
        }

        return [$ignoreRows, $fields, $setters];
    }

}
