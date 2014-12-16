<?php
/**
 * Controller
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Required classes
 */
require_once 'ouroboros/classes/View.php';

/**
 * Controller Class
 *
 * Base class for all controllers in the system
 *
 * @name _Controller
 * @abstract
 */
abstract class _Controller
{
	/**
	 * View
	 *
	 * An instance of View that controllers can use
	 *
	 * @var _View
	 * @access protected
	 */
	protected $oView;

	/**
	 * Constructor
	 *
	 * Initialises the Controller instance
	 *
	 * @name Controller
	 * @access public
	 * @param _View $view				The view associated with the controller
	 * @return Controller
	 */
	public function __construct(_View $view)
	{
		$this->oView	= $view;
	}

	/**
	 * AJAX Response
	 *
	 * Prints out a standardised format for AJAX messages
	 *
	 * @name ajaxResponse
	 * @access public
	 * @static
	 * @param mixed $data				The data to send back
	 * @param bool $error				If the data is an error message
	 * @return false					Returns false so ::load can return it
	 */
	public static function ajaxResponse(/*mixed*/ $data, /*bool*/ $error = false)
	{
		// Set the proper header
		header('Content-Type: application/json; charset=utf-8');

		// Init the return response
		$aJSON	= array(
			'error' => $error,
			'data'	=> $data
		);

		// Encode it and echo it
		echo json_encode($aJSON);

		// Return false for ::load
		return false;
	}

	/**
	 * Load
	 *
	 * Must be implemented by all classes that extend Controller. Should return
	 * false if no action should be taken after it's called. e.g. displaying a
	 * view of some sort.
	 *
	 * @name load
	 * @access public
	 * @abstract
	 * @return bool
	 */
	abstract public function load();

	/**
	 * Redirect
	 *
	 * Redirects to another URL in the system
	 *
	 * @name redirect
	 * @access public
	 * @static
	 * @param string $url				The URL to redirect to
	 * @param uint $code				The HTTP code to return
	 * @return false					Returns false so ::load can return it
	 */
	public static function redirect(/*string*/ $url, /*uint*/ $code = 302)
	{
		header('x', true, $code);
		header('Location: ' . $url);
		return false;
	}

	/**
	 * Return 404
	 *
	 * Modifies the headers and returns 404 Not Found
	 *
	 * @name return404
	 * @access public
	 * @param string $template			The template used to display the 404
	 * @param array $args				Additional arguments to the template
	 * @return void
	 */
	public static function return404(/*string*/ $template, array $vars = array())
	{
		// Set a 404 in the header and added the message
		header('x', true, 404);
		$oView	= new _View($template);
		$oView->assign('requested_url', $_SERVER['REQUEST_URI']);
		foreach($vars as $n => $v) {
			$oView->assign($n, $v);
		}
		$oView->display();
		exit(1);
	}
}

