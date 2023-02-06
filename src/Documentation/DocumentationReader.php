<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Documentation;

interface DocumentationReader
{
    public function get(\DOMElement $node): string;
}
