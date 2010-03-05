<?
abstract class DebugModule
{
	public $title;
	public $template;
	public $description;
	
	public function __construct()
	{
	}
	
	abstract function render();
}