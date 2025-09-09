<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Documentation;

class StandardDocumentationReader implements DocumentationReader
{
    public function get(\DOMElement $node): string
    {
        $doc = '';

        /**
         * @var \DOMNode $childNode
         */
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement && 'annotation' === $childNode->localName) {
                /**
                 * @var \DOMNode $subChildNode
                 */
                foreach ($childNode->childNodes as $subChildNode) {
                    if ($subChildNode instanceof \DOMElement && 'documentation' === $subChildNode->localName) {
                        if (!empty($doc)) {
                            $doc .= "\n" . $subChildNode->nodeValue;
                        } else {
                            $doc .= $subChildNode->nodeValue;
                        }
                    }
                }
            }
        }
        $doc = preg_replace('/[\t ]+/', ' ', $doc);

        return trim($doc);
    }
}
