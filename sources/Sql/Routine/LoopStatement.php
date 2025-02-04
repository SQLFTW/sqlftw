<?php

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class LoopStatement extends Statement
{

    /** @var list<Statement> */
    public array $statements;

    public ?string $label;

    public bool $endLabel;

    /**
     * @param list<Statement> $statements
     */
    public function __construct(array $statements, ?string $label, bool $endLabel = false)
    {
        $this->statements = $statements;
        $this->label = $label;
        $this->endLabel = $endLabel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->label !== null) {
            $result .= $formatter->formatName($this->label) . ': ';
        }

        $result .= "LOOP \n";
        if ($this->statements !== []) {
            $result .= $formatter->formatNodesList($this->statements, ";\n") . ";\n";
        }
        $result .= 'END LOOP';
        if ($this->label !== null && $this->endLabel) {
            $result .= ' ' . $formatter->formatName($this->label);
        }

        return $result;
    }

}
