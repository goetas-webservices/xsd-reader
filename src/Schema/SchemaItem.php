<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

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