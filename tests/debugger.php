<?php

namespace Test;

use Dogma\Debug\Ansi;
use Dogma\Debug\Dumper;
use Dogma\Debug\FormattersDogma;
use Dogma\Debug\Str;
use SqlFtw\Error\Error;
use SqlFtw\Error\Repair;
use SqlFtw\Error\Severity;
use SqlFtw\Parser\InvalidTokenException;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use SqlFtw\Sql\Expression\ColumnType;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\IntLiteral;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\StringLiteral;
use SqlFtw\Sql\Expression\UintLiteral;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\SqlMode;
use SqlFtw\Tests\Assert;
use Tracy\Debugger;
use function count;
use function get_class;
use function implode;

Debugger::$maxDepth = 9;
Debugger::$strictMode = true;
FormattersDogma::register();

Dumper::$hiddenFields[] = 'sql';
Dumper::$doNotTraverse[] = Parser::class;
Dumper::$doNotTraverse[] = Assert::class . '::$validCommands';
Dumper::$doNotTraverse[] = InvalidTokenException::class . '::$tokens';
Dumper::$doNotTraverse[] = Platform::class . '::$nonReserved';
Dumper::$doNotTraverse[] = Platform::class . '::$operators';
Dumper::$doNotTraverse[] = Platform::class . '::$preparableCommands';
Dumper::$doNotTraverse[] = Platform::class . '::$reserved';
Dumper::$doNotTraverse[] = Session::class . '::$onDelimiterChange';
Dumper::$doNotTraverse[] = Session::class . '::$onSqlModeChange';
Dumper::$doNotTraverse[] = TokenList::class . '::$maxLengths';
Dumper::$namespaceReplacements['~SqlFtw\\\\Parser\\\\(.*)~'] = '..\1';
Dumper::$namespaceReplacements['~SqlFtw\\\\Formatter\\\\(.*)~'] = '..\1';
Dumper::$namespaceReplacements['~SqlFtw\\\\Sql\\\\(.*)~'] = '..\1';
Dumper::$namespaceReplacements['~SqlFtw\\\\Platform\\\\(.*)~'] = '..\1';
Dumper::$traceFilters[] = '~^Amp\\\\~';
Dumper::$traceFilters[] = '~^Opis\\\\Closure~';

// TokenType value
Dumper::$intFormatters = [
    '~tokenType|tokenMask|autoSkip~' => static function (int $int): string {
        $types = implode('|', TokenType::getByValue($int)->getConstantNames());

        return Dumper::int((string) $int) . ' ' . Dumper::info('// TokenType::' . $types);
    },
] + Dumper::$intFormatters;

// Token
$tokenFormatter = static function (Token $token, int $depth = 0): string {
    $oldInfo = Dumper::$showInfo;
    $oldEscapeWhiteSpace = Dumper::$escapeWhiteSpace;
    Dumper::$showInfo = false;
    Dumper::$escapeWhiteSpace = true;
    $value = Dumper::dumpValue($token->value, $depth + 1);
    if (($token->type & (TokenType::COMMENT | TokenType::WHITESPACE)) !== 0) {
        $value = Ansi::dgray(Ansi::removeColors($value));
    }
    Dumper::$showInfo = $oldInfo;
    Dumper::$escapeWhiteSpace = $oldEscapeWhiteSpace;

    $type = implode('|', TokenType::getByValue($token->type)->getConstantNames());

    return Dumper::class(get_class($token)) . Dumper::bracket('(') . $value . ' / '
        . Dumper::value2($type) . ' ' . Dumper::info('at position') . ' ' . $token->start
        . Dumper::bracket(')') . Dumper::objectInfo($token);
};
Dumper::$objectFormatters[Token::class] = $tokenFormatter;
Dumper::$shortObjectFormatters[Token::class] = $tokenFormatter;
unset($tokenFormatter);

// TokenList
Dumper::$shortObjectFormatters[TokenList::class] = static function (TokenList $tokenList): string {
    $limit = 15;
    $tokens = $tokenList->getTokens();
    $count = count($tokens);
    $contents = '';
    foreach (array_slice($tokens, 0, $limit) as $token) {
        $contents .= ctype_space($token->value) ? '·' : $token->value;
    }
    $dots = $count > $limit ? '...' : '';

    return Dumper::class(get_class($tokenList)) . Dumper::bracket('(')
        . Dumper::value($contents . $dots) . ' | ' . Dumper::value2($count . ' tokens, position ' . $tokenList->getPosition())
        . Dumper::bracket(')') . Dumper::objectInfo($tokenList);
};

// Platform
Dumper::$objectFormatters[Platform::class] = static function (Platform $platform): string {
    return Dumper::class(get_class($platform)) . Dumper::bracket('(')
        . Dumper::value($platform->getName()) . ' ' . Dumper::value2($platform->getVersion()->format())
        . Dumper::bracket(')');
};

