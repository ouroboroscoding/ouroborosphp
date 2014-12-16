<?php
/**
 * MyCache
 *
 * @author Chris Nasr
 * @copyright FUEL for the FIRE
 * @created 2014-12-02
 */

/**
 * MyCache class
 *
 * Handles all connections and queries to Redis caches. Uses configuration
 * values in 'redis'
 *
 * @name _MyCache
 */
class _MyCache
{
	/**
	 * AUTH timeout time
	 */
	const AUTH_TIMEOUT	= 300;

	/**
	 * Connections
	 *
	 * Holds the Redis instances and other related info
	 *
	 * @var array[]
	 * @access private
	 */
	private static $aCons = array();

	/**
	 * Constructor
	 *
	 * Private so this class can never be instantiated
	 *
	 * @name _MyCache
	 * @access private
	 * @return _MyCache
	 */
	private function __constructor() {}

	/**
	 * Clear Connection
	 *
	 * Resets the connection variable so we try to connect again
	 *
	 * @name clearConnection
	 * @access private
	 * @param string $server			The server to connect to
	 * @param string $type				The type of transaction
	 * @return void
	 */
	private static function clearConnection(/*string*/ $server, /*string*/ $type)
	{
		// Create the combined string of server and type
		$sWhich = "{$server}:{$type}";

		// If the variable exists
		if(isset(self::$aCons[$sWhich]))
		{
			// Close it and unset it
			self::$aCons[$sWhich]['redis']->close();
			unset(self::$aCons[$sWhich]);
		}
	}

