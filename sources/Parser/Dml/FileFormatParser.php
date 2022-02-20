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
     */
    public function parseFormat(TokenList $tokenList): ?FileFormat
    {
        $format = $fieldsTerminatedBy = $fieldsEnclosedBy = $fieldsEscapedBy = null;
        $optionallyEnclosed = false;
        if ($tokenList->hasAnyKeyword(Keyword::FIELDS, Keyword::COLUMNS)) {
            if ($tokenList->hasKeywords(Keyword::TERMINATED, Keyword::BY)) {
                $fieldsTerminatedBy = $tokenList->expectString();
            }
            $optionallyEnclosed = $tokenList->hasKeyword(Keyword::OPTIONALLY);
            if ($tokenList->hasKeywords(Keyword::ENCLOSED, Keyword::BY)) {
                $fieldsEnclosedBy = $tokenList->expectString();
            }
            if ($tokenList->hasKeywords(Keyword::ESCAPED, Keyword::BY)) {
                $fieldsEscapedBy = $tokenList->expectString();
            }
        }
        $linesStaringBy = $linesTerminatedBy = null;
        if ($tokenList->hasKeyword(Keyword::LINES)) {
            if ($tokenList->hasKeywords(Keyword::STARTING, Keyword::BY)) {
                $linesStaringBy = $tokenList->expectString();
            }
            if ($tokenList->hasKeywords(Keyword::TERMINATED, Keyword::BY)) {
                $linesTerminatedBy = $tokenList->expectString();
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
