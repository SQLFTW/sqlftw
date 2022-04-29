<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Tests;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;

class ParserHelper
{
    use StrictBehaviorMixin;

    public static function getParserFactory(string $platform = Platform::MYSQL, ?string $version = '5.7'): ParserFactory
    {
        $platform = Platform::get($platform, $version);

        $settings = new PlatformSettings($platform);
        $settings->setQuoteAllNames(false);

        $lexer = new Lexer($settings, true, true);
        $parser = new Parser($settings, $lexer);

        return new ParserFactory($settings, $parser);
    }

}
