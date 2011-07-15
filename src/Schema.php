<?php 
namespace goetas\xml\xsd;
use DOMElement; 
use goetas\xml\XPath;
class Schema{
	const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
	
	protected $elements;
	protected $types;
	protected $attributes;
	
	protected $ns;
	/**
	 * @var SchemaContainer
	 */
	protected $container;

	protected static $anonymTypes = 0;
	public function createAnonymName(DOMElement $node) {
		$xp = new XPath($node->ownerDocument);
		$xp->registerNamespace("xsd", self::XSD_NS);
		$nodi = $xp->query("ancestor::xsd:*[@name]", $node);

		$concat = array($node->getAttribute("name")); 
		foreach ($nodi as $nd){
			$concat[] = $nd->getAttribute("name");
		}
		$concat[]="Type";
		return implode("_",$concat);
	}
	public function __construct(DOMElement $schema , SchemaContainer $container) {
		$this->container = $container;
		
		
		$xp = new XPath($schema->ownerDocument);
		$xp->registerNamespace("xsd", self::XSD_NS);
		
		
		$this->ns = $schema->getAttribute("targetNamespace");
		
		
		
		$nodes = $xp->query("xsd:*|//xsd:element[not(@xsd:type)]/xsd:complexType", $schema);
		foreach ($nodes as $node){
			switch ($node->localName) {
				case "complexType":
					
					$this->types[$node->getAttribute("name")] = new ComplexType($this , $node);
					
					if(0 && $this->ns == 'http://webservices.hotel.de/MyRES/V1_1'){
						echo "TY: " .$node->getAttribute("name")."\n<br/>\n";
					}
					
					
				break;			
				case "simpleType":
					$this->types[$node->getAttribute("name")] = new SimpleType($this , $node);	
				break;
				case "element":
					if(!$node->hasAttribute("type")){
						
						$typeName = $this->createAnonymName($node);
						
						$prefix = $node->lookupPrefix($this->ns);
						
						$node->setAttribute("type", $prefix.":".$typeName);

						$xp->query("xsd:complexType", $node)->item(0)->setAttribute("name", $typeName);
							
					}
					
					$elName = $node->getAttribute("name");
					$typeName = $node->getAttribute("type");
					
					if(0 && $this->ns == 'http://webservices.hotel.de/MyRES/V1_1'){
						echo "EL: " .$elName." $typeName\n<br/>\n";
					}				
					list($ns, $name, $prefix ) = self::findParts( $node,  $typeName);
						
					$type = new Type($ns==$this->ns?$this:$this->container->getSchema($ns),$name);
					
					$this->elements[$elName] = new Element($this, $type, $node->getAttribute("name") );

				break;
				case "group":
					//$this->elements[$node->getAttribute("name")] = new Element($node, $this );
				break;
				case "attributeGroup":
					//$this->elements[$node->getAttribute("name")] = new Element($node, $this );
				break;
			}
		}				
	}
	
	public static function findParts(DOMElement $node, $type) {
		$typePart = explode(":",$type);
		if(count($typePart)==1){
			list($name) = $typePart;
			$doc = $node->ownerDocument;
			$xp = new XPath($doc);
			$xp->registerNamespace("xsd", self::XSD_NS);
			$ns = $xp->evaluate("string(ancestor::xsd:schema[@targetNamespace]/@targetNamespace)", $node);
		}else{
			list($prefix, $name) = $typePart;
			$ns = $node->lookupNamespaceUri($prefix);
		}
		return array($ns, $name, $prefix); 
	}
	/**
	 * @return SchemaContainer
	 */
	public function getContainer() {
		return $this->container;
	}
	public function getNs() {
		return $this->ns;
	}
	/**
	 * 
	 * @param string $name
	 * @var Attribute
	 */
	public function getAttribute($name) {
		return $this->attributes[$name];
	}
	/**
	 * 
	 * @param string $name
	 * @var Element
	 */
	public function getElement($name) {
		return $this->elements[$name];
	}
	/**
	 * 
	 * @param string $name
	 * @var ComplexType
	 */
	public function getType($name) {
		return $this->types[$name];
	}
	/**
	 * 
	 * @param string $name
	 * @var Attribute
	 */
	public function findAttribute($node, $name) {
		return $this->getAttribute($name);
	}
	/**
	 * 
	 * @param string $name
	 * @var ComplexType
	 */
	public function findType($ns,$name) {
		return $this->getType($name);
	}
	/**
	 * 
	 * @param string $name
	 * @var Element
	 */
	public function findElement($ns,$name) {
		return $this->getElement($name);
	}
	public function findAttributeGroup($ns,$name) {
		return $this->getElement($name);
	}
	public function findGroup($ns,$name) {
		return $this->getElement($name);
	}
} 