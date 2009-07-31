<?
/**
 * Ajax View
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

uses('system.app.view');

/**
 * Ajax View
 * 
 * @package       application
 * @subpackage    view
 * @link          http://wiki.getheavy.info/index.php/Ajax_Views
 */
 class MappedView extends View
 {
  	/**
 	 * Renders and displays the multiview.
 	 */	
 	public function render($data=null, $subview=false)
 	{
 		if ($data!=null)
 			$this->data=$data;
 		else
 			$this->data=array();
 		
 		$this->data['layout']=$this;
 		
 		$result=get_view($this->base_path.$this->view_name);
 		
 		$this->parse_includes($result);
 		$this->parse_nestedcontrols_cdata($result);
 		
 		$this->data['_extracted_content']=$this->_extracted_content;
		
 		$result=render_fragment($result,$this->data);

		$this->parse_layout($result,$subview);
 		$this->parse_subviews($result);
 		$this->parse_other_tags($result);
 		$this->parse_targets($result);
 		$this->parse_uses($result);
 		$this->parse_controls($result);
 		$this->parse_nestedcontrols($result);

		if ($this->layout!=null)
 		{
 			$this->layout->add_content($this->target,$result);
 			$result=$this->layout->render($data);
 			
 		}
 		
 		return $result;
   	}
 }