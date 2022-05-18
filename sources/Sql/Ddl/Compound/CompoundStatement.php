<?php declare(strict_types = 1);

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class CompoundStatement implements Statement
{
    use StrictBehaviorMixin;

    /** @var Statement[] */
    private $statements;

    /** @var string|null */
    private $label;

    /**
     * @param Statement[] $statements
     */
    public function __construct(array $statements, ?string $label)
    {
        $this->statements = $statements;
        $this->label = $label;
    }

    /**
     * @return Statement[]
     */
    public function getStatements(): array
    {
        return $this->statements;
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
        $result .= "BEGIN \n";
        if ($this->statements !== []) {
            $result .= $formatter->formatSerializablesList($this->statements, ";\n");
        }
        $result .= ' END';

        return $result;
    }

}
