<?php
/**
 * Sample Index.php
 *
 * This script is used to give you an example of how you can quickly and easily
 * use the classes in this project to get a webserver up quickly. The main entry
 * point of your web application should never be in the same place as the
 * OuroborosPHP files. The OuroborosPHP files should be in their own folder which
 * is directly in the root of the application. Things will break if it is not.
 * For this example I assume a structure like so, please adapt as is necessary.
 *
 * app
 *   hosts
 *     www
 *       index.php
 *     origin
 *       index.php
 *   interfaces
 *     desktop
 *       controllers
 *       views
 *     mobile
 *       controllers
 *       views
 *     tablet
 *       controllers
 *       views
 *   ouroborosphp
 *     classes
 *     includes
 *     etc.
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2015-02-19
 */

	// Set content type
	header('Content-Type: text/html; charset=utf-8');

	/*********************************
	 * Weird browsers
	 *********************************/
	// If by some weird reason there's no user agent, default to Samsung Tablet
	if(!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT'])) {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-gb; GT-P1000 Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
	}

	// Or if there's no HTTP_HOST..... HOW?
	if(!isset($_SERVER['HTTP_HOST'])) {
		$_SERVER['HTTP_HOST']	= 'somedomain.com';
	}

	/*********************************
	 * Start the session and setup
	 ********************************/
	// If the session is set but invalid
	if(isset($_COOKIE['PHPSESSID']) && !preg_match('/^[-A-Za-z0-9,]+$/', $_COOKIE['PHPSESSID']))
	{
		// Delete it
		unset($_COOKIE['PHPSESSID']);
	}

	// Start the session
	session_start();

	// Include the OuroborosPHP setup
	// Notice that this is the only include with '../' in it. Calling the
	//	OuroborosPHP setup will change your current working directory to the
	//	root of your app. This makes it far easier to develop multiple scripts
	//	and applications without having to worry which folder  you're in.
	require dirname(__FILE__) . '../ouroborosphp/setup.inc.php';

	/*********************************
	 * Figure out the interface based on the device
	 ********************************/
	// Global app type
	$gsInterface	= null;

	// If there's a session
	if(isset($_SESSION['_interface']))
	{
		// Decode it
		$aJSON	= json_decode($_SESSION['_interface'], true);

		// If user agent's match
		if($aJSON['agent'] == md5($_SERVER['HTTP_USER_AGENT']))
		{
			$gsInterface	= $aJSON['type'];
		}
	}
	// If there's a request
	if(isset($_REQUEST['_interface']))
	{
		// Get it
		$gsInterface			= $_REQUEST['_interface'];

		// And store it in the session
		$_SESSION['_interface']	= json_encode(array(
			'type'	=> $gsInterface,
			'agent' => md5($_SERVER['HTTP_USER_AGENT'])
		));
	}
	// If we don't have a type
	if(is_null($gsInterface))
	{
		// Load the mobile detect class
		require_once 'ouroborosphp/classes/3rdparty/Mobile_Detect.php';
		$oMobileDetect	= new Mobile_Detect();

		// If it's a mobile or tablet device set the app appropriately, else
		//	just default to the desktop version
		if($oMobileDetect->isTablet()) {
			$gsInterface	= 'tablet';
		} else if($oMobileDetect->isMobile()) {
			$gsInterface	= 'mobile';
		} else {
			$gsInterface	= 'desktop';
		}

		// Get rid of the mobile detect instance
		unset($oMobileDetect);

		// Store it in the session
		$_SESSION['_interface']	= json_encode(array(
			'type'	=> $gsInterface,
			'agent' => md5($_SERVER['HTTP_USER_AGENT'])
		));
	}

	// Add the interface to the global shutdown info
	$_gaShutdown['Interface']	= $gsInterface;

	/*********************************
	 * Load the controller and default view
	 ********************************/
	// Split the URL into parts
	$aURL	= (isset($_REQUEST['url']) && $_REQUEST['url'] != '/') ?
				explode('/', trim($_REQUEST['url'], '/')) :
				array();

	// If there's no URL, default to the homepage
	if(empty($aURL))
	{
		// Set the controller to static
		$gsController	= 'main';
		$aURL			= array();
	}
	// Else, see if the URL matches a controller
	else
	{
		// Shift off the controller
		$gsController	= array_shift($aURL);

		// Load the list of available controllers
		$gaValidControllers	= require 'hosts/www/controllers.inc.php';

		// If there are any dashes convert them to underscores
		//	php doesn't allow dashes in class names
		if(strpos($gsController, '-') !== false) {
			$gsController	= str_replace('-', '_', $gsController);
		}

		// If the controller doesn't exist
		if(!in_array($gsController, $gaValidControllers)) {
			array_unshift($aURL, $gsController);
			$gsController	= 'main';
		}

 		// Alternatively you could check directly, it's worse for the harddrive
		//	but easier on the coder.
		//	You would skip all the above code except shifting off the controller.
		//	However you will also need to do more after the else. Check the
		//	below ALTERNATE CONTROLLER CHECKING
	}

	// Generate the path to the controller
	$sCtrlPath	= 'interfaces/' . $gsInterface . '/controllers/' . $gsController . '.php';

	/**
	 * ALTERNATE CONTROLLER CHECKING
	 */
