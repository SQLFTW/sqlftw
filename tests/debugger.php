<?php declare(strict_types = 1);

namespace Test;

use Dogma\Debug\Ansi;
use Dogma\Debug\Dumper;
use Dogma\Debug\FormattersDogma;
use SqlFtw\Parser\InvalidTokenException;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Tests\Assert;
use Tracy\Debugger;
use function get_class;
use function implode;

Debugger::$maxDepth = 9;
Debugger::$strictMode = true;
FormattersDogma::register();

Dumper::$hiddenFields[] = 'sql';
Dumper::$hiddenFields[] = 'maxLengths';
Dumper::$doNotTraverse[] = Parser::class;
Dumper::$doNotTraverse[] = Assert::class . '::validCommands';
Dumper::$doNotTraverse[] = InvalidTokenException::class . '::tokens';
Dumper::$namespaceReplacements['~SqlFtw\\\\Parser\\\\(.*)~'] = '..\1';
Dumper::$namespaceReplacements['~SqlFtw\\\\Formatter\\\\(.*)~'] = '..\1';
Dumper::$namespaceReplacements['~SqlFtw\\\\Sql\\\\(.*)~'] = '..\1';
Dumper::$namespaceReplacements['~SqlFtw\\\\Platform\\\\(.*)~'] = '..\1';

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
    Dumper::$showInfo = false;
    $value = Dumper::dumpValue($token->value, $depth + 1);
    if (($token->type & (TokenType::COMMENT | TokenType::WHITESPACE)) !== 0) {
        $value = Ansi::dgray(Ansi::removeColors($value));
    }
    Dumper::$showInfo = $oldInfo;

    $type = implode('|', TokenType::getByValue($token->type)->getConstantNames());
    $orig = $token->original !== null && $token->original !== $token->value ? ' / ' . Dumper::value($token->original) : '';

    return Dumper::name(get_class($token)) . Dumper::bracket('(') . $value . $orig . ' / '
        . Dumper::value2($type) . ' ' . Dumper::info('at row') . ' ' . $token->row
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

    return Dumper::name(get_class($tokenList)) . Dumper::bracket('(')
        . Dumper::value($contents . $dots) . ' | ' . Dumper::value2($count . ' tokens, position ' . $tokenList->getPosition())
        . Dumper::bracket(')') . Dumper::objectInfo($tokenList);
};

// Platform
Dumper::$objectFormatters[Platform::class] = static function (Platform $platform): string {
    return Dumper::name(get_class($platform)) . Dumper::bracket('(')
        . Dumper::value($platform->getName()) . ' ' . Dumper::value2($platform->getVersion()->format())
        . Dumper::bracket(')');
};

// SimpleName
Dumper::$objectFormatters[SimpleName::class] = static function (SimpleName $simpleName): string {
    return Dumper::name(get_class($simpleName)) . Dumper::bracket('(')
        . Dumper::value($simpleName->getName())
        . Dumper::bracket(')');
};

// Token
Dumper::$shortObjectFormatters[FunctionCall::class] = static function (FunctionCall $functionCall): string {
    return Dumper::name(get_class($functionCall)) . Dumper::bracket('(') . ' '
        . Dumper::value($functionCall->getFunction()->getFullName()) . ' ' . Dumper::exceptions('...') . ' '
        . Dumper::bracket(')') . Dumper::objectInfo($functionCall);
};