// SimpleName
Dumper::$objectFormatters[SimpleName::class] = static function (SimpleName $simpleName): string {
    return Dumper::class(get_class($simpleName)) . Dumper::bracket('(')
        . Dumper::value($simpleName->name)
        . Dumper::bracket(')');
};

// QualifiedName
Dumper::$objectFormatters[QualifiedName::class] = static function (QualifiedName $qualifiedName): string {
    $name = $qualifiedName->schema . '.' . $qualifiedName->name;
    if (Str::isBinary($name) !== null) {
        $name = Dumper::string($name);
    } else {
        $name = Dumper::value($name);
    }

    return Dumper::class(get_class($qualifiedName)) . Dumper::bracket('(') . $name . Dumper::bracket(')');
};

// UserVariable
Dumper::$objectFormatters[UserVariable::class] = static function (UserVariable $userVariable): string {
    return Dumper::class(get_class($userVariable)) . Dumper::bracket('(')
        . Dumper::value($userVariable->name)
        . Dumper::bracket(')');
};

// UintLiteral
Dumper::$objectFormatters[UintLiteral::class] = static function (UintLiteral $uintLiteral): string {
    return Dumper::class(get_class($uintLiteral)) . Dumper::bracket('(')
        . Dumper::value($uintLiteral->value)
        . Dumper::bracket(')');
};

// IntLiteral
Dumper::$objectFormatters[IntLiteral::class] = static function (IntLiteral $intLiteral): string {
    return Dumper::class(get_class($intLiteral)) . Dumper::bracket('(')
        . Dumper::value($intLiteral->value)
        . Dumper::bracket(')');
};

// StringLiteral
Dumper::$objectFormatters[StringLiteral::class] = static function (StringLiteral $stringLiteral): string {
    if ($stringLiteral->charset !== null || count($stringLiteral->parts) > 1) {
        return '';
    }
    return Dumper::class(get_class($stringLiteral)) . Dumper::bracket('(')
        . Dumper::string($stringLiteral->parts[0])
        . Dumper::bracket(')');
};

// ColumnType
Dumper::$objectFormatters[ColumnType::class] = static function (ColumnType $columnType): string {
    if ($columnType->unsigned !== false || $columnType->zerofill !== false || $columnType->size !== null || $columnType->values !== null
        || $columnType->charset !== null || $columnType->collation !== null || $columnType->srid !== null
    ) {
        return '';
    }
    return Dumper::class(get_class($columnType)) . Dumper::bracket('(')
        . Dumper::value($columnType->baseType->getValue())
        . Dumper::bracket(')');
};

// FunctionCall
Dumper::$shortObjectFormatters[FunctionCall::class] = static function (FunctionCall $functionCall): string {
    return Dumper::class(get_class($functionCall)) . Dumper::bracket('(') . ' '
        . Dumper::value($functionCall->function->getFullName()) . ' ' . Dumper::exceptions('...') . ' '
        . Dumper::bracket(')') . Dumper::objectInfo($functionCall);
};

// TableReferenceTable
Dumper::$shortObjectFormatters[TableReferenceTable::class] = static function (TableReferenceTable $reference): string {
    if ($reference->partitions !== null || $reference->indexHints !== null) {
        return '';
    }

    return Dumper::class(get_class($reference)) . Dumper::bracket('(')
        . Dumper::value($reference->table->getFullName())
        . ($reference->alias !== null ? ' AS ' . Dumper::value2($reference->alias) : '')
        . Dumper::bracket(')') . Dumper::objectInfo($reference);
};

// Error
Dumper::$objectFormatters[Error::class] = static function (Error $error, int $depth): string {
    return Dumper::class(get_class($error)) . Dumper::bracket('(')
        . Dumper::value(Severity::$labels[$error->severity] . ':') . ' ' . Dumper::value($error->identifier) . ' '
        . Dumper::value2(Repair::$labels[$error->repair])
        . ":\n" . Dumper::value($error->message)
        . Dumper::bracket(')') . Dumper::objectInfo($error)
        . ($error->callstack !== null ? "\n" . Dumper::formatCallstack($error->callstack, 20, null, null, null, $depth + 1) : '');
};

// SqlMode::$value
Dumper::$intFormatters['~SqlMode::value~'] = static function (int $int): string {
    return Dumper::int((string) $int) . ' ' . Dumper::info('// ' . SqlMode::fromInt($int)->asString());
};

// SqlMode::$fullValue
Dumper::$intFormatters['~SqlMode::fullValue~'] = static function (int $int): string {
    return Dumper::int((string) $int) . ' ' . Dumper::info('// ' . SqlMode::fromInt($int)->asString());
};
