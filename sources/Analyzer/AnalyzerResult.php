<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use SqlFtw\Sql\Statement;
use function spl_object_id;

class AnalyzerResult
{

    /** @var int */
    private $id;

    /** @var string */
    private $message;

    /** @var AnalyzerRule */
    private $rule;

    /** @var Statement */
    private $statement;

    /** @var int */
    private $severity;

    /** @var bool|null */
    private $autoRepair;

    /** @var Statement[]|null */
    private $repairStatements;

    /**
     * @param Statement[]|null $repairStatements
     */
    public function __construct(
        string $message,
        AnalyzerRule $rule,
        Statement $statement,
        ?int $severity = null,
        ?bool $autoRepair = AutoRepair::NOT_POSSIBLE,
        ?array $repairStatements = null
    )
    {
        if ($severity === null) {
            $severity = AnalyzerResultSeverity::ERROR;
        }

        $this->id = spl_object_id($this);
        $this->message = $message;
        $this->rule = $rule;
        $this->statement = $statement;
        $this->severity = $severity;
        $this->autoRepair = $autoRepair;
        $this->repairStatements = $repairStatements;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRule(): AnalyzerRule
    {
        return $this->rule;
    }

    public function getStatement(): Statement
    {
        return $this->statement;
    }

    public function getSeverity(): int
    {
        return $this->severity;
    }

    public function canBeAutoRepaired(): bool
    {
        return $this->autoRepair === AutoRepair::POSSIBLE;
    }

    public function isAutoRepaired(): bool
    {
        return $this->autoRepair === AutoRepair::REPAIRED;
    }

    /**
     * @return Statement[]
     */
    public function getRepairStatements(): array
    {
        return $this->repairStatements ?? [];
    }

}
