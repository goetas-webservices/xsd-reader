<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

interface SchemaItem
{
    public function getSchema(): Schema;

    /**
     * @return string|null
     */
    public function getDoc();
}
