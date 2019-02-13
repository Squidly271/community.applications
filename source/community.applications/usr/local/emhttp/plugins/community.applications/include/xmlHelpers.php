 <?PHP

 /**
 * @copyright Copyright 2006-2012, Miles Johnson - http://milesj.me
 * @license   http://opensource.org/licenses/mit-license.php - Licensed under the MIT License
 * @link    http://milesj.me/code/php/type-converter
 */

/**
 * A class that handles the detection and conversion of certain resource formats / content types into other formats.
 * The current formats are supported: XML, JSON, Array, Object, Serialized
 *
 * @version 2.0.0
 * @package mjohnson.utility
 */
class TypeConverter {

	/**
	 * Disregard XML attributes and only return the value.
	 */
	const XML_NONE = 0;

	/**
	 * Merge attributes and the value into a single dimension; the values key will be "value".
	 */
	const XML_MERGE = 1;

	/**
	 * Group the attributes into a key "attributes" and the value into a key of "value".
	 */
	const XML_GROUP = 2;

	/**
	 * Attributes will only be returned.
	 */
	const XML_OVERWRITE = 3;

	/**
	 * Returns a string for the detected type.
	 *
	 * @access public
	 * @param mixed $data
	 * @return string
	 * @static
	 */
	public static function is($data) {
		if (self::isArray($data)) {
			return 'array';

		} else if (self::isObject($data)) {
			return 'object';

		} else if (self::isJson($data)) {
			return 'json';

		} else if (self::isSerialized($data)) {
			return 'serialized';

		} else if (self::isXml($data)) {
			return 'xml';
		}

		return 'other';
	}

	/**
	 * Check to see if data passed is an array.
	 *
	 * @access public
	 * @param mixed $data
	 * @return boolean
	 * @static
	 */
	public static function isArray($data) {
		return is_array($data);
	}

	/**
	 * Check to see if data passed is a JSON object.
	 *
	 * @access public
	 * @param mixed $data
	 * @return boolean
	 * @static
	 */
	public static function isJson($data) {
		return (@json_decode($data) !== null);
	}

	/**
	 * Check to see if data passed is an object.
	 *
	 * @access public
	 * @param mixed $data
	 * @return boolean
	 * @static
	 */
	public static function isObject($data) {
		return is_object($data);
	}

	/**
	 * Check to see if data passed has been serialized.
	 *
	 * @access public
	 * @param mixed $data
	 * @return boolean
	 * @static
	 */
	public static function isSerialized($data) {
		$ser = @unserialize($data);

		return ($ser !== false) ? $ser : false;
	}

	/**
	 * Check to see if data passed is an XML document.
	 *
	 * @access public
	 * @param mixed $data
	 * @return boolean
	 * @static
	 */
	public static function isXml($data) {
		$xml = @simplexml_load_string($data);

		return ($xml instanceof SimpleXmlElement) ? $xml : false;
	}

	/**
	 * Transforms a resource into an array.
	 *
	 * @access public
	 * @param mixed $resource
	 * @return array
	 * @static
	 */
	public static function toArray($resource) {
		if (self::isArray($resource)) {
			return $resource;

		} else if (self::isObject($resource)) {
			return self::buildArray($resource);

		} else if (self::isJson($resource)) {
			return json_decode($resource, true);

		} else if ($ser = self::isSerialized($resource)) {
			return self::toArray($ser);

		} else if ($xml = self::isXml($resource)) {
			return self::xmlToArray($xml);
		}

		return $resource;
	}

	/**
	 * Transforms a resource into a JSON object.
	 *
	 * @access public
	 * @param mixed $resource
	 * @return string (json)
	 * @static
	 */
	public static function toJson($resource) {
		if (self::isJson($resource)) {
			return $resource;
		}

		if ($xml = self::isXml($resource)) {
			$resource = self::xmlToArray($xml);

		} else if ($ser = self::isSerialized($resource)) {
			$resource = $ser;
		}

		return json_encode($resource);
	}

	/**
	 * Transforms a resource into an object.
	 *
	 * @access public
	 * @param mixed $resource
	 * @return object
	 * @static
	 */
	public static function toObject($resource) {
		if (self::isObject($resource)) {
			return $resource;

		} else if (self::isArray($resource)) {
			return self::buildObject($resource);

		} else if (self::isJson($resource)) {
			return json_decode($resource);

		} else if ($ser = self::isSerialized($resource)) {
			return self::toObject($ser);

		} else if ($xml = self::isXml($resource)) {
			return $xml;
		}

		return $resource;
	}

