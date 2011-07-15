<?php 
namespace goetas\xml\xsd;

class Type {
	const NS = 'http://www.w3.org/2001/XMLSchema';
	/**
	 * @var Schema
	 */
	protected $xsd;
	
	protected $name;
	
	public function __construct(Schema $xsd, $name) {
		$this->xsd = $xsd;
		$this->name = $name;
	}
	public function getSchema() {
		return $this->xsd;
	}
	public function getName() {
		return $this->name;
	}
	public function getNs() {
		return $this->getSchema()->getNs();
	}
	public function __toString() {
		return "{".$this->getNs()."}". $this->getName();
	}
} 