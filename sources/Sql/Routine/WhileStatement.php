<?php declare(strict_types = 1);

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\Statement;

class WhileStatement extends Statement implements SqlSerializable
{

    /** @var Statement[] */
    private $statements;

    /** @var RootNode */
    private $condition;

    /** @var string|null */
    private $label;

    /**
     * @param Statement[] $statements
     */
    public function __construct(array $statements, RootNode $condition, ?string $label)
    {
        $this->statements = $statements;
        $this->condition = $condition;
        $this->label = $label;
    }

    /**
     * @return Statement[]
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    public function getCondition(): RootNode
    {
        return $this->condition;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->label !== null) {
            $result .= $formatter->formatName($this->label) . ': ';
        }
        $result .= 'WHILE ' . $this->condition->serialize($formatter) . " DO\n";
        if ($this->statements !== []) {
            $result .= $formatter->formatSerializablesList($this->statements, ";\n") . ";\n";
        }
        $result .= "END WHILE";

        return $result;
    }

}
