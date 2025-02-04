<?php

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Statement;

class RepeatStatement extends Statement
{

    /** @var list<Statement> */
    public array $statements;

    public RootNode $condition;

    public ?string $label;

    public bool $endLabel;

    /**
     * @param list<Statement> $statements
     */
    public function __construct(array $statements, RootNode $condition, ?string $label, bool $endLabel = false)
    {
        $this->statements = $statements;
        $this->condition = $condition;
        $this->label = $label;
        $this->endLabel = $endLabel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->label !== null) {
            $result .= $formatter->formatName($this->label) . ': ';
        }
        $result .= "REPEAT\n";
        if ($this->statements !== []) {
            $result .= $formatter->formatNodesList($this->statements, ";\n") . ";\n";
        }
        $result .= "UNTIL " . $this->condition->serialize($formatter) . "\nEND REPEAT";
        if ($this->label !== null && $this->endLabel) {
            $result .= ' ' . $formatter->formatName($this->label);
        }

        return $result;
    }

}
