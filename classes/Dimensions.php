<?php
/**
 * Dimensions
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2015-01-10
 */

/**#@+
 * Defines
 */
define('_DIMENSIONS_ARRAY_HASH',	0x01);
define('_DIMENSIONS_ARRAY_NUM',		0x02);
define('_DIMENSIONS_ARRAY_BOTH',	(_DIMENSIONS_ARRAY_HASH | _DIMENSIONS_ARRAY_NUM));
/**#@-*/

/**
 * Dimensions class
 *
 * Class for handling width and height dimensions
 *
 * @name _Dimensions
 */
class _Dimensions
{
	/**
	 * Height
	 *
	 * Holds the height dimension
	 *
	 * @var uint
	 * @access public
	 */
	public /*uint*/ $height;

	/**
	 * Width
	 *
	 * Holds the width dimension
	 *
	 * @var uint
	 * @access public
	 */
	public /*uint*/ $width;

	/**
	 * Fit Inside
	 *
	 * Returns a new Dimensions instance that fits the passed dimensions within
	 * the bounds of the current dimensions
	 *
	 * @name fitInside
	 * @access public
	 * @param _Dimensions $dims			The dimensions to resize
	 * @param bool $round				If true will round off to the nearest whole number
	 * @return _Dimensions
	 */
	public static function fitInside(_Dimensions $dims, /*bool*/ $round = true)
	{
		// Initialise return values
		$oNew	= new _Dimensions(0, 0);

		// If either the width or the height is smaller than the boundary box
		//	width and height, then resize the dimensions to be larger
		if($dims->width < $this->width && $dims->height < $this->height)
		{
			// If the difference in width is larger than the difference in height
			if(($this->width / $dims->width) > ($this->height / $dims->height))
			{
				$oNew->height	= $this->height;
				$oNew->width	= $this->height * ($dims->width / $dims->height);
			}
			// Else, the difference in height was larger
			else
			{
				$oNew->width	= $this->width;
				$oNew->height	= $this->width * ($dims->height / $dims->width);
			}
		}
		// Else if the image is larger than the boundary box, we need to resize
		//	the dimensions to be smaller
		else
		{
			// If the difference in width is larger than the difference in height
			if(($dims->width / $this->width) > ($dims->height / $this->height))
			{
				$oNew->width	= $this->width;
				$oNew->height	= $this->width * ($dims->height / $dims->width);
			}
			// Else, the difference in height was larger
			else
			{
				$oNew->height	= $this->height;
				$oNew->width	= $this->height * ($dims->width / $dims->height);
			}
		}

		// If we need to round off the values
		if($round) {
			$aNew	= array('width' => round($oNew->width), 'height' => round($oNew->height));
		}

		// Return the new dimensions
		return $oNew;
	}

	/**
	 * Fit One Dimension
	 *
	 * Returns a new Dimensions instance that fits the smaller of the two
	 * dimensions into the bounds of the current dimensions
	 *
	 * @name fitOneDimension
	 * @access public
	 * @param _Dimensions $dims			The dimensions to resize
	 * @param bool $round				If true will round off to the nearest whole number
	 * @return _Dimensions
	 */
	public static function fitOneDimension(array $dims, /*bool*/ $round = false)
	{
		// Initialise return values
		$oNew	= new self(0, 0);

		// If either the width or the height is smaller than the boundary box
		//	width and height, then resize the dimensions to be larger
		if($dims->width < $this->width || $dims->height < $this->height)
		{
			// If the difference in width is larger than the difference in heights
			if(($this->width / $dims->width) > ($this->height / $dims->height))
			{
				$oNew->width	= $this->width;
				$oNew->height	= $this->width * ($dims->height / $dims->width);
			}
			// Else, the difference in height was larger
			else
			{
				$oNew->height	= $this->height;
				$oNew->width	= $this->height * ($dims->width / $dims->height);
			}
		}
		// Else if the image is larger than the boundary box, we need to resize
		//	the dimensions to be smaller
		else
		{
			// If the difference in width is larger than the difference in height
			if(($dims->width / $this->width) > ($dims->height / $this->height))
			{
				$oNew->height	= $this->height;
				$oNew->width	= $this->height * ($dims->width / $dims->height);
			}
			// Else, the difference in height was larger
			else
			{
				$oNew->width	= $this->width;
				$oNew->height	= $this->width * ($dims->height / $dims->width);
			}
		}

		// If we need to round off the values
		if($round) {
			$aNew	= array('width' => round($oNew->width), 'height' => round($oNew->height));
		}

		// Return the new dimensions
		return $oNew;
	}

	/**
	 * To Array
	 *
	 * Returns the instance as an array
	 *
	 * @name toArray
	 * @access public
	 * @param uint $type				One of _DIMENSIONS_ARRAY_HASH, _DIMENSIONS_ARRAY_NUM, or _DIMENSIONS_ARRAY_BOTH
	 * @return array
	 */
	public function toArray(/*uint*/ $type = _DIMENSIONS_ARRAY_HASH)
	{
		// Init the return array
		$aReturn	= array();

		// If the numeric flag is set
		if($type & _DIMENSIONS_ARRAY_NUM) {
			$aReturn[]	= $this->width;
			$aReturn[]	= $this->height;
		}

		// If the hash flag is set
		if($type & _DIMENSIONS_ARRAY_HASH) {
			$aReturn['width']	= $this->width;
			$aReturn['height']	= $this->height;
		}

		// Return the result
		return $aReturn;
	}
}