<?
/**
* $Id: S3.php 36 2008-10-23 06:53:41Z don.schonknecht $
* 
* Based on code from Donovan Schönknecht.  Modified by Jon Gilkison <jg@massifycorp.com>
*
* Original Copyright Notice:
* 
* Copyright (c) 2008, Donovan Schönknecht.  All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* Amazon S3 is a trademark of Amazon.com, Inc. or its affiliates.
*/

uses('system.cloud.provider.amazon.aws_exception');
uses('system.cloud.provider.amazon.s3_request');
uses('system.cloud.provider.amazon.s3');

/**
 * S3 Driver for StorageManager
 */
class S3StorageManager extends StorageManager
{
	private $id=null;
	private $secret=null;

	/**
	 * Constructor
	 *
	 * @param string $id Amazon ID
	 * @param string $secret Amazon Secret
	 */
	public function __construct($id,$secret)
	{
		$this->id=$id;
		$this->secret=$secret;
	}

	/**
	* Get a list of buckets
	*
	* @param boolean $detailed Returns detailed bucket list when true
	* @return array | false
	*/
	public function list_buckets($detailed = false) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'GET', '', '');
		
		$rest = $rest->getResponse();
		
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
			
		if ($rest->error !== false) 
			throw new AWSException(sprintf("S3::listBuckets(): [%s] %s", $rest->error['code'], $rest->error['message']));
		
		$results = array(); //var_dump($rest->body);
		
		if (!isset($rest->body->Buckets)) 
			return $results;

		if ($detailed) 
		{
			if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
				$results['owner'] = array(
					'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->ID
					);

			$results['buckets'] = array();

			foreach ($rest->body->Buckets->Bucket as $b)
				$results['buckets'][] = array(
					'name' => (string)$b->Name, 'time' => strtotime((string)$b->CreationDate)
				);
		} 
		else
			foreach ($rest->body->Buckets->Bucket as $b) 
				$results[] = (string)$b->Name;

		return $results;
	}
	
	/**
	 * Creates a new bucket
	 * 
	 * @param $bucket
	 * @param $acl
	 * @param $location
	 * @return unknown_type
	 */
	public function create_bucket($bucket, $acl = StorageManager::ACL_PRIVATE, $location = false) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'PUT', $bucket, '');
		$rest->setAmzHeader('x-amz-acl', $acl);

		if ($location !== false) 
		{
			$dom = new DOMDocument();

			$createBucketConfiguration = $dom->createElement('CreateBucketConfiguration');
			$locationConstraint = $dom->createElement('LocationConstraint', strtoupper($location));
			$createBucketConfiguration->appendChild($locationConstraint);
			$dom->appendChild($createBucketConfiguration);
			$rest->data = $dom->saveXML();
			$rest->size = strlen($rest->data);
			$rest->setHeader('Content-Type', 'application/xml');
		}

		$rest = $rest->getResponse();

		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');

		if ($rest->error !== false) 
			throw new AWSException(sprintf("S3::putBucket({$bucket}, {$acl}, {$location}): [%s] %s",$rest->error['code'], $rest->error['message']));
		
		return true;
	}
		
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
	public function bucket_contents($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'GET', $bucket, '');
	
		if ($prefix !== null && $prefix !== '') 
			$rest->setParameter('prefix', $prefix);

		if ($marker !== null && $marker !== '') 
			$rest->setParameter('marker', $marker);

		if ($maxKeys !== null && $maxKeys !== '') 
			$rest->setParameter('max-keys', $maxKeys);
		
		if ($delimiter !== null && $delimiter !== '') 
			$rest->setParameter('delimiter', $delimiter);
		
		$response = $rest->getResponse();
		
		if ($response->error === false && $response->code !== 200)
			$response->error = array('code' => $response->code, 'message' => 'Unexpected HTTP status');
		
		if ($response->error !== false) 
			throw new AWSException(sprintf("S3::getBucket(): [%s] %s", $response->error['code'], $response->error['message']));

		$results = array();

		$lastMarker = null;
		if (isset($response->body, $response->body->Contents))
			foreach ($response->body->Contents as $c) 
			{
				$results[(string)$c->Key] = array(
					'name' => (string)$c->Key,
					'time' => strtotime((string)$c->LastModified),
					'size' => (int)$c->Size,
					'hash' => substr((string)$c->ETag, 1, -1)
				);
				$lastMarker = (string)$c->Key;
				//$response->body->IsTruncated = 'true'; break;
			}


		if (isset($response->body->IsTruncated) && (string)$response->body->IsTruncated == 'false') 
			return $results;

		// Loop through truncated results if maxKeys isn't specified
		if ($maxKeys == null && $lastMarker !== null && (string)$response->body->IsTruncated == 'true')
			do 
			{
				$rest = new S3Request($this->id,$this->secret,true,'GET', $bucket, '');

				if ($prefix !== null) 
					$rest->setParameter('prefix', $prefix);

				$rest->setParameter('marker', $lastMarker);

				if (($response = $rest->getResponse(true)) == false || $response->code !== 200) 
					break;

				if (isset($response->body, $response->body->Contents))
					foreach ($response->body->Contents as $c) 
					{
						$results[(string)$c->Key] = array(
							'name' => (string)$c->Key,
							'time' => strtotime((string)$c->LastModified),
							'size' => (int)$c->Size,
							'hash' => substr((string)$c->ETag, 1, -1)
						);
						
						$lastMarker = (string)$c->Key;
					}
				} 
				while ($response !== false && (string)$response->body->IsTruncated == 'true');

		return $results;
	}
	
	/**
	 * Deletes a bucket
	 * 
	 * @param $bucket
	 * @return unknown_type
	 */
	public function delete_bucket($bucket) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'DELETE', $bucket);
		$rest = $rest->getResponse();
	
		if ($rest->error === false && $rest->code !== 204)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
	
		if ($rest->error !== false) 
			throw new AWSException(sprintf("S3::deleteBucket({$bucket}): [%s] %s", $rest->error['code'], $rest->error['message']));
		
		return true;
	}
	
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
	public function put($input, $bucket, $uri, $acl = StorageManager::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array()) 
	{
		if ($input == false) 
			return false;
		
		$rest = new S3Request($this->id,$this->secret,true,'PUT', $bucket, $uri);

		if (is_string($input)) 
			$input = array(
				'data' => $input, 
				'size' => strlen($input),
				'md5sum' => base64_encode(md5($input, true))
			);

		// Data
		if (isset($input['fp']))
			$rest->fp =& $input['fp'];
		elseif (isset($input['file']))
			$rest->fp = @fopen($input['file'], 'rb');
		elseif (isset($input['data']))
			$rest->data = $input['data'];

		// Content-Length (required)
		if (isset($input['size']) && $input['size'] > -1)
			$rest->size = $input['size'];
		else 
		{
			if (isset($input['file']))
				$rest->size = filesize($input['file']);
			elseif (isset($input['data']))
				$rest->size = strlen($input['data']);
		}

		// Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
		if (is_array($requestHeaders))
			foreach ($requestHeaders as $h => $v) $rest->setHeader($h, $v);
		elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
			$input['type'] = $requestHeaders;

		// Content-Type
		if (!isset($input['type'])) 
		{
			if (isset($requestHeaders['Content-Type']))
				$input['type'] =& $requestHeaders['Content-Type'];
			elseif (isset($input['file']))
				$input['type'] = $this->getMimeType($input['file']);
			else
				$input['type'] = 'application/octet-stream';
		}

		// We need to post with Content-Length and Content-Type, MD5 is optional
		if ($rest->size > 0 && ($rest->fp !== false || $rest->data !== false)) 
		{
			$rest->setHeader('Content-Type', $input['type']);
			if (isset($input['md5sum'])) $rest->setHeader('Content-MD5', $input['md5sum']);

			$rest->setAmzHeader('x-amz-acl', $acl);
			foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-'.$h, $v);
			$rest->getResponse();
		} 
		else
			$rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		
		if ($rest->response->error !== false) 
		{
			print_r($rest->response);
			throw new AWSException(sprintf("S3::putObject(): [%s] %s", $rest->response->error['code'], $rest->response->error['message']));
		}
		
		return true;
	}
	
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
	public function put_file($file, $bucket, $uri, $acl = StorageManager::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array())
	{
		if (!file_exists($file) || !is_file($file) || !is_readable($file)) 
			throw new AWSException('S3::inputFile(): Unable to open input file: '.$file);
		
		$input=array(
			'file' => $file, 
			'size' => filesize($file),
			'md5sum' => base64_encode(md5_file($file, true))
		);
		
		return $this->put($input,$bucket,$uri,$acl,$metaHeaders,$requestHeaders);
	}
	
	/**
	 * Gets an object from the storage system
	 * 
	 * @param $bucket
	 * @param $uri
	 * @param $saveTo
	 * @return unknown_type
	 */
	public function get($bucket, $uri, $saveTo = false) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'GET', $bucket, $uri);
		if ($saveTo !== false) 
		{
			if (is_resource($saveTo))
				$rest->fp =& $saveTo;
			else
				if (($rest->fp = @fopen($saveTo, 'wb')) == false)
				$rest->response->error = array('code' => 0, 'message' => 'Unable to open save file for writing: '.$saveTo);
		}
		
		if ($rest->response->error === false) 
			$rest->getResponse();

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		
		if ($rest->response->error !== false) 
			throw new AWSException(sprintf("S3::getObject({$bucket}, {$uri}): [%s] %s",$rest->response->error['code'], $rest->response->error['message']));
		
		$rest->file = realpath($saveTo);
		
		return $rest->response;
	}
	
	/**
	* Get object information
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param boolean $returnInfo Return response information
	* @return mixed | false
	*/
	public function info($bucket, $uri, $returnInfo = true) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'HEAD', $bucket, $uri);
	
		$rest = $rest->getResponse();
	
		if ($rest->error === false && ($rest->code !== 200 && $rest->code !== 404))
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
	
		if ($rest->error !== false) 
			throw new AWSException(sprintf("S3::getObjectInfo({$bucket}, {$uri}): [%s] %s",$rest->error['code'], $rest->error['message']));
		
		return $rest->code == 200 ? $returnInfo ? $rest->headers : true : false;
	}	

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
	public function copy($srcBucket, $srcUri, $bucket, $uri, $acl = StorageManager::ACL_PRIVATE) 
	{
		$rest = new S3Request($this->id,$this->secret,$this->useSSL,'PUT', $bucket, $uri);
		$rest->setAmzHeader('x-amz-acl', $acl);
		$rest->setAmzHeader('x-amz-copy-source', sprintf('/%s/%s', $srcBucket, $srcUri));
		$rest = $rest->getResponse();
	
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
	
		if ($rest->error !== false) 
			throw new AWSException(sprintf("S3::copyObject({$srcBucket}, {$srcUri}, {$bucket}, {$uri}): [%s] %s", $rest->error['code'], $rest->error['message']));
		
		return isset($rest->body->LastModified, $rest->body->ETag) ? array(
			'time' => strtotime((string)$rest->body->LastModified),
			'hash' => substr((string)$rest->body->ETag, 1, -1)
		) : false;
	}

	/**
	 * Deletes an object from the bucket
	 */
	public function delete($bucket, $uri) 
	{
		$rest = new S3Request($this->id,$this->secret,true,'DELETE', $bucket, $uri);
	
		$rest = $rest->getResponse();
	
		if ($rest->error === false && $rest->code !== 204)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
	
		if ($rest->error !== false) 
			throw new AWSException(sprintf("S3::deleteObject(): [%s] %s",$rest->error['code'], $rest->error['message']));

		return true;
	}
	
		/**
	* Get MIME type for file
	*
	* @internal Used to get mime types
	* @param string &$file File path
	* @return string
	*/
	public function getMimeType(&$file) 
	{
		$type = false;
		// Fileinfo documentation says fileinfo_open() will use the
		// MAGIC env var for the magic file
		if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
		($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {
			if (($type = finfo_file($finfo, $file)) !== false) {
				// Remove the charset and grab the last content-type
				$type = explode(' ', str_replace('; charset=', ';charset=', $type));
				$type = array_pop($type);
				$type = explode(';', $type);
				$type = trim(array_shift($type));
			}
			finfo_close($finfo);

		// If anyone is still using mime_content_type()
		} elseif (function_exists('mime_content_type'))
			$type = trim(mime_content_type($file));

		if ($type !== false && strlen($type) > 0) return $type;

		// Otherwise do it the old fashioned way
		static $exts = array(
			'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
			'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
			'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
			'zip' => 'application/zip', 'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
			'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
			'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
		);
		$ext = strToLower(pathInfo($file, PATHINFO_EXTENSION));
		return isset($exts[$ext]) ? $exts[$ext] : 'application/octet-stream';
	}
	
}