<?
/**
 * The main request dispatcher.
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


uses('system.app.dispatcher');
uses('system.app.http.http_request');

/**
 * HTTP Dispatcher
 * 
 * @package		application
 * @subpackage	dispatcher
 * @link          http://wiki.getheavy.info/index.php/Dispatcher
 */
class HTTPDispatcher extends Dispatcher
{
	
	private $query=null;  // Need this when dispatching internally for portlets (no $_GET present)
	
	/**
	 * Constructor 
	 * 
	 * @param $path
	 * @param $controller_root
	 * @param $view_root
	 * @param $use_routes
	 * @param $force_routes
	 */
	public function __construct($path=null,$controller_root=null,$view_root=null,$use_routes=true,$force_routes=false)
	{
		if ($path==null)
		{
			$path = (isset ($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @ getenv('PATH_INFO');
			$path = rtrim(strtolower($path), '/');
		}
		else
		{
			$query_pos = strpos($path,'?');
			if ($query_pos)
			{
				$query = substr($path,$query_pos+1);
				$this->query = new Query($query); 
				$path = substr($path,0,$query_pos); // remove query part for Dispatcher
			}
		}
		
		parent::__construct($path,$controller_root,$view_root,$use_routes,$force_routes);
	}

	/**
	 * @see sys/app/Dispatcher#build_request()
	 */
	public function build_request($base=null)
	{				
		return new HTTPRequest($this,null,$base,$this->segments,$this->query);
	}


	/**
	 * (non-PHPdoc)
	 * @see sys/app/Dispatcher#new_instance($path, $controller_root, $view_root, $use_routes, $force_routes)
	 */
	public function new_instance($path=null,$controller_root=null,$view_root=null,$use_routes=true,$force_routes=false)
	{
		$controller_root=($controller_root) ? $controller_root : $this->controller_root;
		$view_root=($view_root) ? $view_root : $this->view_root;
		return new HTTPDispatcher($path,$controller_root,$view_root,$use_routes,$force_routes);
	}
		
	/**
	 * @see sys/app/Dispatcher#transform($data, $req_type)
	 */
	public function transform(&$data, $req_type=null)
	{
		
		// fetch the view conf
		$viewconf=Config::Get('view');
		
		$default_engine=$viewconf->default;
		
		// set the default extension
		$extension=EXT;
		
		// if request type hasn't been specified
		// run it through the map to see if we get a hit.
		if ($req_type==null)
		{
			// default
			$req_type=$default_engine;
			
			try
			{
				foreach($viewconf->map as $item)
				{
					switch($item->test)
					{
						case 'server':
							$array=&$_SERVER;
							break;
						case 'get':
							$array=&$_GET;
							break;
						case 'post':
							$array=&$_POST;
							break;
						case 'env':
							$array=&$_ENV;
							break;
					}
					
					if (isset($array[$item->key]))
					{
						if ($item->matches)
						{
							if (preg_match("#{$item->matches}#",$array[$item->key]))
							{
								$req_type=$item->type;
								break;
							}
						}
						else
						{
							$req_type=$item->type;
							break;
						}
					}
				}
			}
			catch (ConfigInvalidFormatException $fex)
			{
				throw $fex;
			}
			catch (ConfigException $ex)
			{
				
			}
		}
		
		
		if ($this->view)
			$view_name=$this->view;
		else
			$view_name=strtolower($this->controller_path.$this->controller.'/'.$this->action);

		$conf=$viewconf->engines->{$req_type};
		if (!$conf)
			$conf=$viewconf->engines->{$default_engine};
		if (!$conf)
			throw new Exception("Your view.conf file is invalid.  Missing default engine.");
			
		if ($conf->extension)
			$extension=$conf->extension;
			
		$view_found=file_exists($this->view_root.$view_name.'.'.$req_type.$extension);
		
		// if we didn't find the view for the request type, try the default one
		if ((!$view_found) && ($req_type!=$viewconf->default) && (file_exists($this->view_root.$view_name.'.'.$viewconf->default.EXT)))
		{
			$req_type=$viewconf->default;
			$extension=EXT;
			$view_found=true;
		}
			
		if ($view_found==false)
			return '';
							
		if ($view_found)
		{		
			$viewclass=$conf->class;
			
			uses($conf->uses);
			$view=new $viewclass($view_name.'.'.$req_type,$data['controller'],$this->view_root);
			
			return $view->render($data);
		}		
	}
}
