
Best practices regarding types representing parts of SQL code and their type-hints:

Arrays:
-------
- non-empty set is represented by a non-empty-array and is translated to e.g. `(...)`)
- empty set is represented by an empty array and is translates to e.g. `()`)
- when empty set is not allowed by SQL syntax, `null` value represents non-existence of the set
- so for non-empty set the typehint must be `non-empty-array<Foo>|null`
- for set, that can be empty, type-hint `array<Foo>` is sufficient
- syntax `array<Foo>` is preferred over `Foo[]` when appropriate
- `Foo[]|Bar[]` means "array of Foo or array of Bar", where `array<Foo|Bar>` means "array of mixed Foo and Bar"


- empty array can also indicate use of SQL keyword `NONE`
- in some cases SQL keyword `ALL` used as a quantifier instead of a set is represented by 
  either by `true` or `null`. these should be replaced by e.g. instance of `AllLiteral`
