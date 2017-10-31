<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Element extends Item implements ElementItem, ElementSingle
{
    /**
     * @var int
     */
    protected $min = 1;

    /**
     * @var int
     */
    protected $max = 1;

    /**
     * @var bool
     */
    protected $qualified = false;

    /**
     * @var bool
     */
    protected $nil = false;

    /**
     * @return int
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param int $min
     *
     * @return $this
     */
    public function setMin($min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param int $max
     *
     * @return $this
     */
    public function setMax($max)
    {
        $this->max = $max;

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
        $this->qualified = is_bool($qualified) ? $qualified : (bool) $qualified;

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
        $this->nil = is_bool($nil) ? $nil : (bool) $nil;

        return $this;
    }

    /**
     * @return Element
     */
    public static function loadElement(
        SchemaReader $reader,
        Schema $schema,
        DOMElement $node
    ) {
        $element = new self($schema, $node->getAttribute('name'));
        $element->setDoc(SchemaReader::getDocumentation($node));

        $reader->fillItem($element, $node);

        SchemaReader::maybeSetMax($element, $node);
        SchemaReader::maybeSetMin($element, $node);

        $xp = new \DOMXPath($node->ownerDocument);
        $xp->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        if ($xp->query('ancestor::xs:choice', $node)->length) {
            $element->setMin(0);
        }

        if ($node->hasAttribute('nillable')) {
            $element->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $element->setQualified($node->getAttribute('form') == 'qualified');
        }

        return $element;
    }
}
