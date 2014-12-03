<?php
/**
 * Google Sitemaps
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * Google Sitemaps class
 *
 * Class that handles building every sort of Google Sitemap
 *
 * @name _GoogleSitemaps
 */
class _GoogleSitemaps
{
	/**#@+
	 * Class constants
	 */
	const MAX_ITEMS		= 50000;
	const MAX_SIZE		= 52428800; // 50mb
	const TYPE_IMAGE	= 0x01;
	const TYPE_MOBILE	= 0x02;
	const TYPE_NEWS		= 0x04;
	const TYPE_VIDEO	= 0x08;
	const XML_HEAD		= '<?xml version="1.0" encoding="UTF-8"?>';
	const URLSET		= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"|NAMESPACES|>|CONTENT|</urlset>';
	const SITEMAPINDEX	= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">|CONTENT|</sitemapindex>';
	const TOKEN_NS		= '|NAMESPACES|';
	const TOKEN_CON		= '|CONTENT|';
	const NS_IMAGE		= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
	const NS_VIDEO		= 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"';
	const NS_MOBILE		= 'xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"';
	const NS_NEWS		= 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"';
	/**#@-*/

	/**
	 * Current Sitemap
	 *
	 * Holds the data for the current sitemap
	 *
	 * @var array
	 * @access private
	 */
	private $aCurrSitemap;

	/**
	 * Paths
	 *
	 * Holds the root http and local paths for the sitemaps
	 *
	 * @var access
	 * @access private
	 */
	private $aPaths;

	/**
	 * Sitemaps
	 *
	 * Holds the names of the sitemaps that have been built. Used to make the index
	 *
	 * @var array
	 * @access private
	 */
	private $aSitemaps;

	/**
	 * Gzip
	 *
	 * Flag to know wether to gzip the sitemaps or not
	 *
	 * @var bool
	 * @access private
	 */
	private $bGzip;

	/**
	 * Mobile
	 *
	 * Flag to know whether these will be mobile sitemaps or not
	 *
	 * @var bool
	 * @access private
	 */
	private $bMobile;

	/**
	 * New Lines
	 *
	 * Flag for adding new lines to each item, helps with debugging
	 *
	 * @var bool
	 * @access private
	 */
	private $bNewLines;

	/**
	 * Constructor
	 *
	 * Initialise the instance of _GoogleSitemaps
	 *
	 * @name _GoogleSitemaps
	 * @access public
	 * @param string $http_path			The root http path for all sitemaps
	 * @param string $local_path		The root local path for all sitemaps
	 * @param array $options			gzip => bool, mobile => bool, newlines => true
	 * @return _GoogleSitemaps
	 */
	public function __construct(/*string*/ $http_path, /*string*/ $local_path, array $options = array())
	{
		// Set the current sitemap to null
		$this->aCurrSitemap = null;

		// Store the paths
		$this->aPaths		= array(
			'http'	=> rtrim($http_path, '/'),
			'local' => rtrim($local_path, DIRECTORY_SEPARATOR)
		);

		// Init the sitemaps array
		$this->aSitemaps	= array();

		// Check the options
		$this->bGzip		= isset($options['gzip']) ? (bool)$options['gzip'] : false;
		$this->bMobile		= isset($options['mobile']) ? (bool)$options['mobile'] : false;
		$this->bNewLines	= isset($options['newlines']) ? (bool)$options['newlines'] : false;
	}

	/**
	 * Add Image
	 *
	 * Add an image item to the current sitemap
	 *
	 * @name addImage
	 * @access public
	 * @param string $location			Full URL to the source page
	 * @param string|array $image		One or more images found on the source page
	 * @return void
	 */
	public function addImage(/*string*/ $location, /*string|array*/ $image)
	{
		// Init the string holding the item
		$sItem	= '';

		// Add the image type to the list
		$this->insertNamespace(self::TYPE_IMAGE);

		// Add the location
		$sItem	.= '<loc>' . $this->escape($location) . '</loc>';

		// If there's only one image
		if(!is_array($image)) {
			$image	= array($image);
		}

		// Add each image
		foreach($image as $sSrc) {
			$sItem	.= '<image:image>' . $this->escape($sSrc) . '</image:image>';
		}

		// Try to add the element
		$this->insertElement($sItem);
	}

