<?
/**
 * The data channel is 
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
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
 */

uses('system.data.filter');

class Channel
{
	private static $_cache=array();
	
	public static function Get($channel)
	{
		if (isset(self::$_cache[$channel]))
			return self::$_cache[$channel];
		
		uses('channel.'.$channel);
		
		$classname=str_replace('_','',$channel)."Channel";
		if (class_exists($classname))
		{
			$class=new $classname();
			
			self::$_cache[$channel]=$class;
			
			return $class;
		}
		
		throw new Exception("Channel '$channel' does not exist.");
	}
	
	public static function GetDatasource($datasource,$offset=null,$limit=null,&$count=null)
	{
		// format for datasource is:
		// controller://path/path?arg1=val&q=asdads asd ad ad&arg=[123,232,123]
		// channel://channel/datasource?arg1=val&q=asdads asd ad ad&arg=[123,232,123]
		// model://profiles/profile_view?arg1!=val&q=asdads asd ad ad&arg=[123,232,123]
		
		$matches=array();
		if (preg_match_all('#^([^:]*):\/\/([^?]*)(.*)$#',$datasource,$matches))
		{
			switch($matches[1][0])
			{
				case 'controller':
					return Dispatcher::Call($matches[2][0]);
				case 'model':
					$parsed=explode('.',$matches[2][0]);
					if (count($parsed)==2)
					{
						$filter=filter($matches[2][0]);
							
						if ($offset)
							$filter->offset=$offset;
							
						if ($limit)
							$filter->limit=$limit;
							
						if ($matches[3][0]!='')
							$filter->parse(trim($matches[3][0],'?'));
	
                        if ($count==null)
    						$count=$filter->get_count();
		
                        return $filter->find(); 
					}
					
					return null;
				case 'channel':
					$parsed=explode('/',$matches[2][0]);
					$channel=Channel::Get($parsed[0]);
					$query=trim($matches[3][0],'?');
					
					$args=array();
					
					if ($query!="")
					{
						$items=explode('&',$query);
						foreach($items as $item)
						{
							$element=explode('=',$item);
							$args[trim($element[0])]=trim($element[1]);						
						}
						
					}
						
					return $channel->datasource($parsed[1],$offset,$limit,$count,$args);
					
			}
		}
	}
	
  	/**
 	 * Requests a datasource from the controller
 	 * 
 	 * @param string $datasource The name of the datasource to get
 	 * @param int $offset The offset into the result-set to start fetching results from
 	 * @param int $limit The maximum number of results to return in the dataset
 	 * 
 	 * @return mixed The datasource, or NULL if none.
 	 */
 	public function datasource($datasource,$offset=null,$limit=null,&$count=null,$args=null)
 	{
 		try
 		{
 			$func=new ReflectionMethod($this,$datasource);
 		} 
 		catch(Exception $ex)
 		{
 			trigger_error($ex->getMessage(),E_USER_WARNING);
 			return null;
 		}
 		
		$assembled = array();
		
		$funcargs=$func->getParameters();
		
    	foreach ($funcargs as $index => $arg)
		{
			$argname = $arg->getName();
			if (array_key_exists($argname, $args))
				$assembled[] = $args[$argname];
        	else
        	{
        		switch($argname)
        		{
        			case 'offset':
        				$assembled[]=$offset;
        				break;
        			case 'limit':
        				$assembled[]=$limit;
        				break;
        			case 'count':
        				$assembled[]=&$count;
        				break;
        			default:
        				if ($arg->isDefaultValueAvailable()) 
			            	$assembled[] = $arg->getDefaultValue();
			            else
        	  				$assembled[]=null;
        				break;
        		}
        	}
        }
        
 		return call_user_func_array(array($this,$datasource),$assembled);
 	}
}