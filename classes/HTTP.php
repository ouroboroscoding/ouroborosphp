<?php
/**
 * HTTP Class, used for cleaning and reading inputs, for loading controllers and
 * views, everything you need to interact with HTTP requests
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @package core
 * @version 0.1
 * @created 2012-05-29
 */

/**
 * include required classes
 */
require_once 'Template.php';

/**
 * HTTP Class
 * @name HTTP
 * @package core
 */
class HTTP
{
	/**
	 * Constructor
	 * Does nothing, call initialize to setup the instance
	 * @name HTTP
	 * @access public
	 * @return HTTP
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
	 * Sets up the HTTP instance based on the config
	 * @name initialize
	 * @access public
	 * @param Config $in_config			Configuration
	 * @param string $in_name			Name of the instance
	 * @return bool
	 */
	public function initialize(Config $in_config, /*string*/ $in_name)
	{
		// @todo store config and name
		// @todo setup debugging
		// @todo clean inputs
		// @todo set path
		// @todo check guimode
		// @todo check controller
	}

	/**
	 * Process
	 * Calls the controller and loads whatever views are necessary
	 * @name process
	 * @access public
	 * @return void
	 */
	public function process()
	{
		// @todo load view(s)
		// @todo load controller
	}

	/**
	 * Options
	 *
	 * controller param => 'c' or 'controller' or 'action', etc
	 *
	 *
	 * guimode param => 'g' or 'guimode' or 'gui', etc
	 * 			'hvf'	=> header, view, and footer
	 * 			'v'		=> view only
	 * 			'hv'	=> header and view
	 * 			''		=> do nothing
	 * 		alternately this could be passed in the XML/config for each page
	 *
	 *
	 * header name	=> 'header.tpl'
	 *
	 *
	 * footer name	=> 'footer.tpl'
	 *
	 *
	 * mode	=> 'path_pairs'
	 * 				'/hello/there/my/friend/' = array('hello' => 'there', 'my' => 'friend')
	 * 			'path'
	 * 				'/hello/there/my/friend/' = array('hello', 'there', 'my', 'friend')
	 * 			'params'
	 * 				'?hello=there&my=friends' = array('hello' => 'there', 'my' => 'friend')
	 *
	 *
	 * debug activator	=> 'debug', '_debug_', 'frank', 'fuckoff', whatever
	 *
	 *
	 * name => 'Hey hey hey interface'
	 *
	 */
}