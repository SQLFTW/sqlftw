# SQLFTW

MySQL (for now) lexer, parser, language model and static analyzer written in PHP

it is a validating parser which produces an object implementing SqlFtw\Sql\Command interface
for each of approximately 140 supported SQL commands (~490 nodes total). Commands do model the syntactic aspect 
of SQL code, not domain aspect (models exactly how queries are written), however does not track white space 
and currently ignores some comments

this parser is intended as a basis for two other projects:
- one is doing static analysis of SQL code, especially safety and performance of migrations (currently using very basic SQL parser from phpMyAdmin project)
- another will hopefully help PHPStan (static analysis tool for PHP) better understand SQL queries and their results

on its own it can be used to validate syntax of SQL code (e.g. migrations), or modify/generate/normalize SQL code


SQL syntax support:
-------------------

supports all SQL statements from MySQL 5.x to last MySQL 8.0.x and almost all language features

it is faster to just state what is not supported - here is a *complete* list:

not supported features, that will fail to parse:
- support for ascii-incompatible multibyte encodings like `shift-jis`, `gb18030` or `utf-16` (fails to parse)
- implicit string concatenation of double-quoted names in ANSI mode (`"foo" "bar"`; this is supported on strings, but not on names)
- quoted delimiters (MySQL client feature; not implemented, probably will fail)

accepted, but ignored features (no model and serialization):
- resolving operator precedence in expressions (for now operators of the same tier are just parsed from left to right; will be implemented later)
- regular comments inside statements (comments before statement are collected)
- HeatWave plugin features (SECONDARY_ENGINE), and MySQL cluster features
- `SELECT ... PROCEDURE ANALYSE (...)` - removed in MySQL 8.0
- `WEIGHT_STRING(... LEVEL ...)` - removed in MySQL 8.0

features implemented other way than MySQL:
- parser produces an error on unterminated comments same as PostgreSQL does (MySQL is silent and according to tests, this might be a bug)
- parser produces an error when reading user variables with invalid name (MySQL silently ignores them and returns null)
- parser produces an error on optimizer hint with invalid syntax (MySQL produces a warning, AWS Aurora MySQL produces an error)


Performance:
------------

SQLFTW parser is pretty fast. on my current pretty modern hardware MySQL test suite (~8.000 tests, ~500.000 statements) 
is parsed and validated in about 2.5 minutes in single thread mode (parse time ~60 μs per query). 
this is quite close to performance of e.g. `nikic/php-parser`

further optimizations are possible


Basic usage:
------------

```php
// simplest way to initialize Parser/Analyzer is to use ParserSuiteFactory, which will return a container with:
// - Analyzer, Parser, Lexer, Formatter, Normalizer and Session
$suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, '8.0'): // defaults to x.x.99 when no patch number is given
// or
$suite = ParserSuiteFactory::fromConfig(new ParserConfig(...));
// you can also initialize the whole machinery manually. it's just bunch of simple constructors. no magic involved!

// see ClientSideExtension for configuring parsing DBAL/client extensions to SQL

// returns a Generator<Command>. will not parse anything if you don't iterate over it
$commands = $suite->parser->parse('SELECT foo FROM ...');
foreach ($commands as $command) {
    // Parser does not throw exceptions. this allows to parse partially invalid code and not fail on first error
    $errors = $command->getErrors();
    if ($errors !== []) {
        ...
    }
    ...
}

// for more complicated cases (parsing stream of commands, some of which can change syntax via sql_mode or other settings)
// return a Generator<AnalyzerResult> and resolves updates to Session
$results = $suite->analyzer->analyze('SELECT foo FROM ...');
foreach ($results as $result) {
    // get Command and Errors from AnalyzerResult
    $command = $result->command;
    $errors = $result->errors;
    if ($errors !== []) {
        ...
    }
    ...
}

// for simple use-cases also these helper method exists:
$parser->parseSingle(...); // throws if given source contains more than one statement
$parser->parseAll(...); // returns array instead of Generator

$analyzer->analyzeSingle(...); // dtto
$analyzer->analyzeAll(...); // dtto

// if you want to reuse Parser/Analyzer for multiple non-related batches of statement, call after each batch:
$suite->session->reset();
```

Architecture:
-------------

main layers:
- Lexer - tokenizes SQL, returns a Generator of parser Tokens
- Parser(s) - validates syntax and returns a Generator of parsed Command objects
- Analyzer - static analysis rules and instrumentation for them
- Command(s) - SQL commands parsed from plaintext to immutable object representation. can be serialized back to plaintext
- Platform - lists of features supported by particular platform
- Formatter - configurable SQL statements serializer

also see `/doc`


Current state of development:
-----------------------------

where we are now:
- ☑ >99.9% MySQL language features implemented
- ☑ basic unit tests with serialisation
- ☑ parses almost everything from MySQL test suite (minimal false negatives)
- ☑ fails on almost all *parse* error tests from MySQL test suite (minimal false positives)
- ☑ serialisation testing on MySQL test suite (all SQL features represented as expected)
- ☐ parallelized and automated tests against multiple versions of MySQL test suite
- ☐ mutation testing (handling mutated SQL same as a real DB; extending range beyond MySQL test suite)
- ☐ distinguishing platform versions (parsing for exact patch version of the DB server)
- ☐ better support for parsing invalid code (partially parsed statement, better error handling)
- ☐ porting my migration static analysis tools to this library
- ☐ release of first stable version?
- ☐ other platforms (MariaDB, SQLite, PostgreSQL...)


Versioning:
-----------

we all love semantic versioning, don't we? : ]

but, this library is a huge undertaking and is still going through a rapid initial development stage. 
i have decided to tag and release versions even in this stage, because i think it is better than nothing and better than
releasing dozens of alpha versions. so **do not expect any backwards compatibility until we leave "0.1"**. 
designing a huge system on a first try is impossible, lots of concepts must settle and click into its place 
and therefore lots of changes are still coming

when using Composer, always lock your dependency on this package to an exact version. e.g. `sqlftw/sqlftw:0.1.14`


License:
--------

explicit license terms have not been determined yet.
this project will be most likely free to use on and by opensource projects and non-commercial entities, but not free for commercial entities


Author:
-------

Vlasta Neubauer, @paranoiq, https://github.com/paranoiq
