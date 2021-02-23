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
use function strtolower;

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
     *
     * @param TokenList $tokenList
     * @return LoadDataCommand
     */
    public function parseLoadData(TokenList $tokenList): LoadDataCommand
    {
        $tokenList->consumeKeywords(Keyword::LOAD, Keyword::DATA);
        [$priority, $local, $file, $duplicateOption, $table, $partitions, $charset] = $this->parseOptions($tokenList, true);
        $format = $this->fileFormatParser->parseFormat($tokenList);
        [$ignoreRows, $fields, $setters] = $this->parseRowsAndFields($tokenList);
        $tokenList->expectEnd();

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
     *
     * @param TokenList $tokenList
     * @return LoadXmlCommand
     */
    public function parseLoadXml(TokenList $tokenList): LoadXmlCommand
    {
        $tokenList->consumeKeywords(Keyword::LOAD, Keyword::XML);
        [$priority, $local, $file, $duplicateOption, $table, , $charset] = $this->parseOptions($tokenList, false);

        $rowsTag = null;
        if ($tokenList->mayConsumeKeywords(Keyword::ROWS, Keyword::IDENTIFIED, Keyword::BY)) {
            $rowsTag = $tokenList->consumeString();
        }

        [$ignoreRows, $fields, $setters] = $this->parseRowsAndFields($tokenList);
        $tokenList->expectEnd();

        return new LoadXmlCommand($file, $table, $rowsTag, $charset, $fields, $setters, $ignoreRows, $priority, $local, $duplicateOption);
    }

    /**
     * @param TokenList $tokenList
     * @param bool $parsePartitions
     * @return mixed[]
     */
    private function parseOptions(TokenList $tokenList, bool $parsePartitions): array
    {
        $priority = $tokenList->mayConsumeKeywordEnum(LoadPriority::class);
        $local = (bool) $tokenList->mayConsumeKeyword(Keyword::LOCAL);

        $tokenList->consumeKeyword(Keyword::INFILE);
        $file = $tokenList->consumeString();

        $duplicateOption = $tokenList->mayConsumeKeywordEnum(DuplicateOption::class);

        $tokenList->consumeKeywords(Keyword::INTO, Keyword::TABLE);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());

        $partitions = null;
        if ($parsePartitions && $tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $partitions = [];
            do {
                $partitions[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        $charset = null;
        if ($tokenList->mayConsumeKeywords(Keyword::CHARACTER, Keyword::SET)) {
            $charset = Charset::get(strtolower($tokenList->consumeString()));
        }

        return [$priority, $local, $file, $duplicateOption, $table, $partitions, $charset];
    }

    /**
     * @param TokenList $tokenList
     * @return mixed[]
     */
    private function parseRowsAndFields(TokenList $tokenList): array
    {
        $ignoreRows = null;
        if ($tokenList->mayConsumeKeyword(Keyword::IGNORE)) {
            $ignoreRows = $tokenList->consumeInt();
            $tokenList->consumeAnyKeyword(Keyword::LINES, Keyword::ROWS);
        }

        $fields = null;
        if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            $fields = [];
            do {
                $fields[] = $tokenList->mayConsumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        $setters = null;
        if ($tokenList->mayConsumeKeyword(Keyword::SET)) {
            $setters = [];
            do {
                $field = $tokenList->consumeName();
                $tokenList->consumeOperator(Operator::EQUAL);
                $expression = $this->expressionParser->parseExpression($tokenList);
                $setters[$field] = $expression;
            } while ($tokenList->mayConsumeComma());
        }

        return [$ignoreRows, $fields, $setters];
    }

}
