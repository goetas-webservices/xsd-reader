<?php
namespace Goetas\XML\XSDReader\Schema\Element;

use Goetas\XML\XSDReader\Schema\Type\Type;

class Group implements Element, ElementHolder
{

    protected $doc;

    protected $name;

    protected $elements;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(Element $element)
    {
        $this->elements[] = $element;
    }

    private static function nav(Element $item)
    {
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

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }

}