	/**
	 * Delete
	 *
	 * Removes a key from the cache
	 *
	 * @name delete
	 * @access public
	 * @static
	 * @param string $server			The server to delete the key from
	 * @param string $key				The key to delete
	 * @return void
	 */
	public static function delete(/*string*/ $server, /*string*/ $key)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Delete the key
		$oRedis->del($key);
	}

	/**
	 * Expire
	 *
	 * Sets the TTL on a specific key
	 *
	 * @name expire
	 * @access public
	 * @static
	 * @param string $server			The server the key is on
	 * @param string					The key to update
	 * @param uint $ttl					The time in seconds before expiration
	 * @return void
	 */
	public static function expire(/*string*/ $server, /*string*/ $key, /*uint*/ $ttl)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Set the TTL
		$oRedis->expire($key, $ttl);
	}

	/**
	 * Fetch Connection
	 *
	 * Returns a connection to the given server, if there isn't one, it creates
	 * the instance and connects before returning
	 *
	 * @name fetchConnection
	 * @access private
	 * @static
	 * @throws MySQL_Exception
	 * @param string $server			The server to connect to
	 * @param string $type				The type of transaction
	 * @return Redis
	 */
	private static function fetchConnection(/*string*/ $server, /*string*/ $type, /*uint*/ $count = 0)
	{
		// Create the combined string of server and type
		$sWhich = "{$server}:{$type}";

		// If we already have the connection
		if(isset(self::$aCons[$sWhich]))
		{
			// If we have an AUTH key and haven't called AUTH in a while
			if(isset(self::$aCons[$sWhich]['auth_key']) && self::$aCons[$sWhich]['auth_ts'] < (time() - self::AUTH_TIMEOUT))
			{
				// If we fail to authenticate
				if(!self::$aCons[$sWhich]['redis']->auth(self::$aCons[$sWhich]['auth_key'])) {
					throw new RedisException('Failed to authenticate Redis server \'' . $server . '\'');
				}

				// Store the new timestamp
				self::$aCons[$sWhich]['auth_ts']	= time();
			}

			// Return the Redis instance
			return self::$aCons[$sWhich]['redis'];
		}

		// Store the conf as the type
		$aConf	= $type;

		// As long as the conf is a string, which it will always be the first time
		while(is_string($aConf))
		{
			// Get the config info
			$aConf	= _Config::get(array('redis', $server, $aConf), array(
				'host'		=> '127.0.0.1',
				'port'		=> '6379',
				'key'		=> null
			));
		}

		// Create a new instance of Redis
		$oRedis = new Redis;

		// Try to connect
		if(!$oRedis->connect($aConf['host'], $aConf['port'])) {
			if(++$count == 5) {
				throw new RedisException('Failed to connect to Redis server \'' . $server . '\'');
			} else {
				sleep(1);
				return self::fetchConnection($server, $type, $count);
			}
		}

		// Store the connection in the array
		self::$aCons[$sWhich]	= array(
			'redis'		=> $oRedis
		);

		// If there's a key
		if(isset($aConf['key']) && !is_null($aConf['key']))
		{
			// Save the key
			self::$aCons[$sWhich]['auth_key']	= $aConf['key'];

			// If we fail to authenticate
			if(!$oRedis->auth($aConf['key'])) {
				throw new RedisException('Failed to authenticate Redis server \'' . $server . '\'');
			}

			// Store the timestamp
			self::$aCons[$sWhich]['auth_ts']	= time();
		}

		// Return the instance
		return $oRedis;
	}

	/**
	 * Flush DB
	 *
	 * Destroys all keys on the given server
	 *
	 * @name flushDB
	 * @access public
	 * @static
	 * @param string $server			The server to flush
	 * @return void
	 */
	public static function flushDB(/*string*/ $server)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Flush the DB
		$oRedis->flushDB();
	}

	/**
	 * Get
	 *
	 * Returns a key on a specific server
	 *
	 * @name get
	 * @access public
	 * @static
	 * @param string $server			The server to fetch the key from
	 * @param string $key				The key to search for
	 * @return mixed
	 */
	public static function get(/*string*/ $server, /*string*/ $key)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'read');

		// Get the value from Redis and return it
		return $oRedis->get($key);
	}

	/**
	 * Get Multiple
	 *
	 * Returns multiple keys on a specific server
	 *
	 * @name getMultiple
	 * @access public
	 * @static
	 * @param string $server			The server to fetch the keys from
	 * @param string[] $keys			The keys to look for
	 * @return array
	 */
	public static function getMultiple(/*string*/ $server, array $keys)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'read');

		// Try to get the keys and return them
		return $oRedis->mGet($keys);
	}

	/**
	 * Hash Get
	 *
	 * Returns one element of a hash or all elements of the hash based on the
	 * arguments
	 *
	 * @name hashGet
	 * @access public
	 * @static
	 * @param string $server			The server the hash is on
	 * @param string $key				The key to the hash
	 * @param string $hash				If set, only this element in the hash will be returned
	 * @param mixed
	 */
	public static function hashGet(/*string*/ $server, /*string*/ $key, /*string*/ $hash = null)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'read');

		// If there's no hash
		if(is_null($hash))
		{
			// Get all parts of the hash
			$aVals	= $oRedis->hGetAll($key);

			// If there's no items
			if(!count($aVals)) {
				return false;
			}

			// Return the vals
			return $aVals;
		}
		// Else
		else
		{
			// Try to get one specific element in the hash and return it
			return $oRedis->hGet($key, $hash);
		}
	}

	/**
	 * Hash Increment
	 *
	 * Increment a hash value by 1
	 *
	 * @name hashIncrement
	 * @access public
	 * @static
	 * @param string $server			The server the hash is on
	 * @param string $key				The key to the hash
	 * @param string $hash				The hash element to inrement
	 * @return int
	 */
	public static function hashIncrement(/*string*/ $server, /*string*/ $key, /*string*/ $hash)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Increment the value
		return $oRedis->hIncrBy($key, $hash, 1);
	}

	/**
	 * Hash Set
	 *
	 * Sets one or more values in a hash
	 *
	 * @name hashSet
	 * @access public
	 * @static
	 * @param string $server			The server the hash is on
	 * @param string $key				The key to the hash
	 * @param string|array $hash		A single hash, or an array of hashes => values
	 * @param mixed $value				The value to set if a single hash is sent
	 * @param void
	 */
	public static function hashSet(/*string*/ $server, /*string*/ $key, /*string|array*/ $hash, /*mixed*/ $value = null)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// If the hash is an array it's a list of elements and values
		if(is_array($hash) && is_null($value))
		{
			// Put the instance into pipeline mode
			$oRedis->multi(Redis::PIPELINE);

			// Go through each item
			foreach($hash as $sHash => $mVal) {
				// And add it to the hash
				$oRedis->hSet($key, $sHash, $mVal);
			}

			// Execute all commands
			$oRedis->exec();
		}
		// Else we're setting one element
		else if(is_string($hash) && !is_null($value))
		{
			// Set the element
			$oRedis->hSet($key, $hash, $value);
		}
		// Else something is wrong
		else
		{
			trigger_error(__METHOD__ . ' Error: Wrong set of parameters.', E_USER_ERROR);
		}
	}

	/**
	 * Increment
	 *
	 * Adds 1 to an existing value
	 *
	 * @name increment
	 * @access public
	 * @static
	 * @param string $server			The server the value is on
	 * @param string					The key to the value
	 * @return void
	 */
	public static function increment(/*string*/ $server, /*string*/ $key, /*uint*/ $incr_by = 1)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// If we only need to increment by 1
		if($incr_by == 1) {
			$oRedis->incr($key);
		} else {
			$oRedis->incrBy($key, $incr_by);
		}
	}

	/**
	 * List Length
	 *
	 * Returns the length of items in a list
	 *
	 * @name listLen
	 * @access public
	 * @static
	 * @param string $server			The server the list is on
	 * @param string $key				They key to the list
	 * @return uint
	 */
	public static function listLen(/*string*/ $server, /*string*/ $key)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'read');

		// Return the list length
		return $oRedis->lLen($key);
	}

	/**
	 * List Pop
	 *
	 * Pops an item off the end of a list
	 *
	 * @name listPop
	 * @access public
	 * @static
	 * @param string $server			The server to pop the list item from
	 * @param string $key				The key to the list
	 * @return mixed
	 */
	public static function listPop(/*string*/ $server, /*string*/ $key)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Right pop the value from Redis and return it
		return $oRedis->rPop($key);
	}

	/**
	 * List Push
	 *
	 * Pushes an item on to the end of a list
	 *
	 * @name listPush
	 * @access public
	 * @static
	 * @param string $server			The server to push the list item to
	 * @param string $key				The key to the list
	 * @param mixed $value				The value to push to the list
	 * @return void
	 */
	public static function listPush(/*string*/ $server, /*string*/ $key, /*mixed*/ $value)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Right push the item to Redis
		$oRedis->rPush($key, $value);
	}

	/**
	 * List Range
	 *
	 * Returns a range of items in a list, see redis LRANGE
	 *
	 * @name listRange
	 * @access public
	 * @static
	 * @param string $server			The server to shift the list item off
	 * @param string $key				The key to the list
	 * @param int $start				The first element in the range
	 * @param int $end					The last element in the range
	 * @return array
	 */
	public static function listRange(/*string*/ $server, /*string*/ $key, /*int*/ $start = 0, /*int*/ $end = -1)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'read');

		// Get the range from Redis amd return it
		return $oRedis->lRange($key, $start, $end);
	}

	/**
	 * List Shift
	 *
	 * Shifts an item off the beginning of a list
	 *
	 * @access public
	 * @static
	 * @param string $server			The server to shift the list item off
	 * @param string $key				The key to the list
	 * @return mixed
	 */
	public static function listShift(/*string*/ $server, /*string*/ $key)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Left pop the value from Redis and return it
		return $oRedis->lPop($key);
	}

	/**
	 * List Trim
	 *
	 * Trims a list based on the start and end arguments. See redis LTRIM
	 *
	 * @name listTrim
	 * @access public
	 * @static
	 * @param string $server			The server to unshift the list item to
	 * @param string $key				The key to the list
	 * @param int $start				Trim items before this
	 * @param int $end					Trim items after this
	 * @return void
	 */
	public static function listTrim(/*string*/ $server, /*string*/ $key, /*int*/ $start, /*int*/ $end)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Trim the list
		$oRedis->lTrim($key, $start, $end);
	}

	/**
	 * List Unshift
	 *
	 * Unshifts an item onto the beginning of a list
	 *
	 * @access public
	 * @static
	 * @param string $server			The server to unshift the list item to
	 * @param string $key				The key to the list
	 * @param mixed $value				The value to unshift to the list
	 * @return void
	 */
	public static function listUnshift(/*string*/ $server, /*string*/ $key, /*mixed*/ $value)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Left push the item to Redis
		$oRedis->lPush($key, $value);
	}

	/**
	 * Set
	 *
	 * Sets a key on a specific server
	 *
	 * @name set
	 * @access public
	 * @static
	 * @param string $server			The server to set the key on
	 * @param string $key				The key to set
	 * @param mixed $value				The value to set on the key
	 * @param uint $ttl					If set, a TTL will be set on the key
	 * @return bool
	 */
	public static function set(/*string*/ $server, /*string*/ $key, /*mixed*/ $value, /*uint*/ $ttl = 0)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Store the value whether there's an expiration time or not
		if($ttl) {
			return $oRedis->setex($key, $ttl, $value);
		} else {
			return $oRedis->set($key, $value);
		}
	}

	/**
	 * Set Multiple
	 *
	 * Sets multiple keys on a specific server
	 *
	 * @name setMultiple
	 * @access public
	 * @static
	 * @param string $server			The server to set the key on
	 * @param array[] $values			A hash of key to value pairs to set
	 * @param uint $ttl					If set, a TTL will be set on each key
	 * @return bool
	 */
	public static function setMultiple(/*string*/ $server, array $values, /*uint*/ $ttl = 0)
	{
		// Get the connection
		$oRedis = self::fetchConnection($server, 'write');

		// Put the instance into pipeline mode
		$oRedis->multi(Redis::PIPELINE);

		// Store the value whether there's an expiration time or not
		if($ttl) {
			foreach($values as $sKey => $mValue) {
				$oRedis->setex($sKey, $ttl, $mValue);
			}
		} else {
			foreach($values as $sKey => $mValue) {
				$oRedis->set($sKey, $mValue);
			}
		}

		// Execute all commands
		return $oRedis->exec();
	}
}
