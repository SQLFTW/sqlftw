<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Tests;

use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserConfig;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;

class ParserHelper
{

    /**
     * @param Platform::*|null $platform
     * @param int|string|null $version
     */
    public static function createParser(
        ?string $platform = null,
        $version = null,
        ?string $delimiter = null,
        ?ParserConfig $config = null
    ): Parser
    {
        $platform = Platform::get($platform ?? Platform::MYSQL, $version);
        $extensions = ClientSideExtension::ALLOW_DELIMITER_DEFINITION
            | ClientSideExtension::ALLOW_QUESTION_MARK_PLACEHOLDERS_OUTSIDE_PREPARED_STATEMENTS
            | ClientSideExtension::ALLOW_NUMBERED_QUESTION_MARK_PLACEHOLDERS
            | ClientSideExtension::ALLOW_NAMED_DOUBLE_COLON_PLACEHOLDERS;

        $config = $config ?? new ParserConfig($platform, $extensions, true, true, true);

        $session = new Session($platform);
        if ($delimiter !== null) {
            $session->setDelimiter($delimiter);
        }

        return new Parser($config, $session);
    }

}
