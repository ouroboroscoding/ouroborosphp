<?php
/**
 * Pagination
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Pagination class
 *
 * Handles parsing and displaying pagination
 *
 * @name _Pagination
 */
class _Pagination
{
	/**
	 * Links
	 *
	 * Holds the list of links to be used to generate pagination
	 *
	 * @var array
	 * @access private
	 */
	private $aLinks;

	/**
	 * Holds misc. options
	 *
	 * Optional pagination modifiers
	 * <pre>string 'primary_url'		 Used to override the value for the primary page
	 * uint 'index_limit'			Used to limit the number of pages indexed
	 * </pre>
	 *
	 * @var array
	 * @access private
	 */
	private $aOptions;

	/**
	 * Page
	 *
	 * The current page the user is on
	 *
	 * @var uint
	 * @access private
	 */
	private $iPage;

	/**
	 * To Show
	 *
	 * The number of pages to show in the pagination
	 *
	 * @var uint
	 * @access private
	 */
	private $iToShow;

	/**
	 * Total
	 *
	 * The total number of pages
	 *
	 * @var uint
	 * @access private
	 */
	private $iTotal;

	/**
	 * Primary URL
	 *
	 * The URL to use for page one, can be overriden in the contructor
	 *
	 * @var string
	 * @access private
	 */
	private $sPrimaryURL;

	/**
	 * URL
	 *
	 * Holds the URL that will be used to generate links
	 *
	 * @var string
	 * @access private
	 */
	private $sURL;

	/**
	 * Constructor
	 *
	 * Creates a new instance of the class
	 * <pre>Options:
	 * string 'primary_url'			Used to override the value for the primary page
	 * uint 'index_limit'			Used to limit the number of pages indexed
	 * </pre>
	 *
	 * @name _Pagination
	 * @access public
	 * @param uint $page				The current page we are on
	 * @param uint $pages				The total number of pages
	 * @param uint $toshow				The number of pages to display
	 * @param array $options			'primary_url', 'index_limit'
	 * @param string $primary_url		Used to override the URL for the first page
	 * @return _Pagination
	 */
	public function __construct(/*uint*/ $page, /*uint*/ $total, /*uint*/ $toshow, array $options = array())
	{
		// Validate the page argument
		if(!is_numeric($page) || $page < 1) {
			trigger_error('Invalid $page argument passed to ' . __METHOD__ . ' must be an unsigned int greater than 0. ' . $page . ' passed.', E_USER_ERROR);
		}

		// Validate the total argument
		if(!is_numeric($total) || $total < 1) {
			trigger_error('Invalid $total argument passed to ' . __METHOD__ . ' must be an unsigned int greater than 0. ' . $total . ' passed.', E_USER_ERROR);
		}

		// Validate the toshow argument
		if(!is_numeric($toshow) || $toshow < 1) {
			trigger_error('Invalid $toshow argument passed to ' . __METHOD__ . ' must be an unsigned int greater than 0. ' . $toshow . ' passed.', E_USER_ERROR);
		}

		// Store the values
		$this->iPage		= intval($page);
		$this->iTotal		= intval($total);
		$this->iToShow		= intval($toshow);
		$this->aOptions		= $options;

		// Check for options
		_Array::checkOptions($this->aOptions, array(
			'primary_url'	=> null,
			'index_limit'	=> PHP_INT_MAX
		));

		// Generate the URL
		$this->generateURL();

		// If the primary URL is null
		if(is_null($this->aOptions['primary_url'])) {
			$this->aOptions['primary_url']	= $this->sURL;
		}

		// Generate links
		$this->generateLinks();
	}

