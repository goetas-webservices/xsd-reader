<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Group implements ElementItem, ElementContainer
{

    /**
     *
     * @var Schema
     */
    protected $schema;

    /**
    * @var string|null
    */
    protected $doc;

    /**
    * @var string
    */
    protected $name;

    /**
    * @var ElementItem[]
    */
    protected $elements = array();

    /**
    * @param string $name
    */
    public function __construct(Schema $schema, $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    /**
    * @return string
    */
    public function getName()
    {
        return $this->name;
    }

    /**
    * @param string $name
    *
    * @return $this
    */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
    * @return ElementItem[]
    */
    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(ElementItem $element)
    {
        $this->elements[] = $element;
    }

    /**
    * @return string|null
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

    /**
    * @return Schema
    */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
    * @return \Closure
    */
    public static function loadGroup(
        SchemaReader $reader,
        Schema $schema,
        DOMElement $node
    ) {
        $group = new Group($schema, $node->getAttribute("name"));
        $group->setDoc(SchemaReader::getDocumentation($node));

        if ($node->hasAttribute("maxOccurs")) {
            /**
            * @var GroupRef $group
            */
            $group = SchemaReader::maybeSetMax(new GroupRef($group), $node);
        }
        if ($node->hasAttribute("minOccurs")) {
            /**
            * @var GroupRef $group
            */
            $group = SchemaReader::maybeSetMin(
                $group instanceof GroupRef ? $group : new GroupRef($group),
                $node
            );
        }

        $schema->addGroup($group);

        static $methods = [
            'sequence' => 'loadSequence',
            'choice' => 'loadSequence',
            'all' => 'loadSequence',
        ];

        return function () use ($reader, $group, $node, $methods) {
            foreach ($node->childNodes as $childNode) {
                $reader->maybeCallMethod(
                    $methods,
                    (string) $childNode->localName,
                    $childNode,
                    $group,
                    $childNode
                );
            }
        };
    }
}
