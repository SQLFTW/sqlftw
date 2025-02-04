<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Plugin;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class InstallPluginCommand extends Command implements PluginCommand
{

    public string $pluginName;

    public string $libName;

    public function __construct(string $pluginName, string $libName)
    {
        $this->pluginName = $pluginName;
        $this->libName = $libName;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INSTALL PLUGIN ' . $formatter->formatName($this->pluginName) . ' SONAME ' . $formatter->formatString($this->libName);
    }

}