	/**
	 * Generate Links
	 *
	 * Generates a list of links and their data based on the current page and
	 * total pages
	 *
	 * @name generateLinks
	 * @access private
	 * @return void
	 */
	private function generateLinks()
	{
		// Additional pages are the total minus the current page
		$iAdditional	= $this->iToShow - 1;

		// Figure out how many additional pages to show
		$iToShow		= ($this->iTotal <= $iAdditional) ? ($this->iTotal - 1) : $iAdditional;

		// Get the pre and post lengths
		if($iToShow % 2 == 0) {
			$iPre	= $iToShow / 2;
			$iPost	= $iPre;
		} else {
			$iPre	= floor($iToShow / 2);
			$iPost	= $iPre + 1;
		}

		// If the current page is less than the pre pages
		if($this->iPage <= $iPre)
		{
			$iPre	= $this->iPage - 1;
			$iPost	= $iToShow - $iPre;
		}
		// Else if the total pages minus the current page is less than the
		//	post pages
		if($this->iTotal - $this->iPage <= $iPost)
		{
			$iPost	= $this->iTotal - $this->iPage;
			$iPre	= $iToShow - $iPost;
		}

		// Init the links array
		$this->aLinks	= array(
			'previous'		=> false,
			'first'			=> false,
			'pre'			=> array(),
			'current'		=> '',
			'post'			=> array(),
			'last'			=> false,
			'next'			=> false
		);

		// If the page isn't 1
		if($this->iPage > 1)
		{
			// Add the previous button
			$this->aLinks['previous'] = array(
				'text'	=> $this->iPage - 1,
				'url'	=> (($this->iPage - 1 == 1) ? $this->aOptions['primary_url'] : $this->sURL . ($this->iPage - 1) . '/') . $this->sQuery,
				'index' => (($this->iPage - 1) > $this->aOptions['index_limit']) ? false : true
			);
		}

		// If the page is greater than the pre length
		if($this->iPage - 1 > $iPre)
		{
			// Add the first page
			$this->aLinks['first'] = array(
				'text'	=> 1,
				'url'	=> $this->aOptions['primary_url'] . $this->sQuery,
				'index' => (1 > $this->aOptions['index_limit']) ? false : true
			);
		}

		// Add the previous pages
		for($i = $this->iPage - $iPre; $i < $this->iPage; ++$i)
		{
			$this->aLinks['pre'][]	= array(
				'text'	=> $i,
				'url'	=> (($i == 1) ? $this->aOptions['primary_url'] : $this->sURL . $i . '/') . $this->sQuery,
				'index' => ($i > $this->aOptions['index_limit']) ? false : true
			);
		}

		// Add the current page
		$this->aLinks['current'] = array(
			'text'	=> $this->iPage,
			'url'	=> (($i == 1) ? $this->aOptions['primary_url'] : $this->sURL . $i . '/') . $this->sQuery,
			'index' => ($this->iPage > $this->aOptions['index_limit']) ? false : true
		);

		// Add the next pages
		$iForCond	= $this->iPage + $iPost + 1;
		for($i = $this->iPage + 1; $i < $iForCond; ++$i)
		{
			$this->aLinks['post'][] = array(
				'text'	=> $i,
				'url'	=> (($i == 1) ? $this->aOptions['primary_url'] : $this->sURL . $i . '/') . $this->sQuery,
				'index' => ($i > $this->aOptions['index_limit']) ? false : true
			);
		}

		// If the last page isn't visible
		if($this->iTotal > $this->iPage + $iPost)
		{
			// And the last page
			$this->aLinks['last']	= array(
				'text'	=> $this->iTotal,
				'url'	=> $this->sURL . $this->iTotal . '/' . $this->sQuery,
				'index' => ($this->iTotal > $this->aOptions['index_limit']) ? false : true
			);
		}

		// If the current page isn't the last page
		if($this->iTotal != $this->iPage)
		{
			// Show the next page
			$this->aLinks['next']	= array(
				'text'	=> $this->iPage + 1,
				'url'	=> $this->sURL . ($this->iPage + 1) . '/' . $this->sQuery,
				'index' => (($this->iPage + 1) > $this->aOptions['index_limit']) ? false : true
			);
		}
	}

	/**
	 * Generate URL
	 *
	 * Generates the string used for creating links to each page
	 *
	 * @name generateURL
	 * @access private
	 * @return void
	 */
	private function generateURL()
	{
		// Is there's a query string, strip it from the URI
		$sURI	= ($iPos = strpos($_SERVER['REQUEST_URI'], '?')) ?
					substr($_SERVER['REQUEST_URI'], 0, $iPos) :
					$_SERVER['REQUEST_URI'];

		// Is there's a ampersand string, strip it from the URI
		$sURI	= ($i = strpos($sURI, '&')) ?
					substr($sURI, 0, $i) :
					$sURI;

		// Is there's a hash string, strip it from the URI
		$sURI	= ($i = strpos($sURI, '#')) ?
					substr($sURI, 0, $i) :
					$sURI;

		// If we're on page 1
		if($this->iPage == 1)
		{
			$this->sURL		= $sURI;
		}
		// Else we need to pull off the current page
		else
		{
			// Split the URI by / after trimming them off the front and end
			$aURI	= explode('/', trim($sURI, '/'));

			// If the last part doesn't match the current page
			$iPage	= array_pop($aURI);
			if($iPage != $this->iPage) {
				trigger_error('Invalid page passed to Pagination. ' . $iPage . ' / ' . $this->iPage, E_USER_ERROR);
			}

			// If there is no other parts
			if(count($aURI) == 0) {
				$this->sURL = '/';
			} else {
				$this->sURL = '/' . implode('/', $aURI) . '/';
			}
		}

		// Set the query string
		$this->sQuery	= ($iPos) ? substr($_SERVER['REQUEST_URI'], $iPos) : '';
	}


	/**
	 * Ouput
	 *
	 * Returns the formatted pagination
	 *
	 * @name output
	 * @access public
	 * @return string
	 */
	public function getLinks()
	{
		return $this->aLinks;
	}
}
