<?php
/**
 * View
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @version 0.1
 * @created 2014-12-02
 */

/**
 * View class
 *
 * Class for displaying templates
 *
 * @name _View
 */
class _View
{
	/**
	 * Paths
	 *
	 * List of paths to look for templates in
	 *
	 * @var string[]
	 * @access private
	 */
	private $_aPaths;

	/**
	 * Values
	 *
	 * Name/Value pairs
	 *
	 * @var array						of mixed
	 * @access private
	 */
	private $_aValues;

	/**
	 * Source
	 *
	 * The source file to run/parse
	 *
	 * @var string
	 * @access private
	 */
	private $_sSource;

	/**
	 * Constructor
	 *
	 * Initializes the object
	 *
	 * @name _View
	 * @access public
	 * @param string $source			View source
	 * @return _View
	 */
	public function __construct(/*string*/ $source = null)
	{
		// Always add the current path
		$this->_aPaths	= array('./');

		// Init the source to null
		$this->_sSource = $source;
	}

	/**
	 * Add Path
	 *
	 * Add a path to the instance, when we look up a file we use these paths
	 *
	 * @name addPath
	 * @access public
	 * @param string $path			Path to add
	 * @return void
	 */
	public function addPath(/*string*/ $path)
	{
		// Check the path ends with a slash
		if(substr($path, -1) != '/')
		{
			$path	.= '/';
		}

		// Add it to the list
		$this->_aPaths[]	= $path;
	}

	/**
	 * Assign
	 *
	 * Assign a value to a name
	 *
	 * @name assign
	 * @access public
	 * @param string $name			Name of variable
	 * @param mixed $value			Value
	 * @return void
	 */
	public function assign(/*string*/ $name, /*mixed*/ $value)
	{
		$this->_aValues[$name]	= $value;
	}

	/**
	 * Assign Map
	 *
	 * This will overwrite all current variables in the template with the ones sent
	 *
	 * @name assignMap
	 * @access public
	 * @param array $map				Array with index being the names of the variables to set
	 * @return void
	 */
	public function assignMap(array $map)
	{
		$this->_aValues = $map;
	}

	/**
	 * Source
	 *
	 * Set and/or get the source file
	 *
	 * @name source
	 * @access public
	 * @param string $filename		The filename of the source
	 * @return string
	 */
	public function source(/*string*/ $filename = null)
	{
		// If the argument is a string, store it to the source
		if(is_string($filename))
		{
			$this->_sSource = $filename;
		}

		// Return the current source value
		return $this->_sSource;
	}

	/**
	 * Display
	 *
	 * Prints out a template to the screen
	 *
	 * @name display
	 * @access public
	 * @param string $filename		Full or relative path to a template
	 * @return void
	 */
	public function display(/*string*/ $filename = null)
	{
		echo $this->fetch($filename);
	}

	/**
	 * Fetch
	 *
	 * Fetch and return a template
	 *
	 * @name fetch
	 * @access public
	 * @param string $filename		Full or relative path to a template
	 * @return string
	 */
	public function fetch(/*string*/ $filename = null)
	{
		$sFilename	= null;

		// If no filename was passed and we have one stored in the instance, use
		//	it
		if(is_null($filename) && !is_null($this->_sSource))
		{
			$filename	= $this->_sSource;
		}

		// Check if the passed filename is specific or we should use the paths
		if(preg_match('/^(?:\.\.\/|\.\/|\/)/', $filename))
		{
			if(file_exists($filename))
			{
				$sFilename	= $filename;
			}
		}
		else
		{
			// Go through each path looking for the file
			foreach($this->_aPaths as $sPath)
			{
				if(file_exists($sPath . $filename))
				{
					$sFilename	= $sPath . $filename;
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
			"Couldn't load template \"{$filename}\" in {$aBT[0]['file']} on line {$aBT[0]['line']}.",
			E_USER_WARNING
		);
		return null;
	}

	/**
	 * __set
	 *
	 * Called when unknown variables are set, works the same as the assign method
	 *
	 * @name __set
	 * @access public
	 * @param string $name			Name of variable
	 * @param mixed $value			Value
	 * @return void
	 */
	public function __set(/*string*/ $name, /*mixed*/ $value)
	{
		$this->_aValues[$name]	= $value;
	}

	/**
	 * __get
	 *
	 * Called when unknown variables are requested
	 *
	 * @name __get
	 * @access public
	 * @param string $name			Name of variable
	 * @return mixed
	 */
	public function __get(/*string*/ $name)
	{
		if(array_key_exists($name, $this->_aValues))
		{
			return $this->_aValues[$name];
		}
		else
		{
			// The variable wasn't found, so trigger an error with the file and line that called this
			$aBT	= debug_backtrace();
			trigger_error(
				'Undefined property:  ' . __CLASS__ . '::$' . $name . ' in ' . $aBT[0]['file'] . ' on line ' . $aBT[0]['line'],
				E_USER_NOTICE
			);
			return null;
		}
	}

	/**
	 * __isset
	 *
	 * Called when someone calls isset on an unknown variable
	 *
	 * @name __isset
	 * @access public
	 * @param string $name			Name of variable
	 * @return bool
	 * @see isset
	 */
	public function __isset(/*string*/ $name)
	{
		return isset($this->_aValues[$name]);
	}

	/**
	 * __unset
	 *
	 * Called when someone tries to unset an unknown variable
	 *
	 * @name __unset
	 * @access public
	 * @param string $name			Name of variable
	 * @return void
	 * @see unset
	 */
	public function __unset(/*string*/ $name)
	{
		if(isset($this->_aValues[$name]))
		{
			unset($this->_aValues[$name]);
		}
	}

}
