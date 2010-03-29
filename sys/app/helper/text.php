<?
function seo_post_url_title($string=null)
{
	// unconvert entities
	$cleaned = htmlspecialchars_decode($string);

	// ditch punctuation
	$cleaned = preg_replace('/[^a-zA-Z0-9-\s]/', '', $cleaned);
	
	// plusses 2 spaces
	$cleaned = str_replace(' ', '+', $cleaned);
	
	return $cleaned;
}