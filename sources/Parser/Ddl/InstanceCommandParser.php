<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use SqlFtw\Parser\InvalidVersionException;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Platform\Features\Feature;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Ddl\Instance\AlterInstanceAction;
use SqlFtw\Sql\Ddl\Instance\AlterInstanceCommand;
use SqlFtw\Sql\Keyword;
use function strtolower;

class InstanceCommandParser
{

    private Platform $platform;

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * 8.0 https://dev.mysql.com/doc/refman/8.0/en/alter-instance.html
     * ALTER INSTANCE instance_action
     *
     * instance_action: {
     *   | {ENABLE|DISABLE} INNODB REDO_LOG
     *   | ROTATE INNODB MASTER KEY
     *   | ROTATE BINLOG MASTER KEY
     *   | RELOAD TLS
     *      [FOR CHANNEL {mysql_main | mysql_admin}]
     *      [NO ROLLBACK ON ERROR]
     *   | RELOAD KEYRING
     * }
     *
     * 5.7 https://dev.mysql.com/doc/refman/5.7/en/alter-instance.html
     * ALTER INSTANCE ROTATE INNODB MASTER KEY
     */
    public function parseAlterInstance(TokenList $tokenList): AlterInstanceCommand
    {
        if (!isset($this->platform->features[Feature::ALTER_INSTANCE])) {
            throw new InvalidVersionException(Feature::ALTER_INSTANCE, $this->platform, $tokenList);
        }

        $tokenList->expectKeywords(Keyword::ALTER, Keyword::INSTANCE);

        $action = $tokenList->expectMultiNameEnum(AlterInstanceAction::class);
        if (!$action->equalsValue(AlterInstanceAction::ROTATE_INNODB_MASTER_KEY)
            && !isset($this->platform->features[Feature::ALTER_INSTANCE_2])
        ) {
            throw new InvalidVersionException(Feature::ALTER_INSTANCE_2, $this->platform, $tokenList);
        }

        $forChannel = null;
        $noRollbackOnError = false;
        if ($action->equalsValue(AlterInstanceAction::RELOAD_TLS)) {
            if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                $forChannel = strtolower($tokenList->expectNonReservedNameOrString());
                if ($forChannel !== 'mysql_main' && $forChannel !== 'mysql_admin') {
                    throw new ParserException('Invalid channel name.', $tokenList);
                }
            }
            $noRollbackOnError = $tokenList->hasKeywords(Keyword::NO, Keyword::ROLLBACK, Keyword::ON, Keyword::ERROR);
        }

        return new AlterInstanceCommand($action, $forChannel, $noRollbackOnError);
    }

}
