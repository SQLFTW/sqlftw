<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Tests;

use SqlFtw\Analyzer\Analyzer;
use SqlFtw\Analyzer\AnalyzerContext;
use SqlFtw\Analyzer\Context\Provider\DummyDefinitionProvider;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserConfig;
use SqlFtw\ParserSuite;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Normalizer\MysqlNormalizer;
use SqlFtw\Platform\Platform;
use SqlFtw\Resolver\ExpressionResolver;
use SqlFtw\Session\Session;
use SqlFtw\Sql\SqlMode;

class ParserSuiteFactory
{

    public static function fromConfig(
        ParserConfig $config,
        ?int $sqlMode = null,
        array $rules = [],
        ?DummyDefinitionProvider $definitionProvider = null
    ): ParserSuite
    {
        $platform = $config->getPlatform();
        $session = new Session($platform);
        if ($sqlMode !== null) {
            $session->setMode(SqlMode::fromInt($sqlMode));
        }

        $lexer = new Lexer($config, $session);
        $parser = new Parser($config, $session);
        $normalizer = new MysqlNormalizer($platform, $session);
        $formatter = new Formatter($platform, $session, $normalizer);

        $definitionProvider ??= new DummyDefinitionProvider();
        $resolver = new ExpressionResolver($platform, $session);
        $context = new AnalyzerContext($platform, $session, $formatter, $config, $resolver, $definitionProvider);
        $analyzer = new Analyzer($rules, $parser, $context);

        return new ParserSuite($analyzer, $parser, $lexer, $formatter, $normalizer, $session);
    }

    /**
     * @param Platform::* $platform
     * @param int|string|null $version
     */
    public static function fromPlatform(
        string $platform,
        $version = null,
        ?int $extensions = null,
        ?int $sqlMode = null,
        array $rules = [],
        ?DummyDefinitionProvider $definitionProvider = null
    ): ParserSuite
    {
        $platform = Platform::get($platform, $version);
        $extensions ??= ClientSideExtension::ALLOW_DELIMITER_DEFINITION; // useful default for loading exports

        $config = new ParserConfig($platform, $extensions, true, true, true);

        return self::fromConfig($config, $sqlMode, $rules, $definitionProvider);
    }

}
