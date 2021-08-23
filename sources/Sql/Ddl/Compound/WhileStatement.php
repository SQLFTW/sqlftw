<?php declare(strict_types = 1);

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Statement;

class WhileStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var Statement[] */
    private $statements;

    /** @var ExpressionNode */
    private $condition;

    /** @var string|null */
    private $label;

    /**
     * @param Statement[] $statements
     * @param ExpressionNode $condition
     * @param string|null $label
     */
    public function __construct(array $statements, ExpressionNode $condition, ?string $label)
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
        $result .= 'WHILE ' . $this->condition->serialize($formatter) . " DO\n"
            . $formatter->formatSerializablesList($this->statements, ";\n") . "\nEND WHILE";

        return $result;
    }

}
