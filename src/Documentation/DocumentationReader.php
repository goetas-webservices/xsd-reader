<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Documentation;

use DOMElement;

interface DocumentationReader
{
    /**
     * @return string
     */
    public function get(DOMElement $node);
}
