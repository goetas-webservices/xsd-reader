<?php
namespace goetas\xml\xsd;
use DOMElement;

class SimpleType extends BaseType
{
    protected $base;
    /**
     *
     * @return \goetas\xml\xsd\SimpleType
     */
    public function getBase()
    {
        if ( !$this->base || ($this->base->getNs()==Schema::XSD_NS &&  $this->base->getName()=="anySimpleType")) {
            return null;
        }

        return $this->base;
    }
    protected function parseElement(DOMElement $node)
    {
        switch ($node->localName) {
            case "restriction" :
                list ( $ns, $name, $prefix ) = Schema::findParts ( $node, $node->getAttribute ( "base" ) );

                $this->base = $this->xsd->findType($ns, $name);

                //$this->recurse($node);
                break;
        }
    }
}