/*	if(!file_exists($sCtrlPath)) {
		$gsController	= 'main';	// or 404?
		$sCtrlPath		= 'interfaces/' . $gsInterface . '/controllers/main.php'; // or 404.php?
	}
*/

	// Add the controller to the global shutdown info
	$_gaShutdown['Controller']	= $gsController;

	// Wrap the primary code in a try/catch block so we can be notified of any
	//	and all errors
	try
	{
		// Load the controller into memory
		require $sCtrlPath;

		// Generate the class name
		$sCtrlClass = $gsController . '_controller';

		// Create a new View with the default template and add standard vars
		$oView	= new _View('interfaces/www/views/' . $gsInterface . '/layout.tpl');

		// Create a new instance of the controller
		$oCtrl	= new $sCtrlClass($oView);

		// Set the data for the controller
		$oCtrl->data(array(
			'controller'	=> $gsController,
			'view'			=> $gsController,
			'interface'		=> $gsInterface,
			'member'		=> $goMember,
			'meta'			=> array(),
			'gui'			=> isset($_REQUEST['gui']) ? $_REQUEST['gui'] : 'basic'
		));

		// And load it by passing the arguments to it after removing the controller name
		$bRet	= call_user_func_array(array($oCtrl, 'load'), $aURL);

		// If the controller didn't return false, render the View
		if(false !== $bRet)
		{
			// Display the main template
			$oView->display();
		}
	}
	catch(Exception $e)
	{
		// If it's a developer rethrow the exception
		if(_OS::isDeveloper()) {
			throw $e;
		} else {
			$sMessage	= 'Time: ' . date('r') . "\n" .
						  'Code: ' . $e->getCode() . "\n" .
						  'Message: ' . $e->getMessage() . "\n" .
						  'Line: ' . $e->getFile() . ' @ ' . $e->getLine() . "\n" .
						  'URL: ' . $_SERVER['REQUEST_URI'] . "\n" .
						  'Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A') . "\n" .
						  'Session ID: ' . session_id() . "\n" .
						  'Client IP: ' . _OS::getClientIP() . "\n";

			// Add special variables
			foreach($_gaShutdown as $n => $s) {
				$sMessage	.= "{$n}: {$s}\n";
			}

			// If we're in POST mode
			if($_SERVER['REQUEST_METHOD'] == 'POST') {
				$sMessage  .= 'POST: ' . json_encode($_POST) . "\n";
			}

			// Notify a developer
			_OS::notify('exception', $sMessage);

			// Notify the client
			header('x', true, 500);
			readfile('hosts/www/500.html');
		}
	}