	/**
	 * Transforms a resource into a serialized form.
	 *
	 * @access public
	 * @param mixed $resource
	 * @return string
	 * @static
	 */
	public static function toSerialize($resource) {
		if (!self::isArray($resource)) {
			$resource = self::toArray($resource);
		}

		return serialize($resource);
	}

	/**
	 * Transforms a resource into an XML document.
	 *
	 * @access public
	 * @param mixed $resource
	 * @param string $root
	 * @return string (xml)
	 * @static
	 */
	public static function toXml($resource, $root = 'root') {
		if (self::isXml($resource)) {
			return $resource;
		}

		$array = self::toArray($resource);

		if (!empty($array)) {
			$xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><'. $root .'></'. $root .'>');
			$response = self::buildXml($xml, $array);

			return $response->asXML();
		}

		return $resource;
	}

	/**
	 * Turn an object into an array. Alternative to array_map magic.
	 *
	 * @access public
	 * @param object $object
	 * @return array
	 */
	public static function buildArray($object) {
		$array = array();

		foreach ($object as $key => $value) {
			if (is_object($value)) {
				$array[$key] = self::buildArray($value);
			} else {
				$array[$key] = $value;
			}
		}

		return $array;
	}

	/**
	 * Turn an array into an object. Alternative to array_map magic.
	 *
	 * @access public
	 * @param array $array
	 * @return object
	 */
	public static function buildObject($array) {
		$obj = new \stdClass();

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$obj->{$key} = self::buildObject($value);
			} else {
				$obj->{$key} = $value;
			}
		}

		return $obj;
	}

	/**
	 * Turn an array into an XML document. Alternative to array_map magic.
	 *
	 * @access public
	 * @param object $xml
	 * @param array $array
	 * @return object
	 */
	public static function buildXml(&$xml, $array) {
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				// XML_NONE
				if (!is_array($value)) {
					$xml->addChild($key, $value);
					continue;
				}

				// Multiple nodes of the same name
				if (isset($value[0])) {
					foreach ($value as $kValue) {
						if (is_array($kValue)) {
							self::buildXml($xml, array($key => $kValue));
						} else {
							$xml->addChild($key, $kValue);
						}
					}

				// XML_GROUP
				} else if (isset($value['@attributes'])) {
					if (is_array($value['value'])) {
						$node = $xml->addChild($key);
						self::buildXml($node, $value['value']);
					} else {
						$node = $xml->addChild($key, $value['value']);
					}

					if (!empty($value['@attributes'])) {
						foreach ($value['@attributes'] as $aKey => $aValue) {
							$node->addAttribute($aKey, $aValue);
						}
					}

				// XML_MERGE
				} else if (isset($value['value'])) {
					$node = $xml->addChild($key, $value['value']);
					unset($value['value']);

					if (!empty($value)) {
						foreach ($value as $aKey => $aValue) {
							if (is_array($aValue)) {
								self::buildXml($node, array($aKey => $aValue));
							} else {
								$node->addAttribute($aKey, $aValue);
							}
						}
					}

				// XML_OVERWRITE
				} else {
					$node = $xml->addChild($key);

					if (!empty($value)) {
						foreach ($value as $aKey => $aValue) {
							if (is_array($aValue)) {
								self::buildXml($node, array($aKey => $aValue));
							} else {
								$node->addChild($aKey, $aValue);
							}
						}
					}
				}
			}
		}

		return $xml;
	}

	/**
	 * Convert a SimpleXML object into an array.
	 *
	 * @access public
	 * @param object $xml
	 * @param int $format
	 * @return array
	 */
	public static function xmlToArray($xml, $format = self::XML_GROUP) {
		if (is_string($xml)) {
			$xml = @simplexml_load_string($xml);
		}
		if ( ! $xml ) { return false; }
		if (count($xml->children()) <= 0) {
			return (string)$xml;
		}

		$array = array();

		foreach ($xml->children() as $element => $node) {
			$data = array();

			if (!isset($array[$element])) {
#       $array[$element] = "";
				$array[$element] = [];
			}

			if (!$node->attributes() || $format === self::XML_NONE) {
				$data = self::xmlToArray($node, $format);

			} else {
				switch ($format) {
					case self::XML_GROUP:
						$data = array(
							'@attributes' => array(),
							'value' => (string)$node
						);

						if (count($node->children()) > 0) {
							$data['value'] = self::xmlToArray($node, $format);
						}

						foreach ($node->attributes() as $attr => $value) {
							$data['@attributes'][$attr] = (string)$value;
						}
					break;

					case self::XML_MERGE:
					case self::XML_OVERWRITE:
						if ($format === self::XML_MERGE) {
							if (count($node->children()) > 0) {
								$data = $data + self::xmlToArray($node, $format);
							} else {
								$data['value'] = (string)$node;
							}
						}

						foreach ($node->attributes() as $attr => $value) {
							$data[$attr] = (string)$value;
						}
					break;
				}
			}

			if (count($xml->{$element}) > 1) {
				$array[$element][] = $data;
			} else {
				$array[$element] = $data;
			}
		}

		return $array;
	}

	/**
	 * Encode a resource object for UTF-8.
	 *
	 * @access public
	 * @param mixed $data
	 * @return array|string
	 * @static
	 */
	public static function utf8Encode($data) {
		if (is_string($data)) {
			return utf8_encode($data);

		} else if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[utf8_encode($key)] = self::utf8Encode($value);
			}

		} else if (is_object($data)) {
			foreach ($data as $key => $value) {
				$data->{$key} = self::utf8Encode($value);
			}
		}

		return $data;
	}

	/**
	 * Decode a resource object for UTF-8.
	 *
	 * @access public
	 * @param mixed $data
	 * @return array|string
	 * @static
	 */
	public static function utf8Decode($data) {
		if (is_string($data)) {
			return utf8_decode($data);

		} else if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[utf8_decode($key)] = self::utf8Decode($value);
			}

		} else if (is_object($data)) {
			foreach ($data as $key => $value) {
				$data->{$key} = self::utf8Decode($value);
			}
		}

		return $data;
	}

}

 /**
 * Array2XML: A class to convert array in PHP to XML
 * It also takes into account attributes names unlike SimpleXML in PHP
 * It returns the XML in form of DOMDocument class for further manipulation.
 * It throws exception if the tag name or attribute name has illegal chars.
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (10 July 2011)
 * Version: 0.2 (16 August 2011)
 *          - replaced htmlentities() with htmlspecialchars() (Thanks to Liel Dulev)
 *          - fixed a edge case where root node has a false/null/0 value. (Thanks to Liel Dulev)
 * Version: 0.3 (22 August 2011)
 *          - fixed tag sanitize regex which didn't allow tagnames with single character.
 * Version: 0.4 (18 September 2011)
 *          - Added support for CDATA section using @cdata instead of @value.
 * Version: 0.5 (07 December 2011)
 *          - Changed logic to check numeric array indices not starting from 0.
 * Version: 0.6 (04 March 2012)
 *          - Code now doesn't @cdata to be placed in an empty array
 * Version: 0.7 (24 March 2012)
 *          - Reverted to version 0.5
 * Version: 0.8 (02 May 2012)
 *          - Removed htmlspecialchars() before adding to text node or attributes.
 *
 * Usage:
 *       $xml = Array2XML::createXML('root_node_name', $php_array);
 *       echo $xml->saveXML();
 */
