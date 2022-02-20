<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Table\AnalyzeTableCommand;
use SqlFtw\Sql\Dal\Table\ChecksumTableCommand;
use SqlFtw\Sql\Dal\Table\CheckTableCommand;
use SqlFtw\Sql\Dal\Table\CheckTableOption;
use SqlFtw\Sql\Dal\Table\OptimizeTableCommand;
use SqlFtw\Sql\Dal\Table\RepairTableCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class TableMaintenanceCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     */
    public function parseAnalyzeTable(TokenList $tokenList): AnalyzeTableCommand
    {
        $tokenList->expectKeyword(Keyword::ANALYZE);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectKeyword(Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());
        $tokenList->expectEnd();

        return new AnalyzeTableCommand($tables, $local);
    }

    /**
     * CHECK TABLE tbl_name [, tbl_name] ... [option] ...
     *
     * option = {
     *     FOR UPGRADE
     *   | QUICK
     *   | FAST
     *   | MEDIUM
     *   | EXTENDED
     *   | CHANGED
     * }
     */
    public function parseCheckTable(TokenList $tokenList): CheckTableCommand
    {
        $tokenList->expectKeywords(Keyword::CHECK, Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());

        $option = $tokenList->getAnyKeyword(Keyword::FOR, Keyword::QUICK, Keyword::FAST, Keyword::MEDIUM, Keyword::EXTENDED, Keyword::CHANGED);
        if ($option !== null) {
            if ($option === Keyword::FOR) {
                $tokenList->expectKeyword(Keyword::UPGRADE);
                $option .= ' UPGRADE';
            }
            $option = CheckTableOption::get($option);
        }
        $tokenList->expectEnd();

        return new CheckTableCommand($tables, $option);
    }

    /**
     * CHECKSUM TABLE tbl_name [, tbl_name] ... [QUICK | EXTENDED]
     */
    public function parseChecksumTable(TokenList $tokenList): ChecksumTableCommand
    {
        $tokenList->expectKeywords(Keyword::CHECKSUM, Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());

        $quick = $tokenList->hasKeyword(Keyword::QUICK);
        $extended = $tokenList->hasKeyword(Keyword::EXTENDED);
        $tokenList->expectEnd();

        return new ChecksumTableCommand($tables, $quick, $extended);
    }

    /**
     * OPTIMIZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     */
    public function parseOptimizeTable(TokenList $tokenList): OptimizeTableCommand
    {
        $tokenList->expectKeyword(Keyword::OPTIMIZE);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectKeyword(Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());
        $tokenList->expectEnd();

        return new OptimizeTableCommand($tables, $local);
    }

    /**
     * REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     *     [QUICK] [EXTENDED] [USE_FRM]
     */
    public function parseRepairTable(TokenList $tokenList): RepairTableCommand
    {
        $tokenList->expectKeyword(Keyword::REPAIR);
        $local = $tokenList->hasAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->expectKeyword(Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());

        $quick = $tokenList->hasKeyword(Keyword::QUICK);
        $extended = $tokenList->hasKeyword(Keyword::EXTENDED);
        $useFrm = $tokenList->hasKeyword(Keyword::USE_FRM);
        $tokenList->expectEnd();

        return new RepairTableCommand($tables, $local, $quick, $extended, $useFrm);
    }

}
