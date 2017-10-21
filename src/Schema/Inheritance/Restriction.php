<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\XML\XSDReader\SchemaReaderLoadAbstraction;

class Restriction extends Base
{
    /**
    * @var mixed[][]
    */
    protected $checks = array();

    /**
    * @param string $type
    * @param mixed[] $value
    *
    * @return $this
    */
    public function addCheck($type, $value)
    {
        $this->checks[$type][] = $value;
        return $this;
    }

    /**
    * @return mixed[][]
    */
    public function getChecks()
    {
        return $this->checks;
    }

    /**
    * @param string $type
    *
    * @return mixed[]
    */
    public function getChecksByType($type)
    {
        return isset($this->checks[$type])?$this->checks[$type]:array();
    }

    public static function loadRestriction(
        SchemaReaderLoadAbstraction $reader,
        Type $type,
        DOMElement $node
    ) {
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute("base")) {
            $reader->findAndSetSomeBase($type, $restriction, $node);
        } else {
            $addCallback = function (Type $restType) use ($restriction) {
                $restriction->setBase($restType);
            };

            Type::loadTypeWithCallbackOnChildNodes(
                $reader,
                $type->getSchema(),
                $node,
                $addCallback
            );
        }
        $limit = $node->childNodes->length;
        for ($i = 0; $i < $limit; $i += 1) {
            /**
            * @var DOMNode $childNode
            */
            $childNode = $node->childNodes->item($i);

            if ($childNode instanceof DOMElement) {
                static::maybeLoadRestrictionOnChildNode(
                    $restriction,
                    $childNode
                );
            }
        }
    }

    protected static function maybeLoadRestrictionOnChildNode(
        Restriction $restriction,
        DOMElement $childNode
    ) {
        if (
            in_array(
                $childNode->localName,
                [
                    'enumeration',
                    'pattern',
                    'length',
                    'minLength',
                    'maxLength',
                    'minInclusive',
                    'maxInclusive',
                    'minExclusive',
                    'maxExclusive',
                    'fractionDigits',
                    'totalDigits',
                    'whiteSpace'
                ],
                true
            )
        ) {
            static::definitelyLoadRestrictionOnChildNode(
                $restriction,
                $childNode
            );
        }
    }

    protected static function definitelyLoadRestrictionOnChildNode(
        Restriction $restriction,
        DOMElement $childNode
    ) {
        $restriction->addCheck(
            $childNode->localName,
            [
                'value' => $childNode->getAttribute("value"),
                'doc' => SchemaReader::getDocumentation($childNode)
            ]
        );
    }
}
