<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use DOMElement;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\XML\XSDReader\SchemaReaderLoadAbstraction;
use GoetasWebservices\XML\XSDReader\Schema\Schema;

class Group implements AttributeItem, AttributeContainer
{
    use AttributeItemTrait;
    use AttributeContainerTrait;

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
    * @param string $attr
    */
    public static function findSomethingLikeThis(
        SchemaReaderLoadAbstraction $useThis,
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        AttributeContainer $addToThis
    ) {
        /**
        * @var AttributeItem $attribute
        */
        $attribute = $useThis->findSomething('findAttributeGroup', $schema, $node, $childNode->getAttribute("ref"));
        $addToThis->addAttribute($attribute);
    }

    /**
    * @return \Closure
    */
    public static function loadAttributeGroup(
        SchemaReaderLoadAbstraction $schemaReader,
        Schema $schema,
        DOMElement $node
    ) {
        $attGroup = new self($schema, $node->getAttribute("name"));
        $attGroup->setDoc(SchemaReader::getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schemaReader, $schema, $node, $attGroup) {
            $limit = $node->childNodes->length;
            for ($i = 0; $i < $limit; $i += 1) {
                $childNode = $node->childNodes->item($i);
                switch ($childNode->localName) {
                    case 'attribute':
                        $attribute = Attribute::getAttributeFromAttributeOrRef(
                            $schemaReader,
                            $childNode,
                            $schema,
                            $node
                        );
                        $attGroup->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        self::findSomethingLikeThis(
                            $schemaReader,
                            $schema,
                            $node,
                            $childNode,
                            $attGroup
                        );
                        break;
                }
            }
        };
    }
}
