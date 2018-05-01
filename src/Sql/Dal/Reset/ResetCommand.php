<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Reset;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;

class ResetCommand implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\Reset\ResetOption[] */
    private $options;

    /**
     * @param \SqlFtw\Sql\Dal\Reset\ResetOption[] $options
     */
    public function __construct(array $options)
    {
        Check::array($options, 1);
        Check::itemsOfType($options, ResetOption::class);

        $this->options = $options;
    }

    /**
     * @return \SqlFtw\Sql\Dal\Reset\ResetOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'RESET ' . $formatter->formatSerializablesList($this->options);
    }

}
