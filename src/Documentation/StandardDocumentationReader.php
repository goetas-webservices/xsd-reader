<?php

namespace GoetasWebservices\XML\XSDReader\Documentation;

use DOMElement;

class StandardDocumentationReader implements DocumentationReader
{
    public function get(DOMElement $node)
    {
        $doc = '';
        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName == 'annotation') {
                foreach ($childNode->childNodes as $subChildNode) {
                    if ($subChildNode->localName == 'documentation') {
                        $doc .= ($subChildNode->nodeValue);
                    }
                }
            }
        }
        $doc = preg_replace('/[\t ]+/', ' ', $doc);

        return trim($doc);
    }
}
