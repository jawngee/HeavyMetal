<?php
uses('system.app.session');

class Localization
{
	private static $_instances=null;
	private static $_preferred=null;
	
	/**
	 * Fetches a named database from the connection pool.
	 *
	 * @param string $name Name of the database to fetch from the connection pool.
	 * @return Database The named database.
	 */
	public static function Get($name)
	{
		if (isset(self::$_instances[$name]))
			return self::$_instances[$name];
		else
		{
			$conf=Config::Get('i18n/'.$name);
			
			if ($conf)
			{
				self::$_instances[$name]=$conf;
				return $conf;
			}
				
			throw new Exception("Cannot find localization conf named '$name'.");
		}
	}
	
	public static function ParsePreferred()
	{
		if (self::$_preferred==null)
		{
//			$session=Session::Get();
//			if (($session->langs) && (is_array($session->langs)))
//			{
//				self::$_preferred=$session->langs;
//				return;
//			}

			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

    		if (count($lang_parse[1])) 
    		{
    			for($i=0; $i<count($lang_parse[1]); $i++)
    				$lang_parse[1][$i]=array_shift(explode('-',$lang_parse[1][$i]));
    				
        		$langs = array_combine($lang_parse[1], $lang_parse[4]);
        		foreach ($langs as $lang => $val)
        		{
            		if ($val === '') $langs[$lang] = 1;
        		}
        	}

        	arsort($langs, SORT_NUMERIC);
        	
        	foreach($langs as $lang=>$value)
        		$thelangs[]=$lang;

//        	$session->langs=$thelangs;
//        	$session->save();

        	self::$_preferred=$thelangs;
		}		
	}
	
	public static function Localize($name,$identifier)
	{
		$locale=Localization::Get($name);
		for($i=0; $i<count(self::$_preferred); $i++)
		{
			$lang=self::$_preferred[$i];
			if ($locale->{$identifier}->{$lang})
				return $locale->{$identifier}->{$lang};
		}
	}	
}

function localize($name,$identifier)
{
	return Localization::Localize($name,$identifier);
}

Localization::ParsePreferred();