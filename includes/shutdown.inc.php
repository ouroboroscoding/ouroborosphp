<?php
/**
 * Shutdown
 *
 * Attachs the callback that will send off any errors that happen during shutdown
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

	/**
	 * Error Code to Strings
	 *
	 * Converts the PHP error codes into human readable strings
	 *
	 * @name _errCodeToString
	 * @param uint $code				The code in PHP
	 * @return string
	 */
	function _errCodeToString(/*uint*/ $code)
	{
		$aCodes = array(1=>'E_ERROR',2=>'E_WARNING',4=>'E_PARSE',8=>'E_NOTICE',16=>'E_CORE_ERROR',32=>'E_CORE_WARNING',64=>'E_COMPILE_ERROR',128=>'E_COMPILE_WARNING',256=>'E_USER_ERROR',512=>'E_USER_WARNING',1024=>'E_USER_NOTICE',2048=>'E_STRICT',4096=>'E_RECOVERABLE_ERROR',8192=>'E_DEPRECATED',16384=>'E_USER_DEPRECATED');
		return $aCodes[$code];
	}

	/**
	 * Shutdown Callback
	 *
	 * Called by PHP when a script is exitting
	 *
	 * @name shutdownCallback
	 * @return void
	 */
	function shutdownCallback()
	{
		// Get the last error
		$aErr	= error_get_last();

		// If it's a valid error
		if(!is_null($aErr) && false !== $aErr)
		{
			$sMessage	= 'Time: ' . date('r') . "\n" .
							'Code: ' . _errCodeToString($aErr['type']) . "\n" .
							'Message: ' . $aErr['message'] . "\n" .
							'Line: ' . $aErr['file'] . ' @ ' . $aErr['line'] . "\n";

			// If we're not in CLI mode add additional info
			if(!_OS::isCLI())
			{
				// Add the URL, referer, session, and IP
				$sMessage  .= 'URL: ' . $_SERVER['REQUEST_URI'] . "\n" .
								'Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A') . "\n" .
								'Session ID: ' . session_id() . "\n" .
								'Client IP: ' . _OS::getClientIP() . "\n";

				// If someone set the global shutdown fields variable
				if(isset($GLOBALS['_gaShutdown']))
				{
					// Add each value by hash name
					foreach($GLOBALS['_gaShutdown'] as $n => $s) {
						$sMessage	.= $n . ': ' . $s . "\n";
					}
				}

				// Add the post variables if we're in POST mode
				if($_SERVER['REQUEST_METHOD'] == 'POST') {
					$sMessage  .= 'POST: ' . json_encode($_POST) . "\n";
				}
			}

			// If we're in CLI mode, or the user is a developer, print the message
			if(OS::isCLI()) {
				echo $sMessage;
			} else if(OS::isDeveloper()) {
				echo '<pre>', $sMessage, '</pre>';
			} else {
				// Notify a developer of an issue
				_OS::notify('shutdown error', $sMessage);
			}
		}
	}

	// Will register shutdownCallback with PHP
	register_shutdown_function('shutdownCallback');