	/**
	 * Add Item
	 *
	 * Add an item to the current sitemap
	 *
	 * @name addItem
	 * @access public
	 * @param array $item				'loc', 'lastmod', 'changefreq', 'priority'
	 * @return bool
	 */
	public function addItem(array $item)
	{
		// Init the string holding the item
		$sItem	= '';

		// Go through each item element and add it if it's valid
		foreach($item as $sElement => $mValue)
		{
			switch($sElement)
			{
				case 'loc':
					$sItem	.= '<loc>' . $this->escape($mValue) . '</loc>';
					break;
				case 'lastmod':
					// If the value is a timestamp, convert it
					if(is_numeric($mValue) && $mValue > 0) {
						$mValue = date('c', $mValue);
					}
					$sItem	.= '<lastmod>' . $mValue . '</lastmod>';
					break;
				case 'changefreq':
					// If the value isn't valid, set it to 'never'
					if(!in_array($mValue, array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'))) {
						$mValue = 'never';
					}
					$sItem	.= '<changefreq>' . $mValue . '</changefreq>';
					break;
				case 'priority':
					// If the value isn't numeric, set it to 0.1
					if(!is_numeric($mValue)) {
						$mValue = 0.1;
					} else {
						$mValue = floatval($mValue);
					}

					// If the value is too high or too low
					if($mValue > 1.0) {
						$mValue = '1.0';
					} else if($mValue < 0) {
						$mValue = '0.1';
					}

					$sItem	.= '<priority>' . $mValue . '</priority>';
					break;
			}
		}

		// Try to add the element
		$this->insertElement($sItem);
	}

	/**
	 * Add News
	 *
	 * Add a news item to the current sitemap
	 *
	 * @name addNews
	 * @access public
	 * @param string $location			Full URL to the source page
	 * @param array $news				Array of news values
	 * @return void
	 */
	public function addNews(/*string*/ $location, array $news)
	{
		// Init the string holding the item
		$sItem	= '';

		// Add the image type to the list
		$this->insertNamespace(self::TYPE_NEWS);

		// Add the location
		$sItem	.= '<loc>' . $this->escape($location) . '</loc>';

		// Start the news element
		$sItem	.= '<news:news>';

		// Go through each item in the news array
		foreach($news as $sElement => $mValue)
		{
			switch($sElement)
			{
				case 'publication':
					$sItem	.= '<news:publication>' .
								'<news:name><![CDATA[' . $this->escape($mValue['name']) . ']]></news:name>' .
								'<news:language>' . $mValue['language'] . '</news:language>' .
								'</news:publication>';
					break;

				case 'access':
					if(in_array($mValue, array('Subscription', 'Registration'))) {
						$sItem	.= '<news:access>' . $mValue . '</news:access>';
					}
					break;

				case 'genres':
					if(!is_array($mValue)) {
						$mValue = array($mValue);
					}

					// Remove values that are invalid
					foreach($mValue as $i => $sValue) {
						if(!in_array($sValue, array('PressRelease', 'Satire', 'Blog', 'OpEd', 'Opinion', 'UserGenerated'))) {
							unset($mValues[$i]);
						}
					}

					// If there are valid values
					if(count($mValues)) {
						$sItem	.= '<news:genres>' . implode(', ', $mValues) . '</news:genres>';
					}
					break;

				case 'publication_date':
					// If the value is a timestamp, convert it
					if(is_numeric($mValue) && $mValue > 0) {
						$mValue = date('c', $mValue);
					}
					$sItem	.= '<news:publication_date>' . $mValue . '</news:publication_date>';
					break;

				case 'title':
					$sItem	.= '<news:title><![CDATA[' . $this->escape($mValue) . ']]></news:title>';
					break;

				case 'keywords':
					if(!is_array($mValue)) {
						$mValue = array($mValue);
					}

					$sItem	.= '<news:keywords><![CDATA[' . $this->escape(implode(', ', $mValues)) . ']]></news:keywords>';
					break;

				case 'stock_tickers':
					if(!is_array($mValue)) {
						$mValue = array($mValue);
					}

					$sItem	.= '<news:stock_tickers><![CDATA[' . $this->escape(implode(', ', $mValues)) . ']]></news:stock_tickers>';
					break;
			}
		}

		// End news element
		$sItem	.= '</news:news>';

		// Try to add the element
		$this->insertElement($sItem);
	}

	/**
	 * Add Video
	 *
	 * Add a video item to the current sitemap
	 *
	 * @name addVideo
	 * @access public
	 * @param string $location			Full URL to the source page
	 * @param array $video				Array of video values
	 * @return void
	 */
	public function addVideo(/*string*/ $location, array $video)
	{
		// Init the string holding the item
		$sItem	= '';

		// Add the video type to the list
		$this->insertNamespace(self::TYPE_VIDEO);

		// Add the location
		$sItem	.= '<loc>' . $this->escape($location) . '</loc>';

		// Start the video element
		$sItem	.= '<video:video>';

		// Go through each item in the video array
		foreach($video as $sElement => $mValue)
		{
			switch($sElement)
			{
				case 'thumbnail_loc':
					$sItem	.= '<video:thumbnail_loc>' . $this->escape($mValue) . '</video:thumbnail_loc>';
					break;

				case 'title':
					$sItem	.= '<video:title><![CDATA[' . $this->escape($mValue) . ']]></video:title>';
					break;

				case 'description':
					$sItem	.= '<video:description><![CDATA[' . $this->escape($mValue) . ']]></video:description>';
					break;

				case 'content_loc':
					$sItem	.= '<video:content_loc>' . $this->escape($mValue) . '</video:content_loc>';
					break;

				case 'player_loc':
					if(!is_array($mValue)) {
						$mValue = array('loc' => $mValue, 'allow_embed' => false);
					}

					$sItem	.= '<video:player_loc';
					if(isset($mValue['allow_embed'])) {
						if(is_bool($mValue['allow_embed'])) {
							$mValue['allow_embed']	= $mValue['allow_embed'] ? 'yes':'no';
						}

						if(in_array($mValue['allow_embed'], array('yes', 'no'))) {
							$sItem	.= ' allow_embed="' . ($mValue['allow_embed'] ? 'yes' : 'no') . '"';
						}
					}
					if(isset($mValue['autoplay'])) {
						$sItem	.= ' autoplay="' . $mValue['autoplay'] . '"';
					}
					$sItem	.= '>' . $this->escape($mValue['loc']) . '</video:player_loc>';
					break;

				case 'duration':
					if(is_numeric($mValue)) {
						$sItem	.= '<video:duration>' . intval($mValue) . '</video:duration>';
					}
					break;

				case 'expiration_date':
					// If the value is a timestamp, convert it
					if(is_numeric($mValue) && $mValue > 0) {
						$mValue = date('c', $mValue);
					}
					$sItem	.= '<video:expiration_date>' . $mValue . '</video:expiration_date>';
					break;

				case 'rating':
					if(is_numeric($mValue))
					{
						$mValue = floatval($mValue);

						if($mValue > 5.0) {
							$mValue = '5.0';
						} else if($mValue < 0) {
							$mValue = '0.0';
						}

						$sItem	.= '<video:rating>' . $mValue . '</video:rating>';
					}
					break;

				case 'view_count':
					if(is_numeric($mValue)) {
						$sItem	.= '<video:view_count>' . intval($mValue) . '</video:view_count>';
					}
					break;

				case 'publication_date':
					// If the value is a timestamp, convert it
					if(is_numeric($mValue) && $mValue > 0) {
						$mValue = date('c', $mValue);
					}
					$sItem	.= '<video:publication_date>' . $mValue . '</video:publication_date>';
					break;

				case 'family_friendly':
						if(is_bool($mValue)) {
							$mValue = $mValue ? 'yes':'no';
						}

						if(in_array($mValue, array('yes', 'no'))) {
							$sItem	.= '<video:family_friendly>' . $mValue . '</video:family_friendly>';
						}
					break;

				case 'tag':
					if(is_array($mValue)) {
						if(count($mValue) > 32) {
							$mValue = array_slice($mValue, 0, 32);
						}
					} else {
						$mValue = array($mValue);
					}

					foreach($mValue as $sTag) {
						$sItem	.= '<video:tag><![CDATA[' . $this->escape($sTag) . ']]></video:tag>';
					}

					break;

				case 'category':
					$sItem	.= '<video:category><![CDATA[' . $this->escape($mValue) . ']]></video:category>';
					break;

				case 'restriction':
					if(!is_array($mValue)) {
						$mValue = explode(',', $mValue);
					}

					foreach($mValue as $i => $sISO) {
						$sISO	= trim($sISO);
						if(strlen($sISO) > 2) {
							unset($mValue[$i]);
						} else {
							$mValue[$i] = mb_strtoupper($sISO);
						}
					}

					if(count($mValue)) {
						$sItem	.= '<video:restriction relationship="deny">' . implode(', ', $mValue) . '</video:restriction>';
					}
					break;

				case 'gallery_loc':
					$sItem	.= '<video:gallery_loc>' . $this->escape($mValue) . '</video:gallery_loc>';
					break;

				case 'price':
					if(!is_array($mValue)) {
						$mValue = array('price' => $mValue, 'currency' => 'USD');
					}
					$mValue['price']	= money_format('%i', $mValue['price']);
					$sItem	.= '<video:price currency="' . $mValue['currency'] . '">' . $mValue['price'] . '</video:currency>';
					break;

				case 'requires_subscription':
					if(is_bool($mValue)) {
						$mValue = $mValue ? 'yes':'no';
					}

					if(in_array($mValue, array('yes', 'no'))) {
						$sItem	.= '<video:requires_subscription>' . $mValue . '</video:requires_subscription>';
					}
					break;

				case 'uploader':
					if(!is_array($mValue)) {
						$mValue = array('uploader' => $mValue);
					}
					$sItem	.= '<video:uploader' . (isset($mValue['url']) ? (' info="' . $this->escape($mValue['url']) . '">') : '>') . '<![CDATA[' . $this->escape($mValue['uploader']) . ']]></video:uploader>';
					break;

				case 'platform':
					if(!is_array($mValue)) {
						if(strpos($mValue, ',') !== false) {
							$mValue = explode(',', $mValue);
						} else {
							$mValue = explode(' ', $mValue);
						}
					}

					foreach($mValue as $i => $sPlatform) {
						$sPlatform	= trim($sPlatform);
						if(!in_array($sPlatform, array('web', 'mobile', 'tv'))) {
							unset($mValue[$i]);
						}
					}

					if(count($mValue)) {
						$sItem	.= '<video:restriction relationship="deny">' . implode(' ', $mValue) . '</video:restriction>';
					}
					break;

				case 'live':
					if(is_bool($mValue)) {
						$mValue = $mValue ? 'yes':'no';
					}

					if(in_array($mValue, array('yes', 'no'))) {
						$sItem	.= '<video:live>' . $mValue . '</video:live>';
					}
					break;
			}
		}

		// End video element
		$sItem	.= '</video:video>';

		// Try to add the element
		$this->insertElement($sItem);
	}

	/**
	 * Close Sitemap
	 *
	 * Generates and stores the current sitemap then clears the variable
	 *
	 * @name closeSitemap
	 * @access public
	 * @return void
	 */
	public function closeSitemap()
	{
		// If there's no sitemap, do nothing
		if(is_null($this->aCurrSitemap)) {
			return;
		}

		// If there's no items in the sitemap, just close it
		if(count($this->aCurrSitemap['items']) == 0) {
			$this->aCurrSitemap = null;
			return;
		}

		// Generate a new temporary filename
		$sTmpFile	= tempnam(sys_get_temp_dir(), 'SM');

		// If gzipping is on
		if($this->bGzip) {
			// Open a gzip file for writing
			$fp = gzopen($sTmpFile, 'w9');
		} else {
			// Open the file for writing
			$fp = fopen($sTmpFile, 'w');
		}

		// If we can't open the temp file
		if(false === $fp) {
			trigger_error('Unable to open "' . $sTmpFile . '" for writing.', E_USER_ERROR);
		}

		// Write the XML header to it
		if($this->bGzip)	gzwrite($fp, self::XML_HEAD);
		else				fwrite($fp, self::XML_HEAD);

		// Split the urlset by content token
		list($sStart, $sEnd)	= explode(self::TOKEN_CON, self::URLSET);

		// Check for namespace
		$sNamespaces	= '';
		if($this->aCurrSitemap['head']['types'] & self::TYPE_IMAGE) {
			$sNamespaces	.= ' ' . self::NS_IMAGE;
		}
		if($this->aCurrSitemap['head']['types'] & self::TYPE_MOBILE) {
			$sNamespaces	.= ' ' . self::NS_MOBILE;
		}
		if($this->aCurrSitemap['head']['types'] & self::TYPE_NEWS) {
			$sNamespaces	.= ' ' . self::NS_NEWS;
		}
		if($this->aCurrSitemap['head']['types'] & self::TYPE_VIDEO) {
			$sNamespaces	.= ' ' . self::NS_VIDEO;
		}

		// Replace the namespace token with the namespaces
		$sStart = str_replace(self::TOKEN_NS, $sNamespaces, $sStart);

		// And write it them to the file
		if($this->bGzip)	gzwrite($fp, $sStart);
		else				fwrite($fp, $sStart);


		// Cleanup
		unset($sStart, $sNamespaces);

		// Go through each item and write it to the file
		foreach($this->aCurrSitemap['items'] as $sURL) {
			if($this->bNewLines)	$sURL .= "\n";
			if($this->bGzip)	gzwrite($fp, $sURL);
			else				fwrite($fp, $sURL);
		}

		// Write the end of the urlset
		if($this->bGzip)	gzwrite($fp, $sEnd);
		else				fwrite($fp, $sEnd);

		// Cleanup
		unset($sEnd);

		// Close the file
		if($this->bGzip)	gzclose($fp);
		else				fclose($fp);

		// Generate the filename
		$sFilename	= $this->aCurrSitemap['name'] .
						($this->aCurrSitemap['count'] > 0 ? ('_' . $this->aCurrSitemap['count']) : '') .
						'.xml' . ($this->bGzip ? '.gz' : '');

		// Copy the file to the proper location
		if(!copy($sTmpFile, $this->aPaths['local'] . DIRECTORY_SEPARATOR . $sFilename)) {
			trigger_error('Unable to copy "' . $sTmpFile . '" to "' . $this->aPaths['local'] . DIRECTORY_SEPARATOR . $sFilename . '".', E_USER_ERROR);
		}

		// Change the permissions of the new file
		chmod($this->aPaths['local'] . DIRECTORY_SEPARATOR . $sFilename, 0666);

		// Unlink the temp file
		if(!unlink($sTmpFile)) {
			trigger_error('Unable to unlink "' . $sTmpFile . '".', E_USER_ERROR);
		}

		// Add the sitemap to the list so we can create an index later
		$this->aSitemaps[]	= array(
			'loc'		=> $this->aPaths['http'] . '/' . $sFilename,
			'lastmod'	=> date('c')
		);

		// Cleanup
		$this->aCurrSitemap = null;
		unset($fp, $sTmpFile, $sFilename);
	}

	/**
	 * Escape
	 *
	 * Escapes any characters that are invalid XML
	 *
	 * @name escape
	 * @access protected
	 * @param string $text				The text to escape
	 * @return string
	 */
	protected function escape(/*string*/ $text)
	{
		return strtr(
			$text,
			array('&' => '&amp;', '<' => '&lt;', '>' => '&gt;',
					'\'' => '&apos;', '"' => '&quot;')
		);
	}

	/**
	 * Extend Sitemap
	 *
	 * Called when a sitemap can not take any more items, closes the existing
	 * sitemap and opens a new one.
	 *
	 * @name extendSitemap
	 * @access private
	 * @return void
	 */
	private function extendSitemap()
	{
		// If we haven't extended this sitemap yet
		if($this->aCurrSitemap['count'] == 0) {
			$this->aCurrSitemap['count']	= 1;
		}

		// Store the info for the next sitemap
		$aNextSitemap	= array(
			'items' => array(),
			'name'	=> $this->aCurrSitemap['name'],
			'count' => $this->aCurrSitemap['count'] + 1,
			'size'	=> 0,
			'head'	=> array(
				'types' => $this->aCurrSitemap['head']['types'],
				'size'	=> $this->aCurrSitemap['head']['size']
			)
		);

		// Close the existing sitemap
		$this->closeSitemap();

		// Start the new one
		$this->aCurrSitemap = $aNextSitemap;
	}

	/**
	 * Initial Size
	 *
	 * Calculates the initial size of the XML document
	 *
	 * @name initialSize
	 * @access private
	 * @return uint
	 */
	private function initialSize()
	{
		// First remove the tokens from the urlset
		$sUrlset	= str_replace(
			array(self::TOKEN_NS, self::TOKEN_CON),
			'',
			self::URLSET
		);

		// Then get the length of that plus the header, and return it
		return strlen(self::XML_HEAD) + strlen($sUrlset);
	}

	/**
	 * Insert Element
	 *
	 * Adds the XML to the sitemap and records how much space it took
	 *
	 * @name insertElement
	 * @access protected
	 * @param string $xml				The XML element to add
	 * @return bool
	 */
	protected function insertElement(/*string*/ $xml)
	{
		// Add the url element
		$xml	= '<url>' . $xml . ($this->bMobile ? '<mobile:mobile/>' : '') . '</url>';

		// What is the total size of the XML?
		$iSize	= strlen($xml);

		// Is the size of the header plus the items is above the max, or the
		//	max number of items has already been met
		$iTotal = $iSize + $this->aCurrSitemap['size'] + $this->aCurrSitemap['head']['size'];
		if($iTotal > self::MAX_SIZE ||
			count($this->aCurrSitemap['items']) >= self::MAX_ITEMS)
		{
			// This will close the existing sitemap and open a new one for
			//	the new item
			$this->extendSitemap();
		}

		// Add the XML to the items
		$this->aCurrSitemap['items'][]	= $xml;
		$this->aCurrSitemap['size']		+= $iSize;
	}

	/**
	 * Insert Namespace
	 *
	 * Adds a namespace to the list of types for the header
	 *
	 * @name insertNamespace
	 * @access protected
	 * @param uint $type				The type of namespace, one of self::_TYPE_*
	 * @return void
	 */
	protected function insertNamespace(/*uint*/ $type)
	{
		// If the type is already part of the types, do nothing
		if($this->aCurrSitemap['head']['types'] & $type) {
			return;
		}

		// Switch the type
		switch($type)
		{
			case self::TYPE_IMAGE:
				$this->aCurrSitemap['head']['types']	|= $type;
				$this->aCurrSitemap['head']['size']		+= strlen(self::NS_IMAGE) + 1;
				break;
			case self::TYPE_MOBILE:
				$this->aCurrSitemap['head']['types']	|= $type;
				$this->aCurrSitemap['head']['size']		+= strlen(self::NS_MOBILE) + 1;
				break;
			case self::TYPE_NEWS:
				$this->aCurrSitemap['head']['types']	|= $type;
				$this->aCurrSitemap['head']['size']		+= strlen(self::NS_NEWS) + 1;
				break;
			case self::TYPE_VIDEO:
				$this->aCurrSitemap['head']['types']	|= $type;
				$this->aCurrSitemap['head']['size']		+= strlen(self::NS_VIDEO) + 1;
				break;
		}
	}

	/**
	 * Start Sitemap
	 *
	 * Creates a new sitemap to be used
	 *
	 * @name startSitemap
	 * @access public
	 * @param string $name				The base name of the sitemap without '.xml'
	 * @return void
	 */
	public function startSitemap(/*string*/ $name)
	{
		// If there's currently a sitemap being built, close it
		if(!is_null($this->aCurrSitemap)) {
			$this->closeSitemap();
		}

		// Init the current sitemap
		$this->aCurrSitemap = array(
			'items' => array(),
			'name'	=> $name,
			'count' => 0,
			'size'	=> 0,
			'head'	=> array(
				'types' => 0,
				'size'	=> $this->initialSize()
			),
		);

		// If we're in mobile mode
		if($this->bMobile) {
			$this->insertNamespace(self::TYPE_MOBILE);
		}
	}

	/**
	 * Write Index
	 *
	 * Takes the list of all the sitemaps generated so far and puts them in a sitemap index
	 *
	 * @name writeIndex
	 * @access public
	 * @param string $name				The base name of the sitemap index without '.xml'
	 * @return void
	 */
	public function writeIndex(/*string*/ $name)
	{
		// Generate a new temporary filename
		$sTmpFile	= tempnam(sys_get_temp_dir(), 'SM');

		// Open it for writing
		if($this->bGzip)	$fp = gzopen($sTmpFile, 'w9');
		else				$fp = fopen($sTmpFile, 'w');

		// If we can't open the temp file
		if(false === $fp) {
			trigger_error('Unable to open "' . $sTmpFile . '" for writing.', E_USER_ERROR);
		}

		// Write the XML header
		if($this->bGzip)	gzwrite($fp, self::XML_HEAD);
		else				fwrite($fp, self::XML_HEAD);

		// Split the sitemapindex by content token
		list($sStart, $sEnd)	= explode(self::TOKEN_CON, self::SITEMAPINDEX);

		// Write the start of sitemapindex to the file
		if($this->bGzip)	gzwrite($fp, $sStart);
		else				fwrite($fp, $sStart);

		// Cleanup
		unset($sStart);

		// Go through each sitemap and write it to the file
		foreach($this->aSitemaps as $aSitemap) {
			if($this->bGzip)	gzwrite($fp, '<sitemap><loc>' . $this->escape($aSitemap['loc']) . '</loc><lastmod>' . $aSitemap['lastmod'] . '</lastmod></sitemap>');
			else				fwrite($fp, '<sitemap><loc>' . $this->escape($aSitemap['loc']) . '</loc><lastmod>' . $aSitemap['lastmod'] . '</lastmod></sitemap>');
		}

		// Write the end of the urlset
		if($this->bGzip)	gzwrite($fp, $sEnd);
		else				fwrite($fp, $sEnd);

		// Cleanup
		unset($sEnd);

		// Close the file
		if($this->bGzip)	gzclose($fp);
		else				fclose($fp);

		// Generate the full filepath
		$sFilepath	= $this->aPaths['local'] . DIRECTORY_SEPARATOR . $name . '.xml' . ($this->bGzip ? '.gz' : '');

		// Copy the file to the proper location
		if(!copy($sTmpFile, $sFilepath)) {
			trigger_error('Unable to copy "' . $sTmpFile . '" to "' . $sFilepath . '".', E_USER_ERROR);
		}

		// Change the permissions on the new file
		chmod($sFilepath, 0666);

		// Delete the temp file
		if(!unlink($sTmpFile)) {
			trigger_error('Unable to unlink "' . $sTmpFile . '".', E_USER_ERROR);
		}
	}
}
