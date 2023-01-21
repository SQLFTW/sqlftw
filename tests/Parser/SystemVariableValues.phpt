<?php declare(strict_types = 1);

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Sql\Expression\Scope;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';



foreach (MysqlVariable::getAllowedValues() as $variableName) {
    if (!MysqlVariable::isDynamic($variableName)) {
        continue;
    }

    $scope = MysqlVariable::getScope($variableName);
    $sessionReadOnly = MysqlVariable::isSessionReadonly($variableName);

    $value = MysqlVariable::getSampleValue($variableName);

    if ($scope === Scope::GLOBAL || $scope === null) {
        $code = "SET @@GLOBAL.{$variableName} = $value";
        Assert::parseSerialize($code);
    }
    if (($scope === Scope::SESSION || $scope === null) && !$sessionReadOnly) {
        $code = "SET @@{$variableName} = $value";
        Assert::parseSerialize($code);
    }
}
