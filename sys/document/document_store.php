<?
/**
 *
 * Copyright (c) 2009, Jon Gilkison and Trunk Archive Inc.
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
 *
**/

/**
 * Abstract representation of a document store.
 */
abstract class DocumentStore
{
    /** List of initialized databases */
	private static $_stores=array();

    /**
     * DocumentStore constructor
     * @param  $dsn The connection DSN/URI
     */
    public function __construct($dsn)
    {
        
    }

    /**
     * Fetches a document store with a given name.  These are cached in a static cache.
     * 
     * @static
     * @throws Exception
     * @param  $name
     * @return DocumentStore
     */
    public static function Get($name)
    {
        if (isset(self::$_stores[$name]))
			return self::$_stores[$name];
		else
		{
			$conf=Config::Get('doc');
			if (isset($conf->items[$name]))
			{
				$dsn=$conf->items[$name]->dsn;

				$matches=array();
				if (preg_match_all('#([a-z0-9]*)://(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];

					uses("sys.document.$driver.$driver"."_document_store");

					$class=$driver."DocumentStore";
					$db=new $class($dsn);

					self::$_stores[$name]=$db;
					return $db;
				}
			}
            
			throw new Exception("Cannot find document store named '$name' in Config.");
		}
    }

    /**
     * @abstract
     * @param  $document
     * @return Finder
     */
    abstract function create_finder($document);

    abstract function save($document);
    abstract function remove($document);
    abstract function query($document,$query,$sorts=null,$offset=0,$limit=0);
    abstract function count($document,$query);
}