class Array2XML {
		private static $xml = null;
	private static $encoding = 'UTF-8';
		/**
		 * Initialize the root XML node [optional]
		 * @param $version
		 * @param $encoding
		 * @param $format_output
		 */
		public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
				self::$xml = new DomDocument($version, $encoding);
				self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
		}
		/**
		 * Convert an Array to XML
		 * @param string $node_name - name of the root node to be converted
		 * @param array $arr - aray to be converterd
		 * @return DomDocument
		 */
		public static function &createXML($node_name, $arr=array()) {
				$xml = self::getXMLRoot();
				$xml->appendChild(self::convert($node_name, $arr));
				self::$xml = null;    // clear the xml node in the class for 2nd time use.
				return $xml;
		}
		/**
		 * Convert an Array to XML
		 * @param string $node_name - name of the root node to be converted
		 * @param array $arr - aray to be converterd
		 * @return DOMNode
		 */
		private static function &convert($node_name, $arr=array()) {
				//print_arr($node_name);
				$xml = self::getXMLRoot();
				$node = $xml->createElement($node_name);
				if(is_array($arr)){
						// get the attributes first.;
						if(isset($arr['@attributes'])) {
								foreach($arr['@attributes'] as $key => $value) {
										if(!self::isValidTagName($key)) {
												throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
										}
										$node->setAttribute($key, self::bool2str($value));
								}
								unset($arr['@attributes']); //remove the key from the array once done.
						}
						// check if it has a value stored in @value, if yes store the value and return
						// else check if its directly stored as string
						if(isset($arr['@value'])) {
								$node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
								unset($arr['@value']);    //remove the key from the array once done.
								//return from recursion, as a note with value cannot have child nodes.
								return $node;
						} else if(isset($arr['@cdata'])) {
								$node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
								unset($arr['@cdata']);    //remove the key from the array once done.
								//return from recursion, as a note with cdata cannot have child nodes.
								return $node;
						}
				}
				//create subnodes using recursion
				if(is_array($arr)){
						// recurse to get the node for that key
						foreach($arr as $key=>$value){
								if(!self::isValidTagName($key)) {
										throw new Exception('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
								}
								if(is_array($value) && is_numeric(key($value))) {
										// MORE THAN ONE NODE OF ITS KIND;
										// if the new array is numeric index, means it is array of nodes of the same kind
										// it should follow the parent key name
										foreach($value as $k=>$v){
												$node->appendChild(self::convert($key, $v));
										}
								} else {
										// ONLY ONE NODE OF ITS KIND
										$node->appendChild(self::convert($key, $value));
								}
								unset($arr[$key]); //remove the key from the array once done.
						}
				}
				// after we are done with all the keys in the array (if it is one)
				// we check if it has any text value, if yes, append it.
				if(!is_array($arr)) {
						$node->appendChild($xml->createTextNode(self::bool2str($arr)));
				}
				return $node;
		}
		/*
		 * Get the root XML node, if there isn't one, create it.
		 */
		private static function getXMLRoot(){
				if(empty(self::$xml)) {
						self::init();
				}
				return self::$xml;
		}
		/*
		 * Get string representation of boolean value
		 */
		private static function bool2str($v){
				//convert boolean to text value.
				$v = $v === true ? 'true' : $v;
				$v = $v === false ? 'false' : $v;
				return $v;
		}
		/*
		 * Check if the tag name or attribute name contains illegal characters
		 * Ref: http://www.w3.org/TR/xml/#sec-common-syn
		 */
		private static function isValidTagName($tag){
				$pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
				return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
		}
}

