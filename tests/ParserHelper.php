<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Tests;

use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Parser\ParserSettings;
use SqlFtw\Platform\Platform;

class ParserHelper
{

    /**
     * @param int|string|null $version
     */
    public static function getParserFactory(
        ?string $platform = null,
        $version = null,
        ?string $delimiter = null,
        bool $withComments = true,
        bool $withWhitespace = true
    ): ParserFactory
    {
        $platform = Platform::get($platform ?? Platform::MYSQL, $version);

        $settings = new ParserSettings($platform, $delimiter);

        $lexer = new Lexer($settings, $withComments, $withWhitespace);
        $parser = new Parser($settings, $lexer);

        return new ParserFactory($settings, $parser);
    }

}
