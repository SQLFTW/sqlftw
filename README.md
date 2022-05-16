# sqlftw
(My)SQL lexer, parser and language model written in PHP

it is a validating parser which produces an object implementing SqlFtw\Sql\Command interface
for each of approximately 140 supported SQL queries. Commands do model the syntactic aspect of SQL code,
not domain aspect (models exactly how queries are written), however does not track white space and currently 
ignores all comments

this parser is intended as a basis for two other projects:
- one is doing static analysis of SQL code, especially safety and performance of migrations (currently using very basic SQL parser from phpMyAdmin project)
- another will hopefully help PHPStan (static analysis tool for PHP) better understand SQL queries and their results

on it's ow can be used to validate syntax of (My)SQL code (e.g. migrations)


SQL syntax support:
-------------------

currently supports almost all SQL features up to MySQL 8.0.15 and some after that

notable not yet supported features:
- some of new MySQL features after 8.0.15
- JSON_TABLE() function parameters
- regular comments (conditional comments are parsed)
- optimizer hint comments
- curly bracket literals `{x expr}`
- resolving operator precedence in expressions

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

$platform = new Platform(Platform::MYSQL, '8.0');
$settings = new PlatformSettings($platform);
$parser = new Parser($settings);
try {
    $commands = $parser->parse('SELECT foo FROM ...');
    foreach ($commands as $command) {
        // ...
    }
} catch (ParserException $e) {
    // ...
}
```


Current state of development:
-----------------------------

where we are now:
- [x] ~98% MySQL language features implemented
- [x] tested against several thousands of tables and migrations
- [ ] tested against all test cases from MySQL test suite
- [ ] porting my static analysis tool on the new parser
- [ ] 100% MySQL language features implemented
- [ ] release of first stable version?
- [ ] better define how to work with different platforms and versions (Maria, Percona, non-MySQL...)
- [ ] other platforms?


Author:
-------

Vlasta Neubauer, @paranoiq, https://github.com/paranoiq
