[![Build Status](https://travis-ci.org/goetas-webservices/xsd-reader.svg?branch=master)](https://travis-ci.org/goetas-webservices/xsd-reader)
[![Code Coverage](https://scrutinizer-ci.com/g/goetas-webservices/xsd-reader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/goetas-webservices/xsd-reader/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/goetas-webservices/xsd-reader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/goetas-webservices/xsd-reader/?branch=master)


PHP XSD Reader
==============

Read any [XML Schema](http://www.w3.org/XML/Schema) (XSD) programmatically with PHP.


Installation
------------

There are two recommended ways to install the `xsd-reader` via [Composer](https://getcomposer.org/):

* using the ``composer require`` command:

```bash
composer require 'goetas-webservices/xsd-reader'
```

* adding the dependency to your ``composer.json`` file:

```js
"require": {
    ..
    "goetas-webservices/xsd-reader" : "0.*",
    ..
}
```
Getting started
---------------

```php
use GoetasWebservices\XML\XSDReader\SchemaReader;

$reader = new SchemaReader();
$schema = $reader->readFile("http://www.example.com/exaple.xsd");

// $schema is instance of GoetasWebservices\XML\XSDReader\Schema\Schema;

// Now you can navigate the entire schema structure

foreach ($schema->getSchemas() as $innerSchema){

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

Note
----

I'm sorry for the *terrible* english fluency used inside the documentation, I'm trying to improve it.
Pull Requests are welcome.
