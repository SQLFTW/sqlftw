
Expression nodes:

note: classes in **bold**, interfaces in *italic*

- *ExpressionNode*
  - *RootNode* (*ArgumentNode*) - can be used on highest level anywhere (select, assignment, return etc.)
    - **CaseExpression** - `CASE x THEN y ELSE z END`
    - **CollateExpression** - `expr COLLATE collation`
    - **CurlyExpression** - `{identifier expr}`
    - **ExistsExpression** - `EXISTS (SELECT ...)`
    - **FunctionCall** - e.g. `AVG([DISTINCT] x) OVER ...`
    - *Identifier* - e.g. `name, @name, *`
      - *ColumnIdentifier* - types that can identify a column
        - **ColumnName** - e.g. `schema1.table1.column1`
      - *FunctionIdentifier* - types that can identify a function
        - **BuiltInFunction** - name of built-in function 
      - *TableIdentifier* - types that can identify a table
        - **QualifiedName** (*ColumnIdentifier*, *FunctionIdentifier*) - e.g. `schema1.foo`
        - **SimpleName** (*ColumnIdentifier*, *FunctionIdentifier*) - e.g. `foo`
      - **SystemVariable** - e.g. `@@name`, `@@global.name`
      - **UserVariable** - e.g. `@name`
    - *Literal* - value, placeholder or value promise (e.g. `DEFAULT`)
      - *KeywordLiteral* - e.g. `DEFAULT`, `UNKNOWN`, `ON`, `OFF`...
        - **AllLiteral** - `ALL`
        - **DefaultLiteral** - `DEFAULT`
        - **UnknownLiteral** - `UNKNOWN`
      - **Placeholder** - ?
      - *ValueLiteral* - concrete value
        - **BinaryLiteral** - e.g. `0b001101110`
        - **HexadecimalLiteral** - e.g. `0x001F`
        - **IntervalLiteral** - e.g. `INTERVAL 6 DAYS`
        - **NumberLiteral** - e.g. `-1.23e-4`
          - **IntLiteral** - e.g. `-123`
            - **UintLiteral** - e.g. `123`
        - **StringLiteral** - e.g. `"start " \n 'middle ' \n "end"`
        - **NullLiteral** (*KeywordLiteral*) - `NULL`
        - **BooleanLiteral** (*KeywordLiteral*) - `TRUE` | `FALSE`
        - **OnOffLiteral** (*KeywordLiteral*) - `ON` | `OFF`
    - **MatchExpression** - `MATCH x AGAINST y`
    - *OperatorExpression*
      - **AssignOperator** - `variable := expr`
      - **BinaryOperator** - e.g. `x + y`
      - **TernaryOperator** - e.g. `x BETWEEN y AND z`
      - **UnaryOperator** - e.g. `NOT x`
    - **Parentheses** - `(...)`
  - *ArgumentNode* - can be used as argument of some functions
    - **Asterisk** - in `SELECT * FORM ...` and `COUNT(*)`
    - **CastType** - in `CAST(expr AS type)`
    - **Charset** - in `CONVERT(expr USING charset_name)`
    - **JsonErrorCondition** - in `JSON_TABLE(...)` and `JSON_VALUE(...)`
    - **ListExpression** - `..., ..., ...` - as argument in `GROUP_CONCAT(... ORDER BY ...)`
    - **OrderByExpression** - `{col_name | expr | position} [ASC | DESC]`
  - *JsonTableColumn* - in `JSON_TABLE(...)`
    - **JsonTableExistPathColumn**
    - **JsonTableOrdinalityColumn**
    - **JsonTablePathColumn**
    - **JsonTableNestedColumn**
  - **AliasExpression** - `expr AS alias` - used on highest level in queries
  - **RowExpression** - `ROW (...[, ...])`  - used as operator parameter
  - **Subquery** - `(SELECT ...)`  - used as operator parameter

maybes:
- **SystemVariable** as node ???
- **Collation** as node ???
- **UserName** as node ???
- **ThreeStateValue** as *Literal* ???
