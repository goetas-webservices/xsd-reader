<?php

namespace GoetasWebservices\XML\XSDReader;

use DOMElement;

class SchemaReader extends SchemaReaderLoadAbstraction
{
    /**
     * @param string $typeName
     *
     * @return mixed[]
     */
    protected static function splitParts(DOMElement $node, $typeName)
    {
        $prefix = null;
        $name = $typeName;
        if (strpos($typeName, ':') !== false) {
            list($prefix, $name) = explode(':', $typeName);
        }

        $namespace = $node->lookupNamespaceUri($prefix ?: '');

        return array(
            $name,
            $namespace,
            $prefix,
        );
    }
}
