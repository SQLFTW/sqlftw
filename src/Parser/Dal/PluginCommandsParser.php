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
use SqlFtw\Sql\Dal\Plugin\InstallPluginCommand;
use SqlFtw\Sql\Dal\Plugin\UninstallPluginCommand;
use SqlFtw\Sql\Keyword;

class PluginCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * INSTALL PLUGIN plugin_name SONAME 'shared_library_name'
     *
     * @param TokenList $tokenList
     * @return InstallPluginCommand
     */
    public function parseInstallPlugin(TokenList $tokenList): InstallPluginCommand
    {
        $tokenList->consumeKeywords(Keyword::INSTALL, Keyword::PLUGIN);
        $pluginName = $tokenList->consumeName();
        $tokenList->consumeKeyword(Keyword::SONAME);
        $libName = $tokenList->consumeString();
        $tokenList->expectEnd();

        return new InstallPluginCommand($pluginName, $libName);
    }

    /**
     * UNINSTALL PLUGIN plugin_name
     *
     * @param TokenList $tokenList
     * @return UninstallPluginCommand
     */
    public function parseUninstallPlugin(TokenList $tokenList): UninstallPluginCommand
    {
        $tokenList->consumeKeywords(Keyword::UNINSTALL, Keyword::PLUGIN);
        $pluginName = $tokenList->consumeName();
        $tokenList->expectEnd();

        return new UninstallPluginCommand($pluginName);
    }

}
