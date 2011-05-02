<?
abstract class StorageManager
{
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	
	private static $_managers=array();
	
	/**
	 * Fetches a named database from the connection pool.
	 *
	 * @param string $name Name of the database to fetch from the connection pool.
	 * @return Database The named database.
	 */
	public static function GetManager($name)
	{
		if (isset(self::$_managers[$name]))
			return self::$_managers[$name];
		else
		{
			$conf=Config::Get('cloud');

			if (isset($conf->dsn->items[$name]))
			{
				$dsn=$conf->dsn->items[$name]->storage;
				$matches=array();
				if (preg_match_all('#([a-z0-9]*)://([^@]*)@(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];
					$auth=$matches[2][0];
					$secret=$matches[3][0];
					
					uses('system.cloud.storage.'.$driver.'.'.$driver.'_storage_manager');
					
					$class=$driver."StorageManager";
					$storage=new $class($auth,$secret);
					
					self::$_managers[$name]=$storage;
					return $storage;
				}
			}

			throw new Exception("Cannot find storage named '$name' in Config.");
		}
	}
	
	/**
	* Get a list of buckets
	*
	* @param boolean $detailed Returns detailed bucket list when true
	* @return array | false
	*/
	abstract function list_buckets($detailed = false);

	
	/**
	 * Creates a new bucket
	 * 
	 * @param $bucket
	 * @param $acl
	 * @param $location
	 * @return unknown_type
	 */
	abstract function create_bucket($bucket, $acl = StorageManager::ACL_PRIVATE, $location = false);

		
	/**
	 * Gets the contents of a bucket
	 * 
	 * @param $bucket
	 * @param $prefix
	 * @param $marker
	 * @param $maxKeys
	 * @param $delimiter
	 * @return unknown_type
	 */
	abstract function bucket_contents($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null);

	
	/**
	 * Deletes a bucket
	 * 
	 * @param $bucket
	 * @return unknown_type
	 */
	abstract function delete_bucket($bucket);

	
	/**
	 * Puts a file or data to the storage system
	 * 
	 * @param $input
	 * @param $bucket
	 * @param $uri
	 * @param $acl
	 * @param $metaHeaders
	 * @param $requestHeaders
	 * @return unknown_type
	 */
	abstract function put($input, $bucket, $uri, $acl = StorageManager::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array());
	
	/**
	 * Puts a file to the storage system
	 * 
	 * @param $file
	 * @param $bucket
	 * @param $uri
	 * @param $acl
	 * @param $metaHeaders
	 * @param $requestHeaders
	 * @return unknown_type
	 */
	abstract function put_file($file, $bucket, $uri, $acl = StorageManager::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array());
	
	/**
	 * Gets an object from the storage system
	 * 
	 * @param $bucket
	 * @param $uri
	 * @param $saveTo
	 * @return unknown_type
	 */
	abstract function get($bucket, $uri, $saveTo = false);
	
	/**
	* Get object information
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param boolean $returnInfo Return response information
	* @return mixed | false
	*/
	abstract function info($bucket, $uri, $returnInfo = true);

	/**
	* Copy an object
	*
	* @param string $bucket Source bucket name
	* @param string $uri Source object URI
	* @param string $bucket Destination bucket name
	* @param string $uri Destination object URI
	* @param constant $acl ACL constant
	* @return mixed | false
	*/
	abstract function copy($srcBucket, $srcUri, $bucket, $uri, $acl = StorageManager::ACL_PRIVATE);

	/**
	 * Deletes an object from the bucket
	 */
	abstract function delete($bucket, $uri);

}