# SQLFTW

(My)SQL lexer, parser and language model written in PHP

it is a validating parser which produces an object implementing SqlFtw\Sql\Command interface
for each of approximately 140 supported SQL commands. Commands do model the syntactic aspect of SQL code,
not domain aspect (models exactly how queries are written), however does not track white space and currently 
ignores all comments

this parser is intended as a basis for two other projects:
- one is doing static analysis of SQL code, especially safety and performance of migrations (currently using very basic SQL parser from phpMyAdmin project)
- another will hopefully help PHPStan (static analysis tool for PHP) better understand SQL queries and their results

on its own it can be used to validate syntax of (My)SQL code (e.g. migrations)


SQL syntax support:
-------------------

supports all SQL commands from MySQL 5.x to MySQL 8.0.29 and almost all language features

not supported features, that will fail to parse:
- support for ascii-incompatible multibyte encodings like `shift-jis` or `gb18030` (fails to parse)
- quoted delimiters (not implemented, probably will fail)
- implicit string concatenation of double-quoted names in ANSI mode (`"foo" "bar"`; this is supported on strings, but not on names)

parsed, but ignored features (no model and serialization):
- resolving operator precedence in expressions (operators of the same tier are just parsed from left to right)
- regular comments inside statements (comments before statement are collected and conditional comments are parsed)
- optimizer hint comments (ignored)
- HeatWave plugin features (SECONDARY_ENGINE)
- `SELECT ... PROCEDURE ANALYSE (...)` - removed in MySQL 8
- `WEIGHT_STRING(... LEVEL ...)` - removed in MySQL 8

features implemented other way than MySQL:
- Parser produces an error on unterminated comments same as PostgreSQL does (MySQL is silent and according to tests, this might be a bug)

Architecture:
-------------

main layers:
- Lexer - tokenizes SQL, returns a Generator of parser Tokens
- Parser(s) - validates syntax and returns a Generator of parsed Command objects
- Command(s) - SQL commands parsed from plaintext to immutable object representation. can be serialized back to plaintext
- Reflection - database structure representation independent of actual SQL syntax (work in progress)
- Platform - lists of features supported by particular platform
- Formatter - helper for configuring SQL serialisation


Basic usage:
------------

```
<?php

use ...

$platform = new Platform(Platform::MYSQL, '8.0'); // version defaults to x.x.99 when no patch number is given
$settings = new ParserSettings($platform);
$parser = new Parser($settings);

// returns a Generator. will not parse anything if you don't iterate over it
$commands = $parser->parse('SELECT foo FROM ...');
foreach ($commands as [$command, $tokenList, $start, $end]) {
    // Parser does not throw exceptions. this allows to parse partially invalid code and not fail on first error
    if ($command instanceof InvalidCommand) {
        $e = $command->getException();
        ...
    }
    ...
}
```


Current state of development:
-----------------------------

where we are now:
- ☑ ~99.9% MySQL language features implemented
- ☑ basic unit tests with serialisation
- ☑ tested against several thousands of tables and migrations
- ☑ parses everything from MySQL test suite (no false negatives)
- ☐ fails on all error tests from MySQL test suite (no false positives)
- ☐ serialisation testing on MySQL test suite (all features kept as expected)
- ☐ fuzzy testing (parser handles mutated SQL strings exactly like a real DB)
- ☐ porting my static analysis tools on the new parser (probably many API changes)
- ☐ distinguishing server version (parsing for exact version of the DB server)
- ☐ 100% MySQL language features implemented
- ☐ release of first stable version?
- ☐ other platforms? (MariaDB, SQLite, PostgreSQL, ...)


Author:
-------

Vlasta Neubauer, @paranoiq, https://github.com/paranoiq
