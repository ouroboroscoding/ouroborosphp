<?php
/**
 * Setup
 * Sets up the paths and autoloads for the project
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

	// Get the current directory of this file
	$gsOuroborosPath	= dirname(__FILE__);

	// Set the root path of the application as the working directory
	chdir(realpath($gsOuroborosPath . '/..'));

	// Pull off just the subfolder
	$gsOuroborosPath	= substr($gsOuroborosPath, strrpos($gsOuroborosPath, '/')+1);

	// Track errors at shutdown
	require $gsOuroborosPath . '/includes/shutdown.inc.php';

	// Create autoload
	require $gsOuroborosPath . '/includes/autoload.inc.php';

	// Load config
	_Config::init(include 'config.inc.php');

	// Before anything else, turn on all errors, warnings, and notices, nothing
	//	unknown or unexpected should ever be happening
	if(_OS::isCLI() || _OS::isDeveloper()) {
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}

	// If we're not in CLI mode turn on output buffering
	if(!_OS::isCLI())
	{
		// If the user isn't a developer strip empty space between HTML/XML
		//	elements as well as any line feeds, tabs, and HTML comments
		if(!_OS::isDeveloper()) {
			ob_start(function($text) {
				return trim(preg_replace('/>\s+</', '><', preg_replace('/[ \r\t]+/', ' ', preg_replace('/<!--.*?-->/s', '', $text))));
			});
		} else {
			ob_start();
		}
	}
	// Else turn arguments into _REQUEST variables to simplify code
	else
	{
		_OS::convertArgvToRequest();
	}

	// If caching is on, and there's a request to reset the cache
	if(_Config::get('caching:enabled')	&&
		isset($_REQUEST['resetCache'])	&&
		$_REQUEST['resetCache'] == 'ehcaCteser')
	{
		// Set the config to 'reset'
		_Config::set('caching:enabled', 'reset');
	}

	// If we're in CLI mode or the user is a developer, and MySQL logging is on
	if((_OS::isCLI() || _OS::isDeveloper()) && isset($_REQUEST['mysqllog'])) {
		_MySQL::logOn();
	}

	// Set the timezone
	date_default_timezone_set(_Config::get('timezone'));
