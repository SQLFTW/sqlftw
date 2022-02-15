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
use SqlFtw\Sql\Dal\Component\InstallComponentCommand;
use SqlFtw\Sql\Dal\Component\UninstallComponentCommand;
use SqlFtw\Sql\Keyword;

class ComponentCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * INSTALL COMPONENT component_name [, component_name ] ...
     */
    public function parseInstallComponent(TokenList $tokenList): InstallComponentCommand
    {
        $tokenList->consumeKeywords(Keyword::INSTALL, Keyword::COMPONENT);
        $components = [];
        do {
            $components[] = $tokenList->consumeNameOrString();
        } while ($tokenList->mayConsumeComma());
        $tokenList->expectEnd();

        return new InstallComponentCommand($components);
    }

    /**
     * UNINSTALL COMPONENT component_name [, component_name ] ...
     */
    public function parseUninstallComponent(TokenList $tokenList): UninstallComponentCommand
    {
        $tokenList->consumeKeywords(Keyword::UNINSTALL, Keyword::COMPONENT);
        $components = [];
        do {
            $components[] = $tokenList->consumeNameOrString();
        } while ($tokenList->mayConsumeComma());
        $tokenList->expectEnd();

        return new UninstallComponentCommand($components);
    }

}
