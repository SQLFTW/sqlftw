
Token type hierarchy:
---------------------

Token type and additional token info are packed into 31 bit int

### TokenType constants (unique):

- **1K WHITESPACE**
- COMMENT
    - **2K LINE_COMMENT**
        - DOUBLE_HYPHEN_COMMENT - `-- ...`
        - DOUBLE_SLASH_COMMENT - `// ...`
        - HASH_COMMENT - `# ...`
    - **4K BLOCK_COMMENT** - `/* ... * /`
        - OPTIONAL_COMMENT - `/*! ... * /`
        - HINT_COMMENT - `/*+ ... * /`
- NAME
    - **32K UNQUOTED_NAME** - `table1` etc.
        - **16K KEYWORD** - `datetime` etc.
            - **8K RESERVED** - `SELECT` etc.
                - **512 OPERATOR** - `AND`, `OR` etc.
    - **64K QUOTED_NAME**
        - DOUBLE_QUOTED_NAME - `"table1"` (standard, MySQL in ANSI_STRINGS mode)
        - BACKTICK_QUOTED_NAME - `` `table1` `` (MySQL, PostgreSQL, Sqlite)
        - SQUARE_BRACKETED_NAME - `[table1]` (MSSQL, SqLite)
    - **128K AT_VARIABLE** - `@var`, `@@global`, `@'192.168.0.1'` (also includes host names)
        - SINGLE_QUOTED_AT_VAR - `@'var'`
        - DOUBLE_QUOTED_AT_VAR - `@"var"`
        - BACKTICK_QUOTED_AT_VAR - `` @`var` ``
- VALUE
    - **1M NUMBER**
        - **512K INT**
            - **256K UINT**
    - **2M STRING**
        - SINGLE_QUOTED_STRING - `'string'` (standard)
        - DOUBLE_QUOTED_STRING - `"string"` (MySQL in default mode)
        - DOLLAR_QUOTED_STRING - `$$table1$$` (PostgreSQL)
    - **4M BIT_STRING**
        - BINARY_LITERAL
        - OCTAL_LITERAL (PostgreSQL)
        - HEXADECIMAL_LITERAL
    - **8M UUID** - e.g. `3E11FA47-71CA-11E1-9E33-C80AA9429562`
- **16M SYMBOL** - `(`, `)`, `[`, `]`, `{`, `}`, `.`, `,`, `;`
    - **512 OPERATOR** - `+`, `||` etc.
    - OPTIMIZER_HINT_START - `/*+`
    - OPTIMIZER_HINT_END - `*/`
    - N/A **CHARSET_INTRODUCER** - `N`
    - N/A DOLLAR_QUOTE - `$foo$` (PostgreSQL)
- **32M PLACEHOLDER** - placeholder for a parameter
    - QUESTION_MARK_PLACEHOLDER - `?` (SQL, Doctrine, Laravel)
    - NUMBERED_QUESTION_MARK_PLACEHOLDER - `?123` (Doctrine)
    - DOUBLE_COLON_PLACEHOLDER - `:foo` (Doctrine, Laravel)
- **64M DELIMITER** - default `;`
- **128M DELIMITER_DEFINITION**
- **256M END**
- **1G INVALID**

values 512 and 512M are free fow now

### Token info (non unique):

Comment type:
-  **1** DOUBLE_HYPHEN_COMMENT
-  **2** DOUBLE_SLASH_COMMENT
-  **4** HASH_COMMENT
-  **8** OPTIONAL_COMMENT
- **16** OPTIMIZER_HINT_COMMENT

Quoting:
-  **1** SINGLE_QUOTED `'`
-  **2** DOUBLE_QUOTED `"`
-  **4** BACKTICK_QUOTED `` ` ``
-  **8** SQUARE_BRACKETED `[]`
- **16** DOUBLE_DOLLAR_QUOTED `$$`
- **32** DOLLAR_TAG_QUOTED `$foo$string value$foo$`
- **64** (reserved)

Base:
-  **1** (reserved for single quoted literals)
-  **2** (reserved for double quoted literals)
-  **4** binary
-  **8** octal (PostgreSQL)
- **16** hexadecimal
- **32** (base 32?)
- **64** (base 64?)
