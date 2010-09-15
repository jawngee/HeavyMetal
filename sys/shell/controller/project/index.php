<?php
uses('system.app.view');

class ProjectIndexController extends Controller
{
	public function create($app)
	{
		// force them to enter their password
		`sudo ls`;
		$user=trim(`whoami`);
		
		if ($user=='root')
		{
			echo "Please do not run as root.\n\n";
			die;
		}
		
		if (!$app)
			throw new BadRequestException();
			
		$root=explode('/',PATH_ROOT);
		array_pop($root);
		array_pop($root);
		$root=implode('/',$root);
		
		$path=$root.'/'.$app;
		
		if (!file_exists($path))
		{
			echo "Creating directory. ";
			`sudo mkdir $path`;
			echo "Done.\n";
			
			echo "Setting permissions. ";
			`sudo chown -R $user:$user $path`;
			echo "Done.\n";
		}
		
		echo "Copying application files. ";
		`cp -R app $path`;
		`cp metal $path`;
		`cp -R pub $path`;
		echo "Done.\n";
			
		echo "Linking HeavyMetal system. ";
		
		$sys=PATH_ROOT.'sys';
		$nsys=$path.'/sys';
		`ln -s $sys $nsys`;
		
		$mpath=$path.'/metal';
		$mtarget='/usr/local/bin/'.$app;
		`sudo ln -s $mpath $mtarget`;
		`sudo chmod a+x $mtarget`;
		
		echo "Done.\n";
		
		if ($this->request->input->server)
		{
			switch($this->request->input->server)
			{
				case 'apache2':
					echo "Setting up apache. ";
					
					$domain=($this->request->input->domain) ? $this->request->input->domain : $app;		
					$view=new View('apache2.conf',$this,PATH_SYS.'shell/view/project/');
					$rendered=$view->render(array('domain'=>$domain, 'path'=>$path));
					
					$conf=$domain.'.conf';
					$tmp=PATH_ROOT.$conf;
					$apachedir='/etc/apache2/sites-available/'.$conf;
					
					file_put_contents($tmp, $rendered);
					`sudo mv $tmp $apachedir`;
					`sudo a2ensite $conf`;
					
					echo "Done.\n";
						
					echo "Restarting apache. ";
					`sudo apache2ctl restart`;
					echo "Done.\n";
					break;
				default:
					echo 'Unknown server type: '.$this->request->input->server;
					break;
			}
		}
	
		echo "\n";
		echo "Your application has been created.  You can access it via the shell by typing '$app' followed by a command, or '$app commands/show' to display a list! \n";

	}
}