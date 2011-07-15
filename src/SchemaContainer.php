<?php  

namespace goetas\xml\xsd;
use DOMElement;
use goetas\xml\xsd\ComplexType;
use goetas\xml\xsd\Element; 
use OutOfRangeException;
class SchemaContainer extends \ArrayObject{
	protected $finders = array();
	/**
	 * @return Schema
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
	public function addFinder($callback) {
		$this->finders[]=$callback;
	}
	/**
	 * @param string $ns
	 * @param string $name
	 * @return ComplexType
	 */
	public function getType($ns, $name) {
		$typeDef = $this->getSchema($ns)->getType($name);
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
		$elementDef = $this->getSchema($ns)->getElement($name);
		if(!$elementDef){
			throw new OutOfRangeException("Non trovo una definizione per il tipo {{$ns}}$name");
		}
		return $elementDef;
	}
} 