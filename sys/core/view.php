<?php
/**
 * Utility functions for rendering templates
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
 * @subpackage	  core
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


/**
 * Loads the content of a view (any old php file) from file.
 * 
 * @param string $view The full path to the view.
 * @return The contents of the view file.
 */
function get_view($view)
{
	$contents=file_get_contents($view.EXT);
	$contents=preg_replace('#<\s*php\s*\:loop\s*for\s*=\s*"\s*([^\'\"]*)\s*"\s*as\s*=\s*"\s*(.*)\s*"\s*>\s*(.*)\s*<\s*\/\s*php\s*:\s*loop\s*>#','<? foreach($1 as $2):?>$3<?endforeach;?>',$contents);
    $contents=preg_replace("|\{{2}([^}]*)\}{2}|is",'<?=$1?>',$contents);
    $contents=preg_replace("|\{{2}(.*)\}{2}|is",'<?=$1?>',$contents); // for closures.
    return $contents;
}

/**
 * Renders a php fragment
 * 
 * @param string $fragment The fragment of PHP code to render
 * @param array $data Variables to extract before rendering the fragment. 
 */
function render_fragment($fragment, &$data)
{
  	if ($data!=null)
		extract($data);

	ob_start();		
	eval("?>".trim($fragment));
	$result=ob_get_contents();
	ob_end_clean();
	
	return $result;
}

/**
 * Renders a view
 * 
 * @param string $view The full path to the view to render
 * @param array $data The data to pass into the view
 * @return string The rendered view
 */
function render_view($view,&$data)
{
	$contents=get_view($view);
	return render_fragment($contents,$data);
}


