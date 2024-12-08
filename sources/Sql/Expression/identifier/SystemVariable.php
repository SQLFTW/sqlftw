<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use function array_map;
use function explode;
use function implode;
use function strtoupper;

/**
 * Variable name, e.g. VERSION
 */
class SystemVariable implements Identifier
{

    private string $name;

    private ?Scope $scope;

    public function __construct(string $name, ?Scope $scope = null)
    {
        $this->name = $name;
        $this->scope = $scope;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getScope(): ?Scope
    {
        return $this->scope;
    }

    public function getFullName(): string
    {
        return ($this->scope !== null ? '@@' . $this->scope->getValue() . '.' : '@@') . $this->name;
    }

    public function serialize(Formatter $formatter): string
    {
        $parts = array_map(static function (string $part) use ($formatter): string {
            $platform = $formatter->getPlatform();

            return isset($platform->reserved[strtoupper($part)]) ? '`' . $part . '`' : $part;
        }, explode('.', $this->name));

        return ($this->scope !== null ? '@@' . $this->scope->getValue() . '.' : '@@') . implode('.', $parts);
    }

}
