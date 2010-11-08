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
uses('system.app.shell.shell_request');

/**
 * Shell Dispatcher
 * 
 * @package		application
 * @subpackage	dispatcher
 * @link          http://wiki.getheavy.info/index.php/Dispatcher
 */
class ShellDispatcher extends Dispatcher
{
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
		$args=parse_args();
		$switches=parse_switches();
		
		$path=($path) ? $path : $args[0];
		array_shift($args);

		$controller_root=($controller_root) ? $controller_root : PATH_APP.'shell/controller/';
		$view_root=($view_root) ? $view_root : PATH_APP.'shell/view/';
		
		parent::__construct($path,$controller_root,$view_root,$use_routes,$force_routes);

		$this->segments=$args;
	}

	/**
	 * @see sys/app/Dispatcher#build_request()
	 */
	public function build_request($root=null)
	{
		if (!$root)
			$root=$this->path;
			
		return new ShellRequest($this,null,$root,$this->segments,$this->query);
	}


	/**
	 * (non-PHPdoc)
	 * @see sys/app/Dispatcher#new_instance($path, $controller_root, $view_root, $use_routes, $force_routes)
	 */
	public function new_instance($path=null,$controller_root=null,$view_root=null,$use_routes=true,$force_routes=false)
	{
		$controller_root=($controller_root) ? $controller_root : $this->controller_root;
		$view_root=($view_root) ? $view_root : $this->view_root;
		return new ShellDispatcher($path,$controller_root,$view_root,$use_routes,$force_routes);
	}
		
	/**
	 * @see sys/app/Dispatcher#transform($data, $req_type)
	 */
	public function transform(&$data, $req_type=null)
	{
		
		if ($this->view)
			$view_name=$this->view;
		else
		{
			$view_name=strtolower($this->controller_path.$this->controller.'/'.$this->action);
		}
		
		if (!$req_type)
			$req_type='html';
			
		
		$viewconf=Config::Get('view');
			
		$conf=$viewconf->engines->{$req_type};
		
		
		if (!$conf)
			throw new Exception("Your view.conf file is invalid.  Missing default engine.");

		$extension=($conf->extension) ? $conf->extension : '.php';
			
		$view_found=file_exists($this->view_root.$view_name.'.'.$req_type.$extension);
		
		if ((!$view_found) && (preg_match('#(post|put|get|delete)_.*#', $view_name)))
		{
			$view_name=preg_replace('#(?:post|put|get|delete)_(.*)#', '$1', $view_name);
			$view_found=file_exists($this->view_root.$view_name.'.'.$req_type.$extension);
		}
		
		// if we didn't find the view for the request type, try the default one
		if ((!$view_found) && ($req_type!='html') && (file_exists($this->view_root.$view_name.'.html'.EXT)))
		{
			$req_type='html';
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
			
			$data['input']=$data['controller']->request->input;
			return $view->render($data);
		}		
	}
}