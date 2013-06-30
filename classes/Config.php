<?php
/**
 * Configuration class, used to loading and generating configuration files
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-05-29
 */

/**
 * Config class
 * @name Config
 * @package core
 */
class Config
{
	/**
	 * Values array
	 * @var array
	 * @access private
	 */
	private /*array*/ $aValues;

	/**
	 * Constructor
	 * Initialises the configuration instance
	 * @name Config
	 * @access public
	 * @return Config
	 */
	public function __construct()
	{
		$this->aValues	= array();
	}

	/**
	 * __get
	 * PHP overload method for getting unknown variables
	 * @name __get
	 * @access public
	 * @param string $in_name			The name of the variable request
	 * @return mixed
	 */
	public function __get(/*string*/ $in_name)
	{
		// If the value doesn't exist
		if(!isset($this->aValues[$in_name]))
		{
			return null;
		}

		return $this->aValues[$in_name];
	}

	/**
	 * __isset
	 * PHP overload method for checking unknown variables
	 * @name __isset
	 * @access public
	 * @param string $in_name			The name of the variable to check
	 * @return bool
	 */
	public function __isset(/*string*/ $in_name)
	{
		return isset($this->aValue[$in_name]);
	}

	/**
	 * Generate
	 * Returns a string appropriate for saving as a configuration file
	 * @name generate
	 * @access public
	 * @return string
	 */
	public function generate()
	{
		return "<?php\nreturn " . var_export($this->aValues, true) . ";\n?>";
	}

	/**
	 * Get
	 * Returns configuration values, the more arguments you send, the deeper the returned value
	 * @name get
	 * @access public
	 * @param string $in_name			Name of the configuration value
	 * @param mixed $in_default			The value to return if it's not found
	 * @return mixed
	 */
	public function get(/*string*/ $in_name = null, /*mixed*/ $in_default = null)
	{
		// If the name wasn't sent
		if(is_null($in_name))
		{
			// Return the entire array
			return $this->aValues;
		}

		// Split the name by colon
		$aNames	= explode(':', $in_name);
		$iCount	= count($aNames);

		// If the first name doesn't exist, return the default
		if(!isset($this->aValues[$sNames[0]]))
		{
			return $in_default;
		}

		// Set the first level
		$mData	= $this->aValues[$sNames[0]];

		// Keep going through the names till we reach the end
		for($i = 1; $i < $iCount; ++$i)
		{
			// If the data doesn't exist, return null
			if(!isset($mData[$sNames[$i]]))
			{
				return $in_default;
			}

			// Set the current level and loop around
			$mData	= $mData[$sNames[$i]];
		}

		// Return the last data we found
		return $mData;
	}

	/**
	 * Load
	 * Loads a config file into memory
	 * @name load
	 * @access public
	 * @static
	 * @param string $in_filename		Full or relative path to the configuration file
	 * @return bool
	 */
	public function load(/*string*/ $in_filename)
	{
		// Check if the file doesn't exists
		if(!file_exists($in_filename))
		{
			return false;
		}

		// Include the array the config file returns
		$this->aValues	= include $in_filename;

		// If the return value isn't an array the file isn't done properly
		if(!is_array($this->aValues))
		{
			return false;
		}

		// Let the caller know we were succesful
		return true;
	}

	/**
	 * Load From XML
	 * Loads a config file from an XML, converts it to a PHP array, and stores it in memory
	 * @name loadFromXML
	 * @access public
	 * @param string $in_filename		Full or relative path to the XML file
	 * @return bool
	 */
	public function loadFromXML(/*string*/ $in_filename)
	{
		// Check if the file doesn't exists
		if(!file_exists($in_filename))
		{
			return false;
		}

		// If it does, load it into memory
		$sXML	= file_get_contents($in_filename);

		// Pass the file to SimpleXML
		$oXML	= new SimpleXMLElement($sXML);
		unset($sXML);

		// Convert the SimpleXMLElement to an array and store it
		$this->aValues	= self::xmlToArray($oXML);
		unset($oXML);

		// Let the caller know we were successful
		return true;
	}

	/**
	 * Save
	 * Returns a string suitable for saving the config file
	 * @name generate
	 * @access public
	 * @param string $in_filename		Full or relative path to save the configuration file
	 * @return bool
	 */
	public function save(/*string*/ $in_filename)
	{
		// Try to save the file
		$mRet	= file_put_contents($in_filename, $this->generate(), LOCK_EX);

		// If we got false, we failed
		return ($mRet === false) ? false : true;
	}

	/**
	 * xmlToArray
	 * Recursive function that converts a tree of SimpleXMLElements into a flatter array format more suitable for local storage
	 * @name xmlToArray
	 * @access public
	 * @static
	 * @param SimpleXMLElement $in_xml	The element to convert
	 * @return array
	 */
	private static function xmlToArray(SimpleXMLElement $in_xml)
	{
		// Init return array
		$aRet	= array();

		// Go through every child of the element
		foreach($in_xml->children(null, true) as $oSimpleXML)
		{
			// Get the name
			$sName	= strtolower($oSimpleXML->getName());

			// Try to get the text
			$sText	= trim((string)$oSimpleXML);

			// If there was text
			if(strlen($sText))
			{
				// Get all the attributes for the element
				$aAttrs	= $oSimpleXML->attributes();

				// If there's a type attribute
				if(isset($aAttrs['type']))
				{
					// Store the text based on the type
					switch($aAttrs['type'])
					{
						case 'array':
							// Check if the delimeter was passed
							$sDeli	= (isset($aAttrs['delimiter'])) ?
										$aAttrs['delimiter'] :
										',';

							// Separate the string into parts
							$aRet[$sName]	= explode($sDeli, $sText);
							break;

						case 'decimal':
						case 'float':
							$aRet[$sName]	= floatval($sText);
							break;

						case 'bool':
						case 'boolean':
							$sLowText		= mb_strtolower($sText, 'utf8');
							$aRet[$sName]	= (in_array($sText, array('true', 't', 'yes', 'y', '1'))) ?
												true : false;
							break;

						case 'int':
						case 'integer':
							$aRet[$sName]	= intval($sText);
							break;

						default:
							trigger_error("Invalid modifier user in tag {$sName}: {$aAttrs['type']}", E_USER_WARNING);
							$aRet[$sName]	= $sText;
							break;
					}
				}
				else
				{
					// Save it under the name of this child
					$aRet[$sName]	= $sText;
				}
			}
			// If there's children in this child
			else if($oSimpleXML->count())
			{
				// Call xmlToArray again to go deeper into the tree
				$aRet[$sName]	= self::xmlToArray($oSimpleXML);
			}
			// If there's no text and no children, save the key with no value
			else
			{
				$aRet[$sName]	= null;
			}
		}

		return $aRet;
	}
}