<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

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
    use \Dogma\StrictBehaviorMixin;

    /**
     * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     */
    public function parseAnalyzeTable(TokenList $tokenList): AnalyzeTableCommand
    {
        $tokenList->consumeKeyword(Keyword::ANALYZE);
        $local = (bool) $tokenList->mayConsumeAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->consumeKeyword(Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

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
        $tokenList->consumeKeywords(Keyword::REPAIR, Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

        $option = null;
        $option = $tokenList->mayConsumeAnyKeyword(Keyword::FOR, Keyword::QUICK, Keyword::FAST, Keyword::MEDIUM, Keyword::EXTENDED, Keyword::CHANGED);
        if ($option !== null) {
            if ($option === Keyword::FOR) {
                $tokenList->consumeKeyword(Keyword::UPDATE);
                $option .= ' UPDATE';
            }
            $option = CheckTableOption::get($option);
        }

        return new CheckTableCommand($tables, $option);
    }

    /**
     * CHECKSUM TABLE tbl_name [, tbl_name] ... [QUICK | EXTENDED]
     */
    public function parseChecksumTable(TokenList $tokenList): ChecksumTableCommand
    {
        $tokenList->consumeKeywords(Keyword::REPAIR, Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

        $quick = (bool) $tokenList->mayConsumeKeyword(Keyword::QUICK);
        $extended = (bool) $tokenList->mayConsumeKeyword(Keyword::EXTENDED);

        return new ChecksumTableCommand($tables, $quick, $extended);
    }

    /**
     * OPTIMIZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     */
    public function parseOptimizeTable(TokenList $tokenList): OptimizeTableCommand
    {
        $tokenList->consumeKeyword(Keyword::OPTIMIZE);
        $local = (bool) $tokenList->mayConsumeAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->consumeKeyword(Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

        return new OptimizeTableCommand($tables, $local);
    }

    /**
     * REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE
     *     tbl_name [, tbl_name] ...
     *     [QUICK] [EXTENDED] [USE_FRM]
     */
    public function parseRepairTable(TokenList $tokenList): RepairTableCommand
    {
        $tokenList->consumeKeyword(Keyword::REPAIR);
        $local = (bool) $tokenList->mayConsumeAnyKeyword(Keyword::NO_WRITE_TO_BINLOG, Keyword::LOCAL);
        $tokenList->consumeKeyword(Keyword::TABLE);
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

        $quick = (bool) $tokenList->mayConsumeKeyword(Keyword::QUICK);
        $extended = (bool) $tokenList->mayConsumeKeyword(Keyword::EXTENDED);
        $useFrm = (bool) $tokenList->mayConsumeKeyword(Keyword::USE_FRM);

        return new RepairTableCommand($tables, $local, $quick, $extended, $useFrm);
    }

}
