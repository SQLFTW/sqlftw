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
use function array_search;

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

    /** @var array<string, int> */
    private static $ids = [
        self::BIG_5 => 1,
        self::DEC_8 => 3,
        self::CP850 => 4,
        self::HP_8 => 6,
        self::KOI8_R => 7,
        self::LATIN_1 => 8,
        self::LATIN_2 => 9,
        self::SWE_7 => 10,
        self::ASCII => 11,
        self::UJIS => 12,
        self::SJIS => 13,
        self::HEBREW => 16,
        self::TIS_620 => 18,
        self::EUC_KR => 19,
        self::KOI8_U => 22,
        self::GB2312 => 24,
        self::GREEK => 25,
        self::CP1250 => 26,
        self::GBK => 28,
        self::LATIN_5 => 30,
        self::ARMSCII_8 => 32,
        self::UTF_8_OLD => 33,
        self::UCS_2 => 35,
        self::CP866 => 36,
        self::KEYBCS_2 => 37,
        self::MAC_CE => 38,
        self::MAC_ROMAN => 39,
        self::CP852 => 40,
        self::LATIN_7 => 41,
        self::CP1251 => 51,
        self::UTF_16 => 54,
        self::UTF_16LE => 56,
        self::CP1256 => 57,
        self::CP1257 => 59,
        self::UTF_32 => 60,
        self::BINARY => 63,
        self::GEOSTD_8 => 92,
        self::CP932 => 95,
        self::EUC_JP_MS => 97,
        self::GB18030 => 248,
        self::UTF_8 => 255,
    ];

    public function getId(): int
    {
        return self::$ids[$this->getValue()];
    }

    public static function getById(int $id): self
    {
        $key = array_search($id, self::$ids, true);
        if ($key === false) {
            throw new InvalidDefinitionException("Unknown charset id: $id");
        }

        return self::get($key);
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->getValue();
    }

}
