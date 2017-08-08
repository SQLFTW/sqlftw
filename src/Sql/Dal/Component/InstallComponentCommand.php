<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Component;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;

class InstallComponentCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string[] */
    private $components;

    /**
     * @param string[] $components
     */
    public function __construct(array $components)
    {
        Check::array($components, 1);
        Check::itemsOfType($components, Type::STRING);

        $this->components = $components;
    }

    /**
     * @return string[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INSTALL COMPONENT ' . $formatter->formatNamesList($this->components);
    }

}
