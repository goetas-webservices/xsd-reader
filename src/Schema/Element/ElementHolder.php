<?php
namespace Goetas\XML\XSDReader\Schema\Element;

interface ElementHolder
{
    public function addElement(Element $element);
    public function getElements();
}
