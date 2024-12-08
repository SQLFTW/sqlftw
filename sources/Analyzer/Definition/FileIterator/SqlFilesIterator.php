<?php

namespace SqlFtw\Analyzer\Context\FileIterator;

use Generator;

class SqlFilesIterator extends FileIterator
{

    public function getIterator(): Generator
    {
        while ($result = $this->getNextFileContents()) {
            [$version, $contents] = $result;

            yield $version => [$contents];
        }
    }

}
