<?php

namespace GoetasWebservices\XML\XSDReader\Documentation;

use DOMElement;

interface DocumentationReader
{
    public function get(DOMElement $node);
}
