<?php declare(strict_types = 1);

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\StatementImpl;

class CompoundStatement extends StatementImpl
{

    /** @var list<Statement> */
    private array $statements;

    private ?string $label;

    private bool $endLabel;

    /**
     * @param list<Statement> $statements
     */
    public function __construct(array $statements, ?string $label, bool $endLabel = false)
    {
        $this->statements = $statements;
        $this->label = $label;
        $this->endLabel = $endLabel;
    }

    /**
     * @return list<Statement>
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function endLabel(): bool
    {
        return $this->endLabel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->label !== null) {
            $result .= $formatter->formatName($this->label) . ': ';
        }
        $result .= "BEGIN \n";
        if ($this->statements !== []) {
            $result .= $formatter->formatSerializablesList($this->statements, ";\n") . ";\n";
        }
        $result .= ' END';
        if ($this->label !== null && $this->endLabel) {
            $result .= ' ' . $formatter->formatName($this->label);
        }

        return $result;
    }

}
