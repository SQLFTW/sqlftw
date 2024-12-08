<?php

namespace SqlFtw;

use SqlFtw\Analyzer\Analyzer;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Platform\Normalizer\Normalizer;
use SqlFtw\Session\Session;

class ParserSuite
{

    /** @readonly */
    public Analyzer $analyzer;

    /** @readonly */
    public Parser $parser;

    /** @readonly */
    public Lexer $lexer;

    /** @readonly */
    public Formatter $formatter;

    /** @readonly */
    public Normalizer $normalizer;

    /** @readonly */
    public Session $session;

    public function __construct(
        Analyzer $analyzer,
        Parser $parser,
        Lexer $lexer,
        Formatter $formatter,
        Normalizer $normalizer,
        Session $session
    ) {
        $this->analyzer = $analyzer;
        $this->parser = $parser;
        $this->lexer = $lexer;
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
        $this->session = $session;
    }

}
