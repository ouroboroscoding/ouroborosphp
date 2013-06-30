<?php
/**
 * For displaying and writing templates
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2011-04-04
 */

/**
 * Template class
 * @name Template
 * @package core
 */
class Template
{
	/**
	 * List of paths to look for templates in
	 * @var array						of strings
	 * @access private
	 */
	private $_aPaths;

	/**
	 * Name/Value pairs
	 * @var array						of mixed
	 * @access private
	 */
	private $_aValues;

	/**
	 * The source file to run/parse
	 * @var string
	 * @access private
	 */
	private $_sSource;

	/**
	 * Constructor
	 * Initializes the object
	 * @name Template
	 * @access public
	 * @return Template
	 */
	public function __construct()
	{
		// Always add the current path
		$this->_aPaths	= array('./');

		// Init the source to null
		$this->_sSource	= null;
	}

	/**
	 * Add Path
	 * Add a path to the instance, when we look up a file we use these paths
	 * @name addPath
	 * @access public
	 * @param string $in_path			Path to add
	 * @return void
	 */
	public function addPath(/*string*/ $in_path)
	{
		// Check the path ends with a slash
		if(substr($in_path, -1) != '/')
		{
			$in_path	.= '/';
		}

		// Add it to the list
		$this->_aPaths[]	= $in_path;
	}

	/**
	 * Assign
	 * Assign a value to a name
	 * @name assign
	 * @access public
	 * @param string $in_name			Name of variable
	 * @param mixed $in_value			Value
	 * @return void
	 */
	public function assign(/*string*/ $in_name, /*mixed*/ $in_value)
	{
		$this->_aValues[$in_name]	= $in_value;
	}

	/**
	 * Assign Map
	 * This will overwrite all current variables in the template with the ones sent
	 * @name assignMap
	 * @access public
	 * @param array $in_map				Array with index being the names of the variables to set
	 * @return void
	 */
	public function assignMap(array $in_map)
	{
		$this->_aValues	= $in_map;
	}

	/**
	 * Source
	 * Set and/or get the source file
	 * @name source
	 * @access public
	 * @param string $in_filename		The filename of the source
	 * @return string
	 */
	public function source(/*string*/ $in_filename = null)
	{
		// If the argument is a string, store it to the source
		if(is_string($in_filename))
		{
			$this->_sSource	= $in_filename;
		}

		// Return the current source value
		return $this->_sSource;
	}

	/**
	 * Display
	 * Prints out a template to the screen
	 * @name display
	 * @access public
	 * @param string $in_filename		Full or relative path to a template
	 * @return void
	 */
	public function display(/*string*/ $in_filename = null)
	{
		echo $this->fetch($in_filename);
	}

	/**
	 * Fetch
	 * Fetch and return a template
	 * @name fetch
	 * @access public
	 * @param string $in_filename		Full or relative path to a template
	 * @return string
	 */
	public function fetch(/*string*/ $in_filename = null)
	{
		$sFilename	= null;

		// If no filename was passed and we have one stored in the instance, use
		//	it
		if(is_null($in_filename) && !is_null($this->_sSource))
		{
			$in_filename	= $this->_sSource;
		}

		// Check if the passed filename is specific or we should use the paths
		if(preg_match('/^(?:\.\.\/|\.\/|\/)/', $in_filename))
		{
			if(file_exists($in_filename))
			{
				$sFilename	= $in_filename;
			}
		}
		else
		{
			// Go through each path looking for the file
			foreach($this->_aPaths as $sPath)
			{
				if(file_exists($sPath . $in_filename))
				{
					$sFilename	= $sPath . $in_filename;
				}
			}
		}

		// If we found the file, load it and return it
		if($sFilename)
		{
			ob_start();
			include $sFilename;
			return ob_get_clean();
		}

		// The file wasn't found, throw an error
		$aBT	= debug_backtrace();
		trigger_error(
			"Couldn't load template \"{$in_filename}\" in {$aBT[0]['file']} on line {$aBT[0]['line']}.",
			E_USER_WARNING
		);
		return null;
	}

	/**
	 * __set
	 * Called when unknown variables are set, works the same as the assign method
	 * @name __set
	 * @access public
	 * @param string $in_name			Name of variable
	 * @param mixed $in_value			Value
	 * @return void
	 */
	public function __set(/*string*/ $in_name, /*mixed*/ $in_value)
	{
		$this->_aValues[$in_name]	= $in_value;
	}

	/**
	 * __get
	 * Called when unknown variables are requested
	 * @name __get
	 * @access public
	 * @param string $in_name			Name of variable
	 * @return mixed
	 */
	public function __get(/*string*/ $in_name)
	{
		if(array_key_exists($in_name, $this->_aValues))
		{
			return $this->_aValues[$in_name];
		}
		else
		{
			// The variable wasn't found, so trigger an error with the file and line that called this
			$aBT	= debug_backtrace();
			trigger_error(
				'Undefined property:  ' . __CLASS__ . '::$' . $in_name . ' in ' . $aBT[0]['file'] . ' on line ' . $aBT[0]['line'],
				E_USER_NOTICE
			);
			return null;
		}
	}

	/**
	 * __isset
	 * Called when someone calls isset on an unknown variable
	 * @name __isset
	 * @access public
	 * @param string $in_name			Name of variable
	 * @return bool
	 * @see isset
	 */
	public function __isset(/*string*/ $in_name)
	{
		return isset($this->_aValues[$in_name]);
	}

	/**
	 * __unset
	 * Called when someone tries to unset an unknown variable
	 * @name __unset
	 * @access public
	 * @param string $in_name			Name of variable
	 * @return void
	 * @see unset
	 */
	public function __unset(/*string*/ $in_name)
	{
		if(isset($this->_aValues[$in_name]))
		{
			unset($this->_aValues[$in_name]);
		}
	}

}
