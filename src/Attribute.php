<?php  
namespace goetas\xml\xsd;
class Attribute extends Nodo{
	protected $required = false;
	protected $default;
	public function __construct(Schema $xsd, Type $type, $name, $required = false, $default = null) {
		parent::__construct($xsd, $type, $name);
		$this->required = $required;
		$this->default = $default;
	}
	public function getDefault() {
		return $this->default;
	}
	public function isRequred() {
		return (bool)$this->required;
	}
} 