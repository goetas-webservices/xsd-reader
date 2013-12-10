<?php
namespace goetas\xml\xsd;
use DOMElement;

class SimpleContent extends AbstractComplexType
{
    protected $base;
    /**
     *
     * @return \goetas\xml\xsd\SimpleType
     */
    public function getBase()
    {
        return $this->base;
    }
    protected function parseElement(DOMElement $node)
    {
        parent::parseElement ( $node );
        switch ($node->localName) {
            case "simpleContent" :
                $this->recurse($node);
            break;
            case "restriction" :
            case "extension" :
                list ( $ns, $name, $prefix ) = Schema::findParts ( $node, $node->getAttribute ( "base" ) );

                $this->base = $this->xsd->findType($ns, $name);

                $this->recurse($node);

                break;
        }
    }
}
