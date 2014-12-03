<?php
/**
 * Request
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Request class
 *
 * Class used to request URLs, simplifies the use of cURL
 *
 * @name _Request
 */
class _Request
{
	/**
	 * Last Request
	 *
	 * The returned data from the last request
	 *
	 * @var array
	 * @access private
	 */
	private $aLastRequest;

	/**
	 * Count
	 *
	 * Holds the number of requests that have been called using this instance
	 *
	 * @var unsigned int
	 * @access private
	 */
	private $iCount;

	/**
	 * CURL
	 *
	 * Resource returned from curl_init
	 *
	 * @var resource
	 * @access private
	 */
	private $rCURL;

	/**
	 * Constructor
	 *
	 * Initialise the instance
	 *
	 * @name _Request
	 * @access public
	 * @return _Request
	 */
	public function __construct()
	{
		// Init the count
		$this->iCount	= 0;

		// Init curl
		$this->rCURL	= curl_init();

		// Get the current version of curl
		$aVer			= curl_version();

		// Set curl options
		curl_setopt_array($this->rCURL, array(
			CURLOPT_CONNECTTIMEOUT	=> 15,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_TIMEOUT			=> 30,
			CURLOPT_USERAGENT		=> 'CURL/' . $aVer['version']
		));
	}

	/**
	 * Destructor
	 *
	 * Destroys the instance
	 *
	 * @name ~Request
	 * @access public
	 * @return void
	 */
	public function __destruct()
	{
		curl_close($this->rCURL);
	}

	/**
	 * Go
	 *
	 * Fetches and returns the URL given
	 * <pre>Optional arguments:
	 * post		  => Array or string of POST fields
	 * headers	  => Array of headers
	 * </pre>
	 *
	 * @name fetch
	 * @access public
	 * @param string $url				The URL to fetch
	 * @param array $options			Optional arguments
	 * @return array					'content', 'content_type', 'size', 'status_code'
	 */
	public function go(/*string*/ $url, array $options = array())
	{
		// Check the options
		_Array::checkOptions($options, array(
			'post'		=> false,
			'headers'	=> false
		));

		// Reset the return array
		$this->aLastRequest = array();

		// Every fifty requests, close and reset the cURL instance, it can eat
		//	up memory and eventually fail on some systems if you don't
		if(++$this->iCount == 50) {
			curl_close($this->rCURL);
			$this->__construct();
		}

		// Set the URL
		curl_setopt($this->rCURL, CURLOPT_URL, $url);

		// If we're doing a post request
		if($options['post'])
		{
			curl_setopt_array($this->rCURL, array(
				CURLOPT_POST			=> true,
				CURLOPT_POSTFIELDS		=> (is_array($options['post']) || is_string($options['post'])) ? $options['post'] : ''
			));
		}
		else
		{
			curl_setopt_array($this->rCURL, array(
				CURLOPT_HTTPGET			=> true,
				CURLOPT_POSTFIELDS		=> ''
			));
		}

		// If there are custom headers
		if($options['headers']) {
			curl_setopt($this->rCURL, CURLOPT_HTTPHEADER, $options['headers']);
		} else {
			curl_setopt($this->rCURL, CURLOPT_HTTPHEADER, array());
		}

		// Download the content
		$this->aLastRequest['content']	= curl_exec($this->rCURL);

		// Get info
		$this->aLastRequest['content_type'] = curl_getinfo($this->rCURL, CURLINFO_CONTENT_TYPE);
		$this->aLastRequest['http_code']	= curl_getinfo($this->rCURL, CURLINFO_HTTP_CODE);
		$this->aLastRequest['size']			= curl_getinfo($this->rCURL, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		if($this->aLastRequest['size'] == -1) {
			$this->aLastRequest['size'] = strlen($this->aLastRequest['content']);
		}

		// Return true only if we received 200 and at least one byte of content
		return ($this->aLastRequest['http_code'] == 200 &&
				$this->aLastRequest['size'] > 0) ?
				true : false;
	}

	/**
	 * Result
	 *
	 * Returns the result of the last request via an array with the content,
	 * content_type, http_code, and size elements
	 *
	 * @name result
	 * @access public
	 * @return array|null				Returns null if no request has been made
	 */
	public function result()
	{
		return (isset($this->aLastRequest)) ?
				$this->aLastRequest :
				null;
	}
}