<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

trait SchemaItemTrait
{
    /**
    * @var Schema
    */
    protected $schema;

    /**
    * @var string
    */
    protected $doc = '';

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
    * @param string $doc
    *
    * @return $this
    */
    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }
}
