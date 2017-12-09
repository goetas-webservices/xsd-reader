<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Documentation;

use DOMElement;

class StandardDocumentationReader implements DocumentationReader
{
    /**
     * @return string
     */
    public function get(DOMElement $node)
    {
        $doc = '';

        /**
         * @var \DOMNode $childNode
         */
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->localName == 'annotation') {
                /**
                 * @var \DOMNode $subChildNode
                 */
                foreach ($childNode->childNodes as $subChildNode) {
                    if ($subChildNode instanceof DOMElement && $subChildNode->localName == 'documentation') {
                        $doc .= ($subChildNode->nodeValue);
                    }
                }
            }
        }
        $doc = preg_replace('/[\t ]+/', ' ', $doc);

        return trim($doc);
    }
}
