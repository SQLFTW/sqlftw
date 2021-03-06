<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routines;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Compound\CompoundStatement;
use SqlFtw\Sql\Ddl\DataType;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\QualifiedName;
use function implode;

class CreateFunctionCommand implements StoredFunctionCommand, CreateRoutineCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var CompoundStatement */
    private $body;

    /** @var DataType[] ($name => $type) */
    private $params;

    /** @var DataType */
    private $returnType;

    /** @var UserExpression|null */
    private $definer;

    /** @var bool|null */
    private $deterministic;

    /** @var SqlSecurity|null */
    private $security;

    /** @var RoutineSideEffects|null */
    private $sideEffects;

    /** @var string|null */
    private $comment;

    /** @var string|null */
    private $language;

    /**
     * @param QualifiedName $name
     * @param CompoundStatement $body
     * @param DataType[] $params
     * @param DataType $returnType
     * @param UserExpression|null $definer
     * @param bool|null $deterministic
     * @param SqlSecurity|null $security
     * @param RoutineSideEffects|null $sideEffects
     * @param string|null $comment
     * @param string|null $language
     */
    public function __construct(
        QualifiedName $name,
        CompoundStatement $body,
        array $params,
        DataType $returnType,
        ?UserExpression $definer = null,
        ?bool $deterministic = null,
        ?SqlSecurity $security = null,
        ?RoutineSideEffects $sideEffects = null,
        ?string $comment = null,
        ?string $language = null
    ) {
        $this->name = $name;
        $this->body = $body;
        $this->params = $params;
        $this->returnType = $returnType;
        $this->definer = $definer;
        $this->deterministic = $deterministic;
        $this->security = $security;
        $this->sideEffects = $sideEffects;
        $this->comment = $comment;
        $this->language = $language;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getBody(): CompoundStatement
    {
        return $this->body;
    }

    /**
     * @return DataType[] ($name => $type)
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getReturnType(): DataType
    {
        return $this->returnType;
    }

    public function getDefiner(): ?UserExpression
    {
        return $this->definer;
    }

    public function isDeterministic(): ?bool
    {
        return $this->deterministic;
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
        $result = 'CREATE';
        if ($this->getDefiner() !== null) {
            $result .= ' DEFINER = ' . $this->definer->serialize($formatter);
        }
        $result .= ' FUNCTION ' . $this->name->serialize($formatter);

        $result .= '(' . implode(', ', Arr::mapPairs($this->params, static function (string $name, DataType $type) use ($formatter) {
            return $formatter->formatName($name) . ' ' . $type->serialize($formatter);
        })) . ')';
        $result .= ' RETURNS ' . $this->returnType->serialize($formatter);

        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        }
        if ($this->language !== null) {
            $result .= ' LANGUAGE ' . $this->language;
        }
        if ($this->deterministic !== null) {
            $result .= $this->deterministic ? ' DETERMINISTIC' : ' NOT DETERMINISTIC';
        }
        if ($this->sideEffects !== null) {
            $result .= ' ' . $this->sideEffects->serialize($formatter);
        }
        if ($this->security !== null) {
            $result .= ' SQL SECURITY ' . $this->security->serialize($formatter);
        }

        $result .= ' ' . $this->body->serialize($formatter);

        return $result;
    }

}
