<?php
/**
 * Load
 * This include file will setup the necessary defines and paths to use OuroborosPHP
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @package load
 * @version 0.1
 * @created 2013-04-11
 */

	// First, get the base path for the Ouroboros files
	$__sBasePath	= dirname(__FILE__);

	// Load the core class
	require $__sBasePath . '/Ouroboros.php';

	// Init Ouroboros
	_O::init($__sBasePath);
	unset($__sBasePath);
