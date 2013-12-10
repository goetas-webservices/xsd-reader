<?php
namespace goetas\xml\xsd;
use DOMElement;
use DOMXPath;
abstract class BaseType extends Type
{
    /**
     * @var \DOMElement;
     */
    protected $node;

    public function __construct(Schema $xsd, DOMElement $node)
    {
        parent::__construct($xsd, $node->getAttribute("name"));
        $this->node = $node;
        $this->recurse($node);
    }
    public function getNode()
    {
        return $this->node;
    }
    protected function recurse(DOMElement $node)
    {
        $xp = new DOMXPath($node->ownerDocument);
        $xp->registerNamespace("xsd" ,self::NS);
        foreach ($xp->query("xsd:*", $node) as $nd) {
            $this->parseElement($nd);
        }
    }
    abstract protected function parseElement(DOMElement $node);
}
