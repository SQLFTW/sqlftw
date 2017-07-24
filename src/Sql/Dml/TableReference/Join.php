<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

abstract class Join implements \SqlFtw\Sql\Dml\TableReference\TableReferenceNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\TableReference\TableReferenceNode */
    protected $left;

    /** @var \SqlFtw\Sql\Dml\TableReference\TableReferenceNode */
    protected $right;

    public function __construct(TableReferenceNode $left, TableReferenceNode $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function getLeft(): TableReferenceNode
    {
        return $this->left;
    }

    public function getRight(): TableReferenceNode
    {
        return $this->right;
    }

}
