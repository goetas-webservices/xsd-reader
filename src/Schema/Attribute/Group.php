<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use DOMElement;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\XML\XSDReader\Schema\Schema;

class Group implements AttributeItem, AttributeContainer
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
    * @var AttributeItem[]
    */
    protected $attributes = array();

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

    public function addAttribute(AttributeItem $attribute)
    {
        $this->attributes[] = $attribute;
    }

    /**
    * @return AttributeItem[]
    */
    public function getAttributes()
    {
        return $this->attributes;
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
        SchemaReader $useThis,
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
        SchemaReader $schemaReader,
        Schema $schema,
        DOMElement $node
    ) {
        $attGroup = new self($schema, $node->getAttribute("name"));
        $attGroup->setDoc(SchemaReader::getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schemaReader, $schema, $node, $attGroup) {
            foreach ($node->childNodes as $childNode) {
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
