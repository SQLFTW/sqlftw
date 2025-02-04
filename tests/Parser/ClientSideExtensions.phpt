<?php

namespace SqlFtw\Parser;

use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';

// ?
Assert::parseSerialize("UPDATE tbl1 SET col1 = ? WHERE col2 = ?", null, ClientSideExtension::FOR_DOCTRINE);

// ?123
Assert::parseSerialize("UPDATE tbl1 SET col1 = ?123 WHERE col2 = ?456", null, ClientSideExtension::FOR_DOCTRINE);

// :var
Assert::parseSerialize("UPDATE tbl1 SET col1 = :var1 WHERE col2 = :var2", null, ClientSideExtension::FOR_DOCTRINE);
