#!/usr/bin/php
<?php

// include heavy metal's shell system
$dirname=dirname(__FILE__);

require_once($dirname.'/sys/sys_shell.php');

// init the framework
init($dirname.'/');

uses('sys.app.config');
uses('sys.app.shell.shell_dispatcher');
uses('sys.app.shell.sys_shell_dispatcher');

// load the environment
Config::LoadEnvironment();

//fork();


try
{
	$dispatcher=new ShellDispatcher();
	exit($dispatcher->dispatch());
}
catch (Exception $ex)
{
	try
	{
		$dispatcher=new SysShellDispatcher();
		exit($dispatcher->dispatch());
	}
	catch(Exception $ex2)
	{
		echo $ex2->getMessage()."\n";
	}
}