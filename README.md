# sqlftw
Complete MySQL SQL dialect lexer, parser and representation written in PHP

currently supports almost all SQL features up to MySQL 8.0.15

not yet supported features:
- UNION
- functions with named arguments
- comments
- optimizer hints
- curly bracket literals `{x expr}`
- resolving operator precedence

main layers:
- Lexer - *(ns: Parser\Lexer)* tokenizes SQL, returns array of parser Tokens
- Parser(s) - validates syntax and returns a parsed Command
- Command(s) - *(ns: SQL)* SQL command parsed from plaintext to object representation. can be serialized back to plaintext
- Reflection - Database structure representation independent of actual SQL syntax **(work in progress)**

platforms:
- Platform - features supported by particular SQL implementation
- Formatter - helper for configuring SQL serialisation
