<?php
namespace Goetas\XML\XSDReader\Schema;

interface SchemaItem
{
    /**
     * @return Schema
     */
    public function getSchema();

    /**
     * @return string
     */
    public function getDoc();
}