<?php
/**
 * HTTP Class
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * HTTP class
 *
 * Handles anything http related
 *
 * @name _HTTP
 */
class _HTTP
{
	/**
	 * Handle Upload
	 *
	 * Verifies and moves an uploaded file
	 *
	 * @name handleUpload
	 * @access public
	 * @static
	 * @param array $file				The array of the file upload via $_FILES
	 * @param string $new_location		The place to store the uploaded file
	 * @param array $allowed_types		The list of allowed mime types, null to allow all types
	 * @return true|string				Returns an error message if anything goes wrong
	 */
	public static function handleUpload(array $file, /*string*/ $new_location, array $allowed_types = array())
	{
		// If there was an error
		if($file['error'] != UPLOAD_ERR_OK)
		{
			switch($file['error'])
			{
				case UPLOAD_ERR_INI_SIZE:	return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
				case UPLOAD_ERR_FORM_SIZE:	return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
				case UPLOAD_ERR_PARTIAL:	return 'The uploaded file was only partially uploaded.';
				case UPLOAD_ERR_NO_FILE:	return 'No file was uploaded.';
				case UPLOAD_ERR_NO_TMP_DIR: return 'Missing a temporary folder.';
				case UPLOAD_ERR_CANT_WRITE: return 'Failed to write file to disk.';
				case UPLOAD_ERR_EXTENSION:	return 'A PHP extension stopped the file upload.';
				default:					return 'An unknown error of ' . $file['error'] . ' was returned.';
			}
		}

		// Is the file a valid image type?
		if(!empty($allowed_types) && !in_array($file['type'], $allowed_types)) {
			return 'Invalid file format: ' . $file['type'];
		}

		// Try to move the uploaded file
		if(!move_uploaded_file($file['tmp_name'], $new_location)) {
			return 'Unable to move uploaded file.';
		}

		// A-OK
		return true;
	}

	/**
	 * URL
	 *
	 * Returns the full URL to the current page
	 *
	 * @name url
	 * @access public
	 * @static
	 * @param bool $encode				If true the value is returned encoded
	 * @return string
	 */
	public static function url(/*bool*/ $encode = false)
	{
		// Check the protocal
		$sHTTP	= isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ?
					'https' : 'http';

		// Create the URL
		$sURL	= "{$sHTTP}://{$_SERVER['SERVER_NAME']}/{$_SERVER['REQUEST_URI']}";

		// If it needs to be encoded
		if($encode) {
			$sURL	= urlencode($sURL);
		}

		// Return the URL
		return $sURL;
	}

	/**
	 * Get Primary Domain
	 *
	 * Parses the current hostname to generate a string that represents the top
	 * level part of the domain name
	 *
	 * @name getPrimaryDomain
	 * @access public
	 * @static
	 * @return string
	 */
	public static function getPrimaryDomain()
	{
		// If there's a port
		if(strpos($_SERVER['HTTP_HOST'], ':')) {
			// Split the HTTP_HOST into domain and port
			list($sDomain, $sPort)	= explode(':', $_SERVER['HTTP_HOST']);
		} else {
			$sDomain	= $_SERVER['HTTP_HOST'];
			$sPort		= false;
		}

		// Remove a subdomain if it exists
		$aParts		= explode('.', $sDomain);
		$sDomain	= implode('.', array_slice($aParts, -2));

		// If there's a port
		if($sPort) {
			$sDomain	.= ':' . $sPort;
		}

		// Return the domain
		return $sDomain;
	}
}