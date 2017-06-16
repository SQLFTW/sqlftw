<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Sql\Dal\Plugin\InstallPluginCommand;
use SqlFtw\Sql\Dal\Plugin\UninstallPluginCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\TokenList;

class PluginCommandsParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * INSTALL PLUGIN plugin_name SONAME 'shared_library_name'
     */
    public function parseInstallPlugin(TokenList $tokenList): InstallPluginCommand
    {
        $tokenList->consumeKeywords(Keyword::INSTALL, Keyword::PLUGIN);
        $pluginName = $tokenList->consumeName();
        $tokenList->consumeKeyword(Keyword::SONAME);
        $libName = $tokenList->consumeString();

        return new InstallPluginCommand($pluginName, $libName);
    }

    /**
     * UNINSTALL PLUGIN plugin_name
     */
    public function parseUninstallPlugin(TokenList $tokenList): UninstallPluginCommand
    {
        $tokenList->consumeKeywords(Keyword::INSTALL, Keyword::PLUGIN);
        $pluginName = $tokenList->consumeName();

        return new UninstallPluginCommand($pluginName);
    }

}
