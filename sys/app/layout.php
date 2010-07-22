<?
/**
 * Layout for rendering the layout page with a controller.
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

uses('sys.app.view');
uses('sys.app.dynamic_object');

/**
 * Layout for rendering the layout page with a controller.
 * 
 * @package		application
 * @subpackage	view
 * @link          http://wiki.getheavy.info/index.php/Layouts
 */
 class Layout extends View
 {
 	private static $_master=null;
 	
	/** The page title */
 	public $title='';
 	
 	/** The page description for SEO */
 	public $description='';
 	
 	/** The page optimization */
 	public $optimize='';
 	
 	/** list of styles */
 	public $styles=array();
 	
 	/** list of includes (js files) */
 	public $includes=array();
 	
 	/** blocks of control generated script */
 	public $blocks=array();
 	
 	/** document links */
 	public $links=array();
 	
 	/** rendered content for displaying within the layout */
 	public $content=array();
 	
 	/**
 	 * Constructor
 	 * 
 	 * @param string $view The view to use for this layout
 	 */
 	public function __construct($title,$description,$view,$optimize='')
 	{
 		parent::__construct($view,null,PATH_APP.'layout/');
 		
 		$this->title=$title;
 		$this->description=$description;
 		
 		// optimization options
 		$this->optimize=new DynamicObject();
 		foreach(split(",", strtoupper($optimize)) as $id)
 			$this->optimize->{$id}=true;
 		
 		if (self::$_master==null)
 			self::$_master=$this;
 	}
 	
 	/**
 	 * Retrieves the instance of the master layout.
 	 * @return Layout
 	 */
 	public static function MasterLayout()
 	{
 		return self::$_master;
 	}
 	
 	/**
	 * Adds a style sheet to the layout
	 */
 	public function add_style($style,$dynamic=false)
 	{
 		$this->styles[$style]=$style;
 	}
 	
 	/**
 	 * Adds an include (js file) to the layout
 	 */
 	public function add_include($include)
 	{
 		$this->includes[$include]=$include;
 	}
 	
 	/**
 	 * Adds a document link to the layout
 	 */
 	public function add_doc_link($rel,$type,$title,$link)
 	{
 		$this->links[$title]=array($rel,$type,$title,$link);
 	}
 	
	/**
	 * Registers a block of control generated script in the layout
	 */
 	public function register_block($name,$block)
 	{
 		$this->blocks[$name]=$block;
 	}
 	
	/**
	 * Adds content to the layout for a specified target area within the layout.
	 */
 	public function add_content($target,$content)
 	{
 		$this->content[$target][]=$content;
 	}

	/**
	 * Returns all of the content in the content array
	 * 
	 * @return string All of the content.
	 */ 	
 	public function all_content()
 	{
 		$result='';
 		foreach($this->content as $target)
 			foreach($target as $content)
 				$result.=$content;

 		return $result;
 	}
 	
 	
 	/**
 	 * Writes out any defined styles
 	 * 
 	 * @return string The rendered list of styles
 	 */
 	public function write_styles()
 	{
		$result="";
		
		foreach($this->styles as $style)
		{
			if (strpos($style,'http://')!==0)
				$style='/css/'.$style.'.css';

			$result.="\t\t<link rel=\"stylesheet\" href=\"$style\" type=\"text/css\" />\n";
		}
			
		return $result;
 	}
 	
 	
 	/**
 	 * Writes out any included scripts
 	 * 
 	 * @return string The rendered list of includes
 	 */
 	public function write_includes()
 	{
		$result="";
		
		foreach($this->includes as $key => $include)
		{
			if (strpos($include,'http://')!==0)
				$include='/js/'.$include.'.js';
				
			$result.="\t\t<script src=\"$include\" type=\"text/javascript\"></script>\n";
		}
			
		return $result;
 	}
 	
 	/**
 	 * Writes out any content
 	 * 
 	 * @return string The requested content, or an empty string if no content found.
 	 */
 	public function write_content($id)
 	{
 		if ($id=='all')
 			return $this->all_content();
 		else if(isset($this->content[$id]))
 		{
 			$result='';

 			foreach($this->content[$id] as $content)
 				$result.=$content;

 			return $result;
 		}
 		else
 			return '';
 	}
 	
 	/**
 	 * Writes out any defined client script blocks
 	 * 
 	 * @return string The rendered list of client script blocks
 	 */
 	public function write_blocks()
 	{
		$result="";
		
		foreach($this->blocks as $key => $block)
			$result.="\t\t<script type='text/javascript'>\n$block\n</script>\n";
	
		return $result;
 	}
 	
 	
 	/**
 	 * Parses out layout tags from the view's rendered output, replacing them with the rendered tag's contents
 	 * 
 	 * @param string $rendered The view's rendered output that may contain layout tags
 	 */
 	protected function parse_other_tags(&$rendered)
 	{
 		// extract layout tags from rendered source
 		$layouts=array();
 		$layout_tags = "#<layout:(\w+)\s+([^/>]*)([/]*)>#";						// extracts layout tags
		
 		while(preg_match($layout_tags,$rendered,$layouts,PREG_OFFSET_CAPTURE)==1)
 		{
 			$tag=$layouts[1][0];
 			$full_tag=$layouts[0][0];
 			$attributes=$layouts[2][0];
 			
 			switch($tag)
 			{
 				case 'styles':
 					$rendered=str_replace($full_tag,$this->write_styles(),$rendered);
	 				break;
 					
 				case 'includes':
	 				$rendered=str_replace($full_tag,$this->write_includes(),$rendered);
	 				break;
 				
 				case 'blocks':
 					$rendered=str_replace($full_tag,$this->write_blocks(),$rendered);
 					break;
 				
 				case 'content':
 					$attrs=array();
					preg_match_all(View::REGEX_ATTRIBUTE,trim($attributes),$attrs,PREG_SET_ORDER);
					
					$id=null;
					$wrap=false;
					$use_id=false;
					
					foreach($attrs as $attr)
						switch(strtolower($attr[1]))
	 					{
	 						case 'id':
	 							$id=$attr[3];
	 							break;
	 						case 'wrap':
	 							$wrap=$attr[3];
	 							break;
	 						case 'use_id':
	 							$use_id=($attr[3]!='false');
	 							break;
	 						case 'class':
	 							$class_name=$attr[3];
	 							break;
	 					}
	 					
					if ($id==null)
						trace('Layout',"No 'id' attribute supplied for layout content tag.");
						
					$text=$this->write_content($id,$rendered);
					
					if (($wrap) && ($text!=''))
					{
						$text="<$wrap".(($use_id==true) ? " id='$id' ":"").((isset($class_name)) ? " class='$class_name' ":"").">\n".$text;
						$text.="\n</$wrap>\n";
					}
						
					$rendered=str_replace($full_tag,$text,$rendered);
						
 					break;
 				
 				default:
 					$rendered=str_replace($full_tag,'',$rendered);
 			}
 		}
 	}
 }
