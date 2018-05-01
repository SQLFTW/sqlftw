<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Formatter\Formatter;

class Charset extends SqlEnum
{

    public const ARMSCII_8 = 'armscii8';
    public const ASCII = 'ascii';
    public const BIG_5 = 'big5';
    public const BINARY = 'binary';
    public const CP1250 = 'cp1250';
    public const CP1251 = 'cp1251';
    public const CP1256 = 'cp1256';
    public const CP1257 = 'cp1257';
    public const CP850 = 'cp850';
    public const CP852 = 'cp852';
    public const CP866 = 'cp866';
    public const CP932 = 'cp932';
    public const DEC_8 = 'dec8';
    public const EUC_JP_MS = 'eucjpms';
    public const EUC_KR = 'euckr';
    public const GB18030 = 'gb18030';
    public const GB2312 = 'gb2312';
    public const GBK = 'gbk';
    public const GEOSTD_8 = 'geostd8';
    public const GREEK = 'greek';
    public const HEBREW = 'hebrew';
    public const HP_8 = 'hp8';
    public const KEYBCS_2 = 'keybcs2';
    public const KOI8_R = 'koi8r';
    public const KOI8_U = 'koi8u';
    public const LATIN_1 = 'latin1';
    public const LATIN_2 = 'latin2';
    public const LATIN_5 = 'latin5';
    public const LATIN_7 = 'latin7';
    public const MAC_CE = 'macce';
    public const MAC_ROMAN = 'macroman';
    public const SJIS = 'sjis';
    public const SWE_7 = 'swe7';
    public const TIS_620 = 'tis620';
    public const UCS_2 = 'ucs2';
    public const UJIS = 'ujis';
    public const UTF_16 = 'utf16';
    public const UTF_16LE = 'utf16le';
    public const UTF_32 = 'utf32';
    public const UTF_8_OLD = 'utf8';
    public const UTF_8 = 'utf8mb4';

    public function serialize(Formatter $formatter): string
    {
        return "'" . $this->getValue() . "'";
    }

}
