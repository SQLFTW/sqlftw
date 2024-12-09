<?php
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
use SqlFtw\Sql\StatementImpl;

class AlterFunctionCommand extends StatementImpl implements StoredFunctionCommand, AlterRoutineCommand
{

    public ObjectIdentifier $function;

    public ?SqlSecurity $security;

    public ?RoutineSideEffects $sideEffects;

    public ?string $comment;

    public ?string $language;

    public function __construct(
        ObjectIdentifier $function,
        ?SqlSecurity $security,
        ?RoutineSideEffects $sideEffects = null,
        ?string $comment = null,
        ?string $language = null
    ) {
        $this->function = $function;
        $this->security = $security;
        $this->sideEffects = $sideEffects;
        $this->comment = $comment;
        $this->language = $language;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER FUNCTION ' . $this->function->serialize($formatter);
        if ($this->language !== null) {
            $result .= ' LANGUAGE ' . $this->language;
        }
        if ($this->sideEffects !== null) {
            $result .= ' ' . $this->sideEffects->serialize($formatter);
        }
        if ($this->security !== null) {
            $result .= ' SQL SECURITY ' . $this->security->serialize($formatter);
        }
        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        }

        return $result;
    }

}
