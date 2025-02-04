<?php

namespace SqlFtw\Util;

use ReflectionClass;
use function strtolower;

trait CaseInsensitivePseudoEnumMixin
{

    /** @var array<string, string> */
    private static array $lowerCaseIndex = [];

    public static function init(): void
    {
        $ref = new ReflectionClass(self::class);

        /** @var string $name */
        foreach ($ref->getConstants() as $name) {
            self::$lowerCaseIndex[strtolower($name)] = $name;
        }
    }

}
