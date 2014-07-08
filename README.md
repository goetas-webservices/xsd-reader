PHP XSD Reader
==============

Read any [XML Schema](http://www.w3.org/XML/Schema) (XSD) programmatically with PHP.


```php
use Goetas\XML\XSDReader\SchemaReader;

$reader = new SchemaReader();
$schema = $reader->readFile("http://www.example.com/exaple.xsd");

// $schema is instance of Goetas\XML\XSDReader\Schema\Schema;

// Now you can navigate the entire schema structure

foreach ($schema->getSchema() as $innerSchema){

}
foreach ($schema->getTypes() as $type){

}
foreach ($schema->getElements() as $element){

}
foreach ($schema->getGroups() as $group){

}
foreach ($schema->getAttributes() as $attr){

}
foreach ($schema->getAttributeGroups() as $attrGroup){

}


```