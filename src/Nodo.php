<?php  
namespace goetas\xml\xsd;
use goetas\xml\XMLDomElement;
class Nodo{
	/**
	 * @var Schema
	 */
	protected $xsd;
	/**
	 * @var Type
	 */
	protected $type;
	/**
	 * @var ComplexType
	 */
	protected $complexType;
	public function __construct(Schema $xsd, Type $type, $name) {
		$this->xsd = $xsd;
		$this->type = $type;
		$this->name = $name;
	}
	
	public function getNs() {
		return $this->xsd->getNs();
	}
	public function getName() {
		return $this->name;
	}
	/**
	 * @return Type
	 */
	public function getType() {
		return $this->type;
	}
	/**
	 * @return ComplexType
	 */
	public function getComplexType() {
		if($this->complexType===null){
			$this->complexType = $this->xsd->getContainer()->getType($this->type->getSchema()->getNs(), $this->type->getName());
		}
		return $this->complexType;
	}
	public function __toString() {
		return "{".$this->getNs()."}".$this->getName();
	}
} 