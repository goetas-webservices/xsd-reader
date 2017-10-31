<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Attribute extends Item implements AttributeSingle
{
    /**
     * @var static|null
     */
    protected $fixed;

    /**
     * @var static|null
     */
    protected $default;

    /**
     * @var bool
     */
    protected $qualified = true;

    /**
     * @var bool
     */
    protected $nil = false;

    /**
     * @var string
     */
    protected $use = self::USE_OPTIONAL;

    /**
     * @return static|null
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * @param static $fixed
     *
     * @return $this
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * @return static|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param static $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isQualified()
    {
        return $this->qualified;
    }

    /**
     * @param bool $qualified
     *
     * @return $this
     */
    public function setQualified($qualified)
    {
        $this->qualified = $qualified;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNil()
    {
        return $this->nil;
    }

    /**
     * @param bool $nil
     *
     * @return $this
     */
    public function setNil($nil)
    {
        $this->nil = $nil;

        return $this;
    }

    /**
     * @return string
     */
    public function getUse()
    {
        return $this->use;
    }

    /**
     * @param string $use
     *
     * @return $this
     */
    public function setUse($use)
    {
        $this->use = $use;

        return $this;
    }

    /**
     * @return Attribute
     */
    public static function loadAttribute(
        SchemaReader $schemaReader,
        Schema $schema,
        DOMElement $node
    ) {
        $attribute = new self($schema, $node->getAttribute('name'));
        $attribute->setDoc(SchemaReader::getDocumentation($node));
        $schemaReader->fillItem($attribute, $node);

        if ($node->hasAttribute('nillable')) {
            $attribute->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $attribute->setQualified($node->getAttribute('form') == 'qualified');
        }
        if ($node->hasAttribute('use')) {
            $attribute->setUse($node->getAttribute('use'));
        }

        return $attribute;
    }

    /**
     * @return AttributeItem
     */
    public static function getAttributeFromAttributeOrRef(
        SchemaReader $schemaReader,
        DOMElement $childNode,
        Schema $schema,
        DOMElement $node
    ) {
        if ($childNode->hasAttribute('ref')) {
            /**
             * @var AttributeItem
             */
            $attribute = $schemaReader->findSomething('findAttribute', $schema, $node, $childNode->getAttribute('ref'));
        } else {
            /**
             * @var Attribute
             */
            $attribute = self::loadAttribute($schemaReader, $schema, $childNode);
        }

        return $attribute;
    }
}
