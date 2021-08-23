<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class AlterConstraintAction implements ConstraintAction
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var bool */
    private $enforced;

    public function __construct(string $name, bool $enforced)
    {
        $this->name = $name;
        $this->enforced = $enforced;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnforced(): bool
    {
        return $this->enforced;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER CONSTRAINT ' . $formatter->formatName($this->name);
        $result .= $this->enforced ? ' ENFORCED' : ' NOT ENFORCED';

        return $result;
    }

}
