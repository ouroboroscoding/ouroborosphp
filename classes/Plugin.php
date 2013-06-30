<?php
/**
 * Base class for all plugins
 *
 * @author chris nasr
 * @copyright fuel for the fire
 * @version 0.1
 * @package core
 * @created 2011-06-27
 */

/**
 * Include required classes
 */
require_once 'classes/Framework.php';
require_once 'classes/Template.php';

/**
 * Plugin class
 * @name Plugin
 * @package core
 * @abstract
 */
abstract class Plugin
{
	/**
	 * Template instance
	 * @var Template
	 * @access private
	 */
	private $oTemplate;

	/**
	 * Constructor
	 * Initializes the object
	 * @name Plugin
	 * @access public
	 * @return Plugin
	 */
	public function __construct()
	{
		// Instantiate the template class
		$this->oTemplate	= new Template();

		// Set the default source
		$sClass	= get_called_class();
		$this->oTemplate->source("web/plugins/{$sClass}/{$sClass}.tpl");
	}

	/**
	 * Check Arguments
	 * Check if an argument was passed, if not, set it's default or throw an error
	 * @name checkArgument
	 * @access protected
	 * @param array &$in_arguments		Arguments passed to plugin
	 * @param array $in_check			The arguments to check for
	 * @return void
	 */
	protected function checkArguments(array &$in_arguments, array $in_check)
	{
		foreach($in_check as $sArg => $mDefault)
		{
			if(!isset($in_arguments[$sArg]) || empty($in_arguments[$sArg]))
			{
				if(is_null($mDefault))
				{
					trigger_error(
						'Missing argument "' . $sArg .'" to init Plugin "' . get_called_class() . '".',
						E_USER_ERROR
					);
				}

				$in_arguments[$sArg]	= $mDefault;
			}

			if(is_string($in_arguments[$sArg]))
			{
				$in_arguments[$sArg]	= trim($in_arguments[$sArg]);
			}
		}
	}

	/**
	 * Display
	 * Display/Render the plugin
	 * @name display
	 * @access public
	 * @param bool $in_return			If true, return the plugin instead of echoing it
	 * @return void|string
	 */
	public function display($in_return = false)
	{
		if($in_return)
		{
			return $this->oTemplate->fetch();
		}
		else
		{
			echo $this->oTemplate->fetch();
		}
	}

	/**
	 * Init
	 * Initialize the plugin
	 * @name init
	 * @access public
	 * @abstract
	 * @param Template $in_template		The template to
	 * @param array $in_args			The args/options sent to the plugin
	 * @return bool
	 */
	abstract public function init(Template $in_template, array $in_args);

	/**
	 * Load
	 * Create a new instance of a plugin if it's found
	 * @name load
	 * @access public
	 * @static
	 * @param string $in_name			Name of the plugin
	 * @param array $in_args			Arguments to the plugin
	 * @return Plugin
	 */
	public static function load(/*string*/ $in_name, array $in_args)
	{
		// Check if the plugin class has already been loaded
		if(!class_exists($in_name, false))
		{
			// Create the plugin path
			$sFilename	= "web/plugins/{$in_name}/{$in_name}.php";

			// Check the file exists
			if((include($sFilename)) === false)
			{
				return null;
			}
		}

		// Create an instance of the plugin
		$oPlugin	= new $in_name();

		// Check the class is in fact a valid plugin
		if(!($oPlugin instanceof Plugin))
		{
			trigger_error("Unable to load plugin {$in_name}. Not a valid instance of Plugin.", E_USER_WARNING);
			return null;
		}

		// Initialise it
		call_user_func(array($oPlugin, 'init'), $oPlugin->oTemplate, $in_args);

		// Return the new plugin
		return $oPlugin;
	}
}