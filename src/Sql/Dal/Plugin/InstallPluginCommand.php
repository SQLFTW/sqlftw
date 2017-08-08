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

class InstallPluginCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $pluginName;

    /** @var string */
    private $libName;

    public function __construct(string $pluginName, string $libName)
    {
        $this->pluginName = $pluginName;
        $this->libName = $libName;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function getLibName(): string
    {
        return $this->libName;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INSTALL PLUGIN ' . $formatter->formatName($this->pluginName) . ' SONAME ' . $formatter->formatString($this->libName);
    }

}
