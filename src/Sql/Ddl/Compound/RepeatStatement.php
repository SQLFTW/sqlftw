<?php declare(strict_types = 1);

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class RepeatStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Statement[] */
    private $statements;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $condition;

    /** @var string|null */
    private $label;

    /**
     * @param \SqlFtw\Sql\Statement[] $statements
     * @param \SqlFtw\Sql\Expression\ExpressionNode $condition
     * @param string|null $label
     */
    public function __construct(array $statements, ExpressionNode $condition, ?string $label)
    {
        $this->statements = $statements;
        $this->condition = $condition;
        $this->label = $label;
    }

    /**
     * @return \SqlFtw\Sql\Statement[]
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    public function getCondition(): ExpressionNode
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
        $result .= "REPEAT\n" . $formatter->formatSerializablesList($this->statements, ";\n")
            . "\nUNTIL " . $this->condition->serialize($formatter) . "\nEND REPEAT";

        return $result;
    }

}
