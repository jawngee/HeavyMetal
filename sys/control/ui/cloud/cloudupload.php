<?
/**
 * @desc		
 * 
 * @link		http://heavydox.massifycorp.com/Massify_Controls/Flash_Upload_Control
 * @author		Nick Castrop <nick@massifycorp.com>
 * @date		Sep 29, 2007
 * @time		5:03:25 PM
 * @file		flashupload.php
 * @copyright   Copyright (c) 2007 massify.com, all rights reserved.
 */
 
uses('system.app.control');
uses('system.app.view');
uses('system.app.template');
uses('system.app.request.input');
uses('system.app.config');
 
class CloudUploadControl extends Control
{
	protected $conf=null;

	public $id=null;						/** used as the js var */
	public $app_id=null;
	public $secret=null;
	public $form="ui/sys/upload/form"; 			/** item template */
	public $container="ui/sys/upload/container";		/** container template */
	public $script="ui/sys/upload/script";		/** container template */
	public $path=null;						/** where the flash is sending */
	public $max_files=10;
	public $file_formats="*.*";
    public $forced_formats="";
    public $formats="profile,media,docs,video";
    public $queue_limit=20;
    public $allowed_filesize=300;
    public $success_link='';
    public $success_title='';
    
    public $fields=array();
	
	public function init()
	{
		parent::init();
		
		$conf=Config::Get('upload');
		$this->conf=$conf->{$this->type};
		
		$this->app_id=($conf->app_id) ? $conf->app_id : $this->app_id;
		$this->secret=($conf->app_id) ? $conf->secret : $this->secret;

		$this->auth=$this->session->build_session();

		$this->forced_formats=($this->conf->forced_formats) ? $this->conf->forced_formats : $this->forced_formats; 
		$this->formats=($this->conf->formats) ? $this->conf->formats : $this->formats; 
		$this->queue_limit=($this->conf->queue_limit) ? $this->conf->queue_limit : $this->queue_limit; 
		$this->max_files=($this->conf->max_files) ? $this->conf->max_files : $this->max_files; 
		$this->file_formats=($this->conf->file_formats) ? $this->conf->file_formats : $this->file_formats; 
		$this->allowed_filesize=($this->conf->allowed_filesize) ? $this->conf->allowed_filesize : $this->allowed_filesize; 
		$this->form=($this->conf->form) ? $this->conf->form : $this->form; 
		$this->script=($this->conf->script) ? $this->conf->script : $this->script; 
		$this->container=($this->conf->container) ? $this->conf->container : $this->container;
		
		if($this->content && $this->content->fields)
    		foreach($this->content->fields->field as $item)
    			$this->fields[(String)$item['name']]=(String)$item['value'];
			
		$sig=sign(array('app_id' => $this->app_id,'formats' => $this->formats,'forced_formats' => $this->forced_formats),$this->secret);
		$this->time=$sig['time'];
		$this->signature=$sig['signature'];
	}
 	
	function build()
	{
		$rendered='';		
				
		if ($this->script!='none')
		{
			$t=new Template($this->script);
			$rendered.=$t->render(array('control'=>$this));
		}
		
		if ($this->form!='none')
		{
			$t=new Template($this->form);
			$rendered.=$t->render(array('control'=>$this));
		}
		
		if ($this->container!='none')
		{
			$t=new Template($this->container);
			$rendered.=$t->render(array('control'=>$this));
		}
		return $rendered;
	}
}