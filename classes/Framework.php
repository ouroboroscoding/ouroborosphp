<?php
/**
 * Framework class, with exceptions, used for initialising the environment based on type
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-05-30
 */

// @todo replace all trigger_error calls with appropriate exception

/**
 * Include required classes
 */
require_once dirname(__FILE__) . '/Config.php';

/**
 * Framework class
 * @name Framework
 * @package core
 */
class Framework
{
	/**
	 * Holds the instance of the framework created in initialize
	 * @var mixed
	 * @access private
	 * @static
	 */
	private static $soInstance	= null;

	/**
	 * Holds the main configuration instance of the framework
	 * @var Config
	 * @access private
	 * @static
	 */
	private static $soConfig	= null;

	/**
	 * Holds the type of instance we created
	 * @var string
	 * @access private
	 * @static
	 */
	private static $ssType		= '';

	/**
	 * Cleanup
	 * Cleans up after the framework and prints debugging info
	 * @name cleanup
	 * @access public
	 * @static
	 * @return void
	 */
	public static function cleanup()
	{
		// If there is no instance, quit
		if(is_null(self::$soInstance))	return;

		// Cleanup after the instance
		self::$soInstance->cleanup();
	}

	/**
	 * Get Type
	 * Return the variable type of the argument
	 * @name getType
	 * @access public
	 * @static
	 * @param mixed $in_var				The variable whose type we want to know
	 * @return string
	 */
	public static function getType($in_var)
	{
		$sType	= gettype($in_var);

		switch($sType)
		{
			case 'boolean':		return 'bool';
			case 'integer':		return 'int';
			case 'object':		return get_class($in_var);
			default:			return $sType;
		}
	}

	/**
	 * Include Class
	 * Takes the name of a class in the framework and loads it into the application
	 * @name includeClass
	 * @access public
	 * @static
	 * @param string|array $in_class	The name of the class to load
	 * @return bool
	 */
	public static function includeClass(/*string|array*/ $in_class)
	{
		// If the passed variable isn't an array, make it one
		if(!is_array($in_class))
		{
			$in_class	= array($in_class);
		}

		// Go through each class, check if it exists, if not, include it
		$bRet	= true;
		$sPath	= dirname(__FILE__);
		foreach($in_class as $sClass)
		{
			if(!class_exists($sClass))
			{
				// Keep track of any classes that fail to load
				$bRet	= $bRet & (include ($sPath . '/' . $in_class . '.php'));
			}
		}

		// Only returns true if all classes are loaded
		return $bRet;
	}

	/**
	 * Initialize
	 * Sets up the system by loading the configuration, initializing the the data
	 * sources, then loading and initializing the appropriate environment class
	 * (HTTP or Script)
	 * @name initialize
	 * @access public
	 * @static
	 * @param string $in_type			The type of environment
	 * @param string $in_name			The name of the instance in the config
	 * @return mixed
	 */
	public static function initialize(/*string*/ $in_type, /*string*/ $in_name)
	{
		// First, load the configuration
		$oConfig	= self::loadConfig();

		// Then, load the SQL servers
		self::loadSQL($oConfig);

		// Then, load the Cache servers
		self::loadCache($oConfig);

		// Lowercase and store the type
		self::$ssType	= mb_strtolower($in_type);

		// Load the appropriate wrapper based on the type
		switch(self::$ssType)
		{
			case 'http':
				// Include the HTTP class
				self::includeClass('HTTP');

				// Create a new instance of HTTP
				self::$soInstance	= new HTTP();

				// Initialize the framework
				self::$soInstance->initialize($in_config, $in_name);

				break;

			case 'script':
				// Include the Script class
				self::includeClass('Script');

				// Create a new instance of Script
				self::$soInstance	= new Script();

				// Initialize the framework
				self::$soInstance->initialize($in_config, $in_name);

				break;

			default:
				trigger_error("Invalid environment type passed to " . __METHOD__ . ": {$in_type}.", E_USER_ERROR);
				break;
		}

		// Return the instance
		return self::$soInstance;
	}

	/**
	 * Is CLI
	 * Returns whether we're using the command line interface or not
	 * @name isCLI
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function isCLI()
	{
		return ('cli' == PHP_SAPI);
	}

	/**
	 * Load Config
	 * Loads the frameworks configuration file in a Config instance, then returns it
	 * @name loadConfig
	 * @access public
	 * @static
	 * @return Config					Returns false on failure
	 */
	private static function loadConfig()
	{
		// If we already have the config, return it
		if(!is_null(self::$soConfig))	return self::$soConfig;

		// Load the Config class
		self::includeClass('Config');

		// Create an instance of Config
		self::$soConfig	= new Config();

		// Get the root of the framework
		$sRoot		= realpath(dirname(__FILE__) . '../');

		// Generate the config path
		$sConfig		= $sRoot . '/config.inc.php';

		// Try to load the PHP config
		if(!self::$soConfig->load($sConfig))
		{
			// Generate the XML path
			$sXML	= $sRoot . '/config.xml';

			// If it failed, try to load the XML version
			if(!self::$soConfig->loadFromXML($sXML))
			{
				// If it failed again let user know and exit
				trigger_error("Unable to load configuration file {$sXML}.", E_USER_ERROR);
			}

			// If we successfully loaded the XML, check if we should save it as PHP
			if(self::$soConfig->get('load:save_php', true))
			{
				// Try to save it
				if(!self::$soConfig->save($sConfig))
				{
					// If we failed, notify the user
					trigger_error("Could not save the config to {$sConfig}. If you do not wish to save your XML configuration as PHP to decrease load times, please set the <load><save_php></load> element to false.", E_USER_WARNING);
				}
			}
		}

		// Return the config
		return self::$soConfig;
	}
}

/**
 * Thrown when an invalid variable type is passed to a method or function
 * @name FWInvalidType
 * @package core
 * @subpackage Framework
 */
class FWInvalidType extends Exception
{
	/**
	 * Constructor
	 * Initializes the object
	 * @name FWInvalidType
	 * @access public
	 * @param string $in_method			Method that received an invalid argument type
	 * @param string $in_expected_type	Type of argument expected
	 * @param string $in_argument		The argument received
	 * @return FWInvalidType
	 */
	public function __construct($in_method, $in_expected_type, $in_argument)
	{
		// Get the type of argument
		$sType	= Framework::getType($in_argument);

		// Get backtrace
		$aBT	= debug_backtrace();

		// Pass constructed string to parent constructor
		parent::__construct("Invalid argument supplied to {$in_method}. Expected '{$in_expected_type}' but got '{$sType}'. Called in {$aBT[1]['file']} on line {$aBT[1]['line']}\n");
	}
}

/**
 * Thrown when an invalid value is passed to a method or function
 * @name FWInvalidValue
 * @package core
 * @subpackage Framework
 */
class FWInvalidValue extends Exception
{
	/**
	 * Constructor
	 * Initializes the object
	 * @name FWInvalidValue
	 * @access public
	 * @param string $in_method			Method that received an invalid argument type
	 * @param string $in_expected_value	Value of argument expected
	 * @param string $in_received_value	Value of argument received
	 * @return FWInvalidValue
	 */
	public function __construct($in_method, $in_expected_value, $in_received_value)
	{
		// Get backtrace
		$aBT	= debug_backtrace();

		// Pass constructed string to parent constructor
		parent::__construct("Invalid value supplied to {$in_method}. Expected '{$in_expected_value}' but got '{$in_received_value}'. Called in {$aBT[1]['file']} on line {$aBT[1]['line']}\n");
	}
}