<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

interface SchemaItem
{
    /**
     * @return Schema
     */
    public function getSchema();

    /**
     * @return string|null
     */
    public function getDoc();
}