/**
 * XML2Array: A class to convert XML to array in PHP
 * It returns the array which can be converted back to XML using the Array2XML script
 * It takes an XML string or a DOMDocument object as an input.
 *
 * See Array2XML: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-xml-to-array-in-php-xml2array
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (07 Dec 2011)
 * Version: 0.2 (04 Mar 2012)
 *      Fixed typo 'DomDocument' to 'DOMDocument'
 *
 * Usage:
 *       $array = XML2Array::createArray($xml);
 */

class XML2Array {

		private static $xml = null;
	private static $encoding = 'UTF-8';

		/**
		 * Initialize the root XML node [optional]
		 * @param $version
		 * @param $encoding
		 * @param $format_output
		 */
		public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
				self::$xml = new DOMDocument($version, $encoding);
				self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
		}

		/**
		 * Convert an XML to Array
		 * @param string $node_name - name of the root node to be converted
		 * @param array $arr - aray to be converterd
		 * @return DOMDocument
		 */
		public static function &createArray($input_xml) {
				$xml = self::getXMLRoot();
		if(is_string($input_xml)) {
			$parsed = $xml->loadXML($input_xml);
			if(!$parsed) {
				throw new Exception('[XML2Array] Error parsing the XML string.');
			}
		} else {
			if(get_class($input_xml) != 'DOMDocument') {
				throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
			}
			$xml = self::$xml = $input_xml;
		}
		$array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
				self::$xml = null;    // clear the xml node in the class for 2nd time use.
				return $array;
		}

		/**
		 * Convert an Array to XML
		 * @param mixed $node - XML as a string or as an object of DOMDocument
		 * @return mixed
		 */
		private static function &convert($node) {
		$output = array();

		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
				$output['@cdata'] = trim($node->textContent);
				break;

			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;

			case XML_ELEMENT_NODE:

				// for each child node, call the covert function recursively
				for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
					$child = $node->childNodes->item($i);
					$v = self::convert($child);
					if(isset($child->tagName)) {
						$t = $child->tagName;

						// assume more nodes of same kind are coming
						if(!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					} else {
						//check if it is not an empty text node
						if($v !== '') {
							$output = $v;
						}
					}
				}

				if(is_array($output)) {
					// if only one node of its kind, assign it directly instead if array($value);
					foreach ($output as $t => $v) {
						if(is_array($v) && count($v)==1) {
							$output[$t] = $v[0];
						}
					}
					if(empty($output)) {
						//for empty nodes
						$output = '';
					}
				}

				// loop through the attributes and collect them
				if($node->attributes->length) {
					$a = array();
					foreach($node->attributes as $attrName => $attrNode) {
						$a[$attrName] = (string) $attrNode->value;
					}
					// if its an leaf node, store the value in @value instead of directly storing it.
					if(!is_array($output)) {
						$output = array('@value' => $output);
					}
					$output['@attributes'] = $a;
				}
				break;
		}
		return $output;
		}

		/*
		 * Get the root XML node, if there isn't one, create it.
		 */
		private static function getXMLRoot(){
				if(empty(self::$xml)) {
						self::init();
				}
				return self::$xml;
		}
}
?>