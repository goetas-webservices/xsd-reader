<?php
namespace Goetas\XML\XSDReader;

use DOMDocument;
use Goetas\XML\XSDReader\Schema\Schema;

class SchemaContainer
{

    protected $reader;

    public function __construct()
    {
        $this->reader = new SchemaReader();


    }

    public function addFile($file)
    {
        $schema = $this->reader->readSchema($file);
        //print_r($schema);
        die();
    }
}
