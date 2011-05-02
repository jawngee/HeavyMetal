<?php
class HelpController extends Controller
{
	/**
	 * Displays detailed help about a HeavyMetal shell command.
	 * 
	 * @usage ./metal help your/command/uri
	 * @param $uri The URI of the shell command to display help about.
	 */
	public function index($uri)
	{
		try
		{
			try 
			{
				$s=new ShellDispatcher($uri);
				$f=$s->find();
			} 
			catch (Exception $ex)
			{
				$s=new SysShellDispatcher($uri);
				$f=$s->find();
			}
		}
		catch (Exception $ex)
		{
                vomit($ex->getTrace());
			echo "Could not find a suitable controller for $uri - are you sure you got it right?";
		}
		
		$method=new ReflectionMethod($f['classname'],$f['found_action']);
		$help=$method->getDocComment();
		$help=explode("\n",$help);
		
		$description='';
		$switches=array();
		$params=array();
		$usage='';
		
		foreach($help as $line)
			if ((strpos(trim($line,"\t \n"),'/**')===FALSE) && (strpos(trim($line,"\t \n"),'*/')===FALSE))
			{
				$line=trim($line,"\t* ");
				if (strpos($line,'@')===FALSE)
					$description.=$line;
				else
				{
					if (strpos($line,'@usage')===0)
					{
						$usage=substr($line, 6);
					}
					else
					{
						$matches=array();
						preg_match_all('#([@a-z0-9]*) ([$a-z0-9]*) (.*)#', $line, $matches);
						
						switch($matches[1][0])
						{
							case '@switch':
								$switches[$matches[2][0]]=$matches[3][0];
								break;
							case '@param':
								$params[trim($matches[2][0],'$')]=$matches[3][0];
								break;
						}
					}
				}
			}
			
		return array(
			'description' => $description,
			'usage' => $usage,
			'switches' => $switches,
			'params' => $params
		);
	}
	
	private function findcontroller($root,$uri,$folder,$path,&$controllers)
	{
		$path='/'.trim($path,'/').'/'.$folder;
		$root.=ucfirst($folder);
		
		if (!file_exists($path))
			return;
		
		$d = dir($path);
		
		while (false !== ($entry = $d->read())) 
		{
			if (strpos($entry,'.php')>0)
			{
				$name=$root.ucfirst(str_replace('.php','',$entry)).'Controller';
				
				if (!$uri)
					$uri=str_replace('.php','',$entry);
				
				$controllers[$name]=array(
					'file'=>'/'.trim($path,'/').'/'.$entry,
					'uri' => $uri
				);
			}
			else if (($entry!='..') && ($entry!='.'))
			{
				$this->findcontroller($root,$entry,$entry,$path,$controllers);				
			}
		}
		
		$d->close();		
	}
	
	/**
	 * Lists all of the available commands
	 */
	public function all()
	{
		$controllers=array();
		$this->findcontroller('','','',PATH_SYS.'shell/controller',$controllers);
		$this->findcontroller('','','',PATH_APP.'shell/controller',$controllers);
		$methods=array();
		
		foreach($controllers as $class => $info)
		{
			require_once $info['file'];
            $r=new ReflectionClass($class);
			$ms=$r->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach($ms as $m)
			{
				$name=$m->getShortName();
				if (strpos($name,'__')!==0)
				{
					if ($name!='index')
						$uri=$info['uri'].'/'.$name;
					else
						$uri=$info['uri'];
						
					$d=$this->index($uri);
					
					$methods[$uri]=$d;
				}
			}
		}

		return array('methods'=>$methods);
	}
}