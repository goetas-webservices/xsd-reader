<?php
namespace goetas\xml\xsd;
use goetas\xml\xsd\utils\UrlUtils;

use DOMElement;
use goetas\xml\XPath;
class Schema
{
    const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    protected $elementQualification='unqualified';
    protected $attributeQualification='unqualified';

    protected $elements;
    protected $types;
    protected $attributes;

    protected $ns;
    protected $schemaNode;
    /**
     * @var SchemaContainer
     */
    protected $container;
    /**
     * schemi interni
     * @var array
     */
    protected $schemas = array();
    /**
     * @return the $elementQualification
     */
    public function getElementQualification()
    {
        return $this->elementQualification;
    }

    /**
     * @return the $attributeQualification
     */
    public function getAttributeQualification()
    {
        return $this->attributeQualification;
    }

    public function __construct(DOMElement $schema, SchemaContainer $container)
    {
        $this->container = $container;
        $this->schemaNode = $schema;
        $this->ns = $this->schemaNode->getAttribute ( "targetNamespace" );
        $this->elementQualification = strlen($this->ns) && $this->schemaNode->hasAttribute ( "elementFormDefault" )?$this->schemaNode->getAttribute ( "elementFormDefault" ):"unqualified";
        $this->attributeQualification = $this->schemaNode->hasAttribute ( "attributeFormDefault" )?$this->schemaNode->getAttribute ( "attributeFormDefault" ):"unqualified";
        $this->parseImports();
    }
    public function addSchema(Schema $schema)
    {
        $this->schemas [] = $schema;
    }
    public function parseImports()
    {
        $xp = new XPath ( $this->schemaNode->ownerDocument);
        $xp->registerNamespace ( "xsd", self::XSD_NS );
        $imports = $xp->query("xsd:import[@schemaLocation]|xsd:include[@schemaLocation]", $this->schemaNode);

        foreach ($imports as $import) {
            $relPath = UrlUtils::resolve_url($this->schemaNode->ownerDocument->documentURI,$import->getAttribute("schemaLocation"));

            if ($import->getAttribute("namespace") && strpos($import->getAttribute("namespace"), 'http://www.w3.org/XML/1998/namespace')!==false) {
                $ns = $import->getAttribute("namespace");
                $this->container->addFinderFile($import->getAttribute("namespace"), $relPath);
            } else {

                if (!$import->getAttribute("namespace") || !isset($this->container[$import->getAttribute("namespace")])) {
                    $schema = new \DOMDocument('1.0','UTF-8');
                    $schema->load($relPath);
                    //$this->schemas [] = new Schema($schema->documentElement, $this->container);
                    $this->container->addSchemaNode($schema->documentElement);
                }
            }
        }
    }
    public function createAnonymType(DOMElement $node, $name)
    {
        $xp = new XPath ( $node->ownerDocument );
        $xp->registerNamespace ( "xsd", self::XSD_NS );
        $nodi = $xp->query ( "ancestor::xsd:*[@name]", $node );

        /*
        $concat = array ($node->getAttribute ( "name" ) );
        foreach ($nodi as $nd) {
            $concat [] = $nd->getAttribute ( "name" );
        }
        $concat [] = "__AnonymType";

        $typeName = implode ( "_", $concat );

        $tnode->setAttribute ( "name", $typeName );
        */

        $tnode = $xp->query("xsd:complexType|xsd:simpleType", $node)->item(0);

        $this->types[] = $type = $this->buildTypeFromNode($tnode);

        $type->setName($name);
        $type->setAnonymous();

        return $type;
    }

    private function buildTypeFromNode(DOMElement $node)
    {
        $xp = new XPath ( $node->ownerDocument );
        $xp->registerNamespace ( "xsd", self::XSD_NS );

        switch ($node->localName) {
            case "complexType" :
                if ($xp->query ( "xsd:simpleContent", $node )->length) {
                    return new SimpleContent ( $this, $node );
                } else {
                    return new ComplexType ( $this, $node );
                }
                break;
            case "simpleType" :
                return  new SimpleType ( $this, $node );
                break;
        }

        return false;
    }
    protected function loadType($name)
    {
        $xp = new XPath ( $this->schemaNode->ownerDocument );
        $xp->registerNamespace ( "xsd", self::XSD_NS );

        $nodes = $xp->query ( "xsd:complexType[@name='$name']|xsd:simpleType[@name='$name']", $this->schemaNode );
        if ($nodes->length) {
            $node = $nodes->item ( 0 );

            return $this->buildTypeFromNode($node);
        }
        foreach ($this->schemas as $schema) {
            try {
                return $schema->getType($name);
            } catch (\Exception $e) {
            }
        }

        return false;
    }
    protected function loadElement($name)
    {
        $xp = new XPath ( $this->schemaNode->ownerDocument );
        $xp->registerNamespace ( "xsd", self::XSD_NS );

        $nodes = $xp->query ( "xsd:element[@name='$name']", $this->schemaNode );
        if ($nodes->length) {
            $node = $nodes->item ( 0 );

            if (! $node->hasAttribute ( "type" )) {

                $type = $this->createAnonymType ( $node , $node->getAttribute ( "name" ));

                return new Element ( $this, $type, $node->getAttribute ( "name" ), 1, 1, false );

            } else {
                $elName = $node->getAttribute ( "name" );
                $typeName = $node->getAttribute ( "type" );

                list ( $ns, $name, $prefix ) = self::findParts ( $node, $typeName );

                $type = $this->findType($ns, $name);

                return new Element ( $this, $type, $node->getAttribute ( "name" ), 1, 1, false );
            }

        }
        foreach ($this->schemas as $schema) {
            try {
                return $schema->getElement($name);
            } catch (\Exception $e) {
            }
        }

        return false;

    }
    /**
     *
     * @param  string                 $ns
     * @return \goetas\xml\xsd\Schema
     */
    public function getSchema($ns)
    {
        if ($ns == $this->ns) {
            return $this;
        } else {
            return $this->container->getSchema ( $ns );
        }
    }
    public static function findParts(DOMElement $node, $type)
    {
        $typePart = explode ( ":", $type );
        if (count ( $typePart ) == 1) {
            list ( $name ) = $typePart;
            $ns = $node->lookupNamespaceUri ( null );
        } else {
            list ( $prefix, $name ) = $typePart;
            $ns = $node->lookupNamespaceUri ( $prefix );
        }

        return array ($ns, $name, $prefix );
    }
    public function getNs()
    {
        return $this->ns;
    }
    /**
     *
     * @param string $name
     * @var Attribute
     */
    public function getAttribute($name)
    {
        return $this->attributes [$name];
    }
    /**
     *
     * @param string $name
     * @var Element
     */
    private function getElement($name)
    {
        if (! isset ( $this->elements [$name] )) {
            $this->elements [$name] = $this->loadElement( $name );
        }
        if (! $this->elements [$name]) {
            throw new \Exception ( "Non trovo l'elemento '$name' nel namespace '{$this->ns}'" );
        }

        return $this->elements [$name];
    }
    private function getType($name)
    {
        if (! isset ( $this->types [$name] )) {
            $this->types [$name] = $this->loadType ( $name );
        }
        if (! $this->types [$name]) {
            throw new \Exception ( "Non trovo il tipo '$name' namespace '{$this->ns}'" );
        }

        return $this->types [$name];
    }
    /**
     *
     * @param string $name
     * @var Type
     */
    public function findType($ns, $name)
    {
        return $this->getSchema($ns)->getType ( $name );
    }
    /**
     *
     * @param string $name
     * @var Element
     */
    public function findElement($ns, $name)
    {
        return $this->getSchema($ns)->getElement ( $name );
    }
}
