<?
abstract class GridManager
{
	private static $_managers=array();
	
	/**
	 * Fetches a named database from the connection pool.
	 *
	 * @param string $name Name of the database to fetch from the connection pool.
	 * @return Database The named database.
	 */
	public static function Get($name)
	{
		if (isset(self::$_managers[$name]))
			return self::$_managers[$name];
		else
		{
			$conf=Config::Get('cloud');

			if (isset($conf->dsn->items[$name]))
			{
				$dsn=$conf->dsn->items[$name]->grid;
				
				$matches=array();
				if (preg_match_all('#([a-z0-9]*)://([^@]*)@(.*)#',$dsn,$matches))
				{
					$driver=$matches[1][0];
					$auth=$matches[2][0];
					$secret=$matches[3][0];
					
					uses('system.cloud.driver.grid.'.$driver);
					
					$class=$driver."Driver";
					$mgr=new $class($auth,$secret);
					
					self::$_managers[$name]=$mgr;
					return $mgr;
				}
			}

			throw new Exception("Cannot find queue named '$name' in Config.");
		}
	}
}