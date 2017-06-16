<?php

namespace SqlFtw\Sql\Ddl\Compound;

use SqlFtw\SqlFormatter\SqlFormatter;

class LoopStatement implements \SqlFtw\Sql\Statement
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Statement[] */
    private $statements;

    /** @var string|null */
    private $label;

    /**
     * @param \SqlFtw\Sql\Statement[] $statements
     * @param string|null $label
     */
    public function __construct(array $statements, ?string $label)
    {
        $this->statements = $statements;
        $this->label = $label;
    }

    /**
     * @return \SqlFtw\Sql\Statement[]
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = '';
        if ($this->label !== null) {
            $result .= $formatter->formatName($this->label) . ': ';
        }
        $result .= "LOOP \n" . $formatter->formatSerializablesList($this->statements, ";\n") . 'END LOOP';

        return $result;
    }

}
