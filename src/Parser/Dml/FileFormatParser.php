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
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dml\FileFormat;
use SqlFtw\Sql\Keyword;

class FileFormatParser
{
    use StrictBehaviorMixin;

    /**
     * [{FIELDS | COLUMNS}
     *   [TERMINATED BY 'string']
     *   [[OPTIONALLY] ENCLOSED BY 'char']
     *   [ESCAPED BY 'char']
     * ]
     * [LINES
     *   [STARTING BY 'string']
     *   [TERMINATED BY 'string']
     * ]
     *
     * @param TokenList $tokenList
     * @return FileFormat|null
     */
    public function parseFormat(TokenList $tokenList): ?FileFormat
    {
        $format = $fieldsTerminatedBy = $fieldsEnclosedBy = $fieldsEscapedBy = null;
        $optionallyEnclosed = false;
        if ($tokenList->mayConsumeAnyKeyword(Keyword::FIELDS, Keyword::COLUMNS)) {
            if ($tokenList->mayConsumeKeywords(Keyword::TERMINATED, Keyword::BY)) {
                $fieldsTerminatedBy = $tokenList->consumeString();
            }
            $optionallyEnclosed = (bool) $tokenList->mayConsumeKeyword(Keyword::OPTIONALLY);
            if ($tokenList->mayConsumeKeywords(Keyword::ENCLOSED, Keyword::BY)) {
                $fieldsEnclosedBy = $tokenList->consumeString();
            }
            if ($tokenList->mayConsumeKeywords(Keyword::ESCAPED, Keyword::BY)) {
                $fieldsEscapedBy = $tokenList->consumeString();
            }
        }
        $linesStaringBy = $linesTerminatedBy = null;
        if ($tokenList->mayConsumeKeyword(Keyword::LINES)) {
            if ($tokenList->mayConsumeKeywords(Keyword::STARTING, Keyword::BY)) {
                $linesStaringBy = $tokenList->consumeString();
            }
            if ($tokenList->mayConsumeKeywords(Keyword::TERMINATED, Keyword::BY)) {
                $linesTerminatedBy = $tokenList->consumeString();
            }
        }
        if ($fieldsTerminatedBy ?? $fieldsEnclosedBy ?? $fieldsEscapedBy ?? $linesStaringBy ?? $linesTerminatedBy) {
            $format = new FileFormat(
                $fieldsTerminatedBy,
                $fieldsEnclosedBy,
                $fieldsEscapedBy,
                $optionallyEnclosed,
                $linesStaringBy,
                $linesTerminatedBy
            );
        }

        return $format;
    }

}
