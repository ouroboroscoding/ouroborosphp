<?php
/**
 * Script Class, used for running scripts from the cli
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-05-30
 */

/**
 * Script class
 * @name Script
 * @package core
 */
class Script
{
	/**
	 * Constructor
	 * Does nothing, call initialize to setup the instance
	 * @name Script
	 * @access public
	 * @return Script
	 */
	public function __construct() {}

	/**
	 * Cleanup
	 * Cleans up after the instance
	 * @name cleanup
	 * @access public
	 * @return void
	 */
	public function cleanup()
	{
		// @todo display debugging
	}

	/**
	 * Initialize
	 * Sets up the Script instance based on the config
	 * @name initialize
	 * @access public
	 * @param Config $in_config			Configuration
	 * @param string $in_name			Name of the instance
	 * @return bool
	 */
	public function initialize(Config $in_config, /*string*/ $in_name)
	{
		// @todo Setup debugging
	}

	/**
	 * Process
	 * Completely unnecesary for scripting, added for simplicity
	 * @name process
	 * @access public
	 * @return void
	 */
	public function process()
	{
		// Does nothing, empty function
	}
}