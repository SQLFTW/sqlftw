<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Statement;

class AlterProcedureCommand extends Statement implements StoredProcedureCommand, AlterRoutineCommand
{

    /** @var ObjectIdentifier */
    private $name;

    /** @var SqlSecurity|null */
    private $security;

    /** @var RoutineSideEffects|null */
    private $sideEffects;

    /** @var string|null */
    private $comment;

    /** @var string|null */
    private $language;

    public function __construct(
        ObjectIdentifier $name,
        ?SqlSecurity $security,
        ?RoutineSideEffects $sideEffects = null,
        ?string $comment = null,
        ?string $language = null
    ) {
        $this->name = $name;
        $this->security = $security;
        $this->sideEffects = $sideEffects;
        $this->comment = $comment;
        $this->language = $language;
    }

    public function getName(): ObjectIdentifier
    {
        return $this->name;
    }

    public function getSecurity(): ?SqlSecurity
    {
        return $this->security;
    }

    public function getSideEffects(): ?RoutineSideEffects
    {
        return $this->sideEffects;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER PROCEDURE ' . $this->name->serialize($formatter);
        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        }
        if ($this->language !== null) {
            $result .= ' LANGUAGE ' . $this->language;
        }
        if ($this->sideEffects !== null) {
            $result .= ' ' . $this->sideEffects->serialize($formatter);
        }
        if ($this->security !== null) {
            $result .= ' SQL SECURITY ' . $this->security->serialize($formatter);
        }

        return $result;
    }

}
