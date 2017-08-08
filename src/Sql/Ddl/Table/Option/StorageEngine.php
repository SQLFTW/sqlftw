<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use SqlFtw\Formatter\Formatter;

class StorageEngine extends \SqlFtw\Sql\SqlEnum
{

    public const INNODB = 'InnoDB';
    public const MYISAM = 'MyISAM';
    public const MEMORY = 'Memory';
    public const CSV = 'CSV';
    public const ARCHIVE = 'Archive';
    public const BLACKHOLE = 'Blackhole';
    public const NDB = 'NDB';
    public const MERGE = 'Merge';
    public const FEDERATED = 'Federated';
    public const EXAMPLE = 'Example';

    public static function validateValue(string &$value): bool
    {
        if (in_array($value, self::getAllowedValues())) {
            return true;
        } else {
            $lower = strtolower($value);
            foreach (self::getAllowedValues() as $allowedValue) {
                if ($lower === strtolower($allowedValue)) {
                    $value = $allowedValue;
                    return true;
                }
            }
            return false;
        }
    }

    public function serialize(Formatter $formatter): string
    {
        return "'" . parent::serialize($formatter) . "'";
    }

}
