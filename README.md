[![Build status](https://github.com/goetas-webservices/xsd-reader/workflows/Build%20and%20test/badge.svg)](https://github.com/goetas-webservices/xsd-reader/actions?query=workflow%3A"Build+and+test")
![Code Coverage Badge](./coverage_badge.svg)


# PHP XSD Reader

Read any [XML Schema](http://www.w3.org/XML/Schema) (XSD) programmatically with PHP.

## Installation

The recommended way to install the `xsd-reader` via [Composer](https://getcomposer.org/):

```bash
composer require 'goetas-webservices/xsd-reader'
```

## Getting started

```php
use GoetasWebservices\XML\XSDReader\SchemaReader;

$reader = new SchemaReader();
$schema = $reader->readFile("http://www.example.com/example.xsd");

// $schema is instance of GoetasWebservices\XML\XSDReader\Schema\Schema;

// Now you can navigate the entire schema structure

foreach ($schema->getSchemas() as $innerSchema) {

}
foreach ($schema->getTypes() as $type) {

}
foreach ($schema->getElements() as $element) {

}
foreach ($schema->getGroups() as $group) {

}
foreach ($schema->getAttributes() as $attr) {

}
foreach ($schema->getAttributeGroups() as $attrGroup) {

}
```

## Note

The code in this project is provided under the
[MIT](https://opensource.org/licenses/MIT) license.
For professional support
contact [goetas@gmail.com](mailto:goetas@gmail.com)
or visit [https://www.goetas.com](https://www.goetas.com)
