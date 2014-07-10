<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Element\ElementHolder;

class ComplexType extends BaseComplexType implements ElementHolder
{
    protected $elements = array();

    public function getElements()
    {
        return $this->elements;
    }
    public function set()
    {
        $this->elements = array();
        $this->attributes = array();
    }
    public function addElement(Element $element)
    {
        $this->elements[] = $element;
    }

    private static function nav(Element $item){
        $items = array();
        if ($item instanceof ElementHolder) {
            foreach ($item->getElements() as $attrExtra) {
                $items = array_merge($items, self::nav($attrExtra));
            }
        } else {
            $items[] = $item;
        }
        return $items;

    }
    public function getAllElements()
    {
        $items = array();
        foreach ($this->getElements() as $item) {
            $items = array_merge($items, self::nav($item));
        }
        return $items;
    }
}

