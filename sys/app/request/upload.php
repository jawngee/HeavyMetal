<?
/**
 * Wraps a $_FILE upload
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Massify LLC
 * @package       application
 * 
 * Copyright (c) 2009, Jon Gilkison and Massify LLC.
 * All rights reserved.
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
 * This is a modified BSD license (the third clause has been removed).
 * The BSD license may be found here:
 * 
 * http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Represents an uploaded file.
 * 
 * @package		application
 * @subpackage	request
 * @link          http://wiki.getheavy.info/index.php/Uploads
 */
class Upload
{
	/**
	 * File's name
	 * @var string
	 */
	public $name='';
	
	/**
	 * The mime type of file.
	 * @var string
	 */
	public $type='';
	
	/**
	 * Error number, if any.
	 * @var int
	 */
	public $error=0;
	
	/**
	 * The error message, if any.
	 * @var string
	 */
	public $error_message='';
	
	/**
	 * The size of the upload.
	 * 
	 * @var int
	 */
	public $size='';
	
	/**
	 * The uploads current path
	 * @var string
	 */
	public $file_name='';
	
	/**
	 * Constructor
	 *  
	 * @param string $name The file's name
	 * @param string $type The mime type of file
	 * @param string $tmp_name The temp name for the file
	 * @param int $error The error code, if any
	 * @param int $size The size of the file
	 */
	public function __construct($name,$type,$tmp_name,$error,$size)
	{
		$this->name=$name;
		$this->type=$type;
		$this->file_name=$tmp_name;
		$this->error=$error;
		
		switch($error)
		{
			case 1:
			case 2:
				$this->error_message='File too large.';
				break;
			case 3:
				$this->error_message='Partial upload.';
				break;
			case 4:
				$this->error_message='No file uploaded.';
				break;
			case 6:
				$this->error_message='Missing a temporary folder.';
				break;
			case 7:
				$this->error_message='Cannot write to disk.';
				break;
			case 8:
				$this->error_message='File upload stopped.';
				break;
		}
				
		$this->size=$size;
	}
	
	/**
	 * Fetches all of the uploaded files and returns an array of Upload
	 * 
	 * @return array An array of Upload
	 */
	public static function Files()
	{
		$files=array();
		
		foreach ($_FILES as $key => $file)
		{
			if (is_array($file['name']))
			{
				$count=count($file['name']);
				for($i=0; $i<$count; $i++)
					$files[$key][]=new Upload($file['name'][$i],$file['type'][$i],$file['tmp_name'][$i],$file['error'][$i],$file['size'][$i]);
			}
			else
				$files[$key][]=new Upload($file['name'],$file['type'],$file['tmp_name'],$file['error'],$file['size']);
		}
		
			
		return $files;
	}

	/**
	 * Moves the file to HeavyMetal's temp directory for processing.
	 * 
	 * @param string $filename The path to move to, if none specified, will copy to the app's temp directory.
	 * @return The file's new path/name
	 */
	public function move($filename=null)
	{
		if (!$filename)
			$filename=PATH_TEMP.$this->name;
			
		if (!move_uploaded_file($this->file_name,$filename))
			throw new Exception("Could not move temporary file to '$filename'.");
			
		if (!chmod($filename,0666))
			throw new Exception("Could not set permissions on '$filename'.");

		return $this->file_name=$filename;
	}

	/**
	 * Fetches the contents of the file.
	 * 
	 * @return mixed The contents of the file.
	 */
	public function contents()
	{
		return file_get_contents($this->file_name);
	}

	/**
	 * Deletes the upload.
	 */
	public function delete()
	{
		unlink($this->file_name);
	}
}