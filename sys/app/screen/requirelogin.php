<?
/**
 * Require Login
 */

class RequireLoginScreen extends Screen
{
	public function before($controller,$metadata,&$data,&$args)
 	{	
 		if ($controller->session->id==null)
 		{
 			/*TODO: report 403 */
 			if($controller->ajax)
 				die;

			redirect(SITE_URL."/?login&r=".PORTFOLIO_URL);
 		}
 	}
 	
}
