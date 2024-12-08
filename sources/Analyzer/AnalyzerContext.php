<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use SqlFtw\Analyzer\Context\Provider\DefinitionProvider;
use SqlFtw\Analyzer\Types\CastingTypeChecker;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\ParserConfig;
use SqlFtw\Platform\Platform;
use SqlFtw\Resolver\ExpressionResolver;
use SqlFtw\Session\Session;

class AnalyzerContext
{

    /** @readonly */
    public Platform $platform;

    /** @readonly */
    public Session $session;

    /** @readonly */
    public Formatter $formatter;

    /** @readonly */
    public ParserConfig $parserConfig;

    /** @readonly */
    public ExpressionResolver $resolver;

    /** @readonly */
    public DefinitionProvider $definitionProvider;

    /** @readonly */
    public CastingTypeChecker $typeChecker;

    public function __construct(
        Platform $platform,
        Session $session,
        Formatter $formatter,
        ParserConfig $parserConfig,
        ExpressionResolver $resolver,
        DefinitionProvider $definitionProvider,
        ?CastingTypeChecker $typeChecker = null
    ) {
        $this->platform = $platform;
        $this->session = $session;
        $this->formatter = $formatter;
        $this->parserConfig = $parserConfig;
        $this->resolver = $resolver;
        $this->definitionProvider = $definitionProvider;
        $this->typeChecker = $typeChecker ?? new CastingTypeChecker($platform);
    }

}
