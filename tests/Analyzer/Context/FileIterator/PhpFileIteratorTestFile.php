<?php

return [
    'a\'b"c',
    "a'b\"c",
    <<<HD
'a\'b"c'
"a'b\"c"
HD,
    <<<'ND'
'a\'b"c'
"a'b\"c"
ND
];
