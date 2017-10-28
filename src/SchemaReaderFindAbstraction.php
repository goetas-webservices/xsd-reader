<?php

namespace GoetasWebservices\XML\XSDReader;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

abstract class SchemaReaderFindAbstraction extends SchemaReaderCallbackAbstraction
{
    /**
     * @param string $attributeName
     *
     * @return SchemaItem
     */
    protected function findSomeType(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        return $this->findSomeTypeFromAttribute(
            $fromThis,
            $node,
            $node->getAttribute($attributeName)
        );
    }

    /**
     * @param string $attributeName
     *
     * @return SchemaItem
     */
    protected function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        /**
         * @var SchemaItem
         */
        $out = $this->findSomething(
            'findType',
            $fromThis->getSchema(),
            $node,
            $attributeName
        );

        return $out;
    }
}
