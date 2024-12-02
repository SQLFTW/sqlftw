<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\InvalidVersionException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Platform\Features\Feature;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Dal\Component\InstallComponentCommand;
use SqlFtw\Sql\Dal\Component\UninstallComponentCommand;
use SqlFtw\Sql\Keyword;

class ComponentCommandsParser
{

    private Platform $platform;

    private ExpressionParser $expressionParser;

    public function __construct(Platform $platform, ExpressionParser $expressionParser)
    {
        $this->platform = $platform;
        $this->expressionParser = $expressionParser;
    }

    /**
     * INSTALL COMPONENT component_name [, component_name ] ...
     *   [SET variable = expr [, variable = expr] ...]
     *
     * variable: {
     *     {GLOBAL | @@GLOBAL.} [component_prefix.]system_var_name
     *   | {PERSIST | @@PERSIST.} [component_prefix.]system_var_name
     * }
     */
    public function parseInstallComponent(TokenList $tokenList): InstallComponentCommand
    {
        $tokenList->expectKeywords(Keyword::INSTALL, Keyword::COMPONENT);
        $components = [];
        do {
            $components[] = $tokenList->expectNonReservedNameOrString();
        } while ($tokenList->hasSymbol(','));

        $assignments = [];
        if ($tokenList->hasKeyword(Keyword::SET)) {
            if (!isset($this->platform->features[Feature::INSTALL_COMPONENT_SET])) {
                throw new InvalidVersionException(Feature::INSTALL_COMPONENT_SET, $this->platform, $tokenList);
            }
            $assignments = $this->expressionParser->parseSetAssignments($tokenList, true);
        }

        return new InstallComponentCommand($components, $assignments);
    }

    /**
     * UNINSTALL COMPONENT component_name [, component_name ] ...
     */
    public function parseUninstallComponent(TokenList $tokenList): UninstallComponentCommand
    {
        $tokenList->expectKeywords(Keyword::UNINSTALL, Keyword::COMPONENT);
        $components = [];
        do {
            $components[] = $tokenList->expectNonReservedNameOrString();
        } while ($tokenList->hasSymbol(','));

        return new UninstallComponentCommand($components);
    }

}
