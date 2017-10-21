<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItemTrait;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\XML\XSDReader\SchemaReaderLoadAbstraction;

class Group implements ElementItem, ElementContainer
{
    use AttributeItemTrait;
    use ElementContainerTrait;

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
    * @param string $name
    */
    public function __construct(Schema $schema, $name)
    {
        $this->schema = $schema;
        $this->name = $name;
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
    * @return Group|GroupRef
    */
    protected static function loadGroupBeforeCheckingChildNodes(
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

        return $group;
    }

    /**
    * @return \Closure
    */
    public static function loadGroup(
        SchemaReaderLoadAbstraction $reader,
        Schema $schema,
        DOMElement $node
    ) {
        $group = static::loadGroupBeforeCheckingChildNodes(
            $schema,
            $node
        );
        static $methods = [
            'sequence' => 'loadSequence',
            'choice' => 'loadSequence',
            'all' => 'loadSequence',
        ];

        return function () use ($reader, $group, $node, $methods) {
            SchemaReaderLoadAbstraction::againstDOMNodeList(
                $node,
                function (
                    DOMElement $node,
                    DOMElement $childNode
                ) use (
                    $methods,
                    $reader,
                    $group
                ) {
                    /**
                    * @var string[] $methods
                    */
                    $methods = $methods;

                    $reader->maybeCallMethod(
                        $methods,
                        (string) $childNode->localName,
                        $childNode,
                        $group,
                        $childNode
                    );
                }
            );
        };
    }
}
