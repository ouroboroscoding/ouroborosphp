<?php
/**
 * Autoload
 *
 * Attachs the callback that will be called for classes that can't be found
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

	spl_autoload_register(function ($class) {
		if($class[0] == '_') {
			require 'ouroboros/classes/' . substr($class, 1) . '.php';
		}
	});
