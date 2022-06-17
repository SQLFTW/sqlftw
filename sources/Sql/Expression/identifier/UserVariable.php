<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use function strlen;

class UserVariable implements Identifier
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        if ($name[0] !== '@' || strlen($name) < 2) {
            throw new InvalidDefinitionException('User variable name must start with "@" and be at least 2 characters long.');
        }
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatName($this->name);
    }

}
