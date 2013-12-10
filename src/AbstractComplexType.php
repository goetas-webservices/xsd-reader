<?php
namespace goetas\xml\xsd;
use DOMElement;

abstract class AbstractComplexType extends BaseType
{
    public function getAttributes()
    {
        return $this->attributes;
    }
    protected $attributes = array ();
    protected function parseElement(DOMElement $node)
    {
        switch ($node->localName) {
            case "attributeGroup_" :
                list ( $ns, $name, $prefix ) = Schema::findParts ( $node, $node->getAttribute ( "ref" ) );
                $g = $this->findAttributeGroup ( $ns, $name );
                foreach ($g as $att) {
                    $this->attributes [] = $att;
                }
                break;

            case "attribute" :
                if ($node->hasAttribute ( "ref" )) {
                    $this->attributes [] = $this->xsd->findAttribute ( $node, $node->getAttribute ( "ref" ) );
                } else {

                    $qualification = $node->hasAttribute ( "form" )?$node->getAttribute ( "form" ):$this->getSchema()->getAttributeQualification();

                    $required = $node->hasAttribute ( "use" ) ? $node->getAttribute ( "use" ) != 'optional' : false;

                    if ($node->hasAttribute ( "type" ) ) {

                        list ( $ns, $name, $prefix ) = Schema::findParts ( $node, $node->getAttribute ( "type" ) );

                        $type = $this->xsd->findType($ns, $name);

                    } else {
                        $type = $this->xsd->createAnonymType($node, $node->getAttribute ( "name" ));
                    }

                    $this->attributes [$node->getAttribute ( "name" )] = new Attribute ( $this->xsd, $type, $node->getAttribute ( "name" ), $required, $node->getAttribute ( "default" ), $qualification );

                }
                break;
        }
    }
}
