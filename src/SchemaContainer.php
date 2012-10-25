<?php

namespace goetas\xml\xsd;

use DOMElement;
use goetas\xml\xsd\ComplexType;
use goetas\xml\xsd\Element;
use OutOfRangeException;
use DOMDocument;
class SchemaContainer extends \ArrayObject{
	public function __construct(array $nss = array()){
		$this->addFinderFile(Schema::XSD_NS, __DIR__."/res/XMLSchema.xsd");
		foreach ($nss as $ns => $path){
			$this->addFinderFile($ns, $path);
		}
	}
	protected $finders = array();
	/**
	 * @return \goetas\xml\xsd\Schema
	 */
	public function getSchema($ns) {
		if(!isset($this[$ns])){
			foreach ($this->finders as $f){
				$node  = call_user_func($f, $ns);
				if($node instanceof DOMElement){
					$this[$ns] = new Schema($node, $this);
					break;
				}
			}
			if(!isset($this[$ns])){
				throw new OutOfRangeException("Non trovo una definizione per lo schema {{$ns}}");
			}
		}
		return $this[$ns];
	}
	public function addSchemaNode(DOMElement $node) {
		$ns = $node->getAttribute("targetNamespace");
		$schema = new Schema($node, $this);
		if(isset($this[$ns])){
			$this[$ns]->addSchema($schema);
		}else{
			$this[$ns] = $schema;
		}		
	}
	public function addFinder($callback) {
		$this->finders[]=$callback;
	}
	public function addFinderFile($targetNs, $file) {
		
		$finder = function ($ns) use($targetNs, $file){
			if($ns==$targetNs){
				$dom = new DOMDocument("1.0", "UTF-8");
				$dom->load($file);

				if($targetNs==Schema::XSD_NS){
					$type = $dom->createElementNS(Schema::XSD_NS, "simpleType");
					$type->setAttribute("name", "anySimpleType");
					$dom->documentElement->appendChild($type);
				}
				return $dom->documentElement;
			}
		};
		
		if(isset($this[$targetNs])){
			$this->addSchemaNode($finder($targetNs));
		}else{
			$this->addFinder($finder);
		}
	}


	/**
	 * @param string $ns
	 * @param string $name
	 * @return ComplexType
	 */
	public function getType($ns, $name) {
		$typeDef = $this->getSchema($ns)->findType($ns, $name);
		if(!$typeDef){
			throw new OutOfRangeException("Non trovo una definizione per il tipo {{$ns}}$name");
		}
		return $typeDef;
	}
	/**
	 * @param string $ns
	 * @param string $name
	 * @return Element
	 */
	public function getElement($ns, $name) {
		$elementDef = $this->getSchema($ns)->findElement($ns, $name);
		if(!$elementDef){
			throw new OutOfRangeException("Non trovo una definizione per il tipo {{$ns}}#$name");
		}
		return $elementDef;
	}
}