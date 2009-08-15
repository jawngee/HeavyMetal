<?php 

$json=function_exists('json_encode') ? 'Installed' : 'Missing';
$mcrypt=function_exists('mcrypt_encrypt') ? 'Installed' : 'Missing';
$mhash=function_exists('mhash') ? 'Installed' : 'Missing';
$pcntl=function_exists('pcntl_fork') ? 'Installed' : 'Missing';
$simplexml=function_exists('simplexml_load_string') ? 'Installed' : 'Missing';
$syck=function_exists('syck_load') ? 'Installed' : 'Missing';
$memcached=function_exists('memcache_pconnect') ? 'Installed' : 'Missing';
$pgsql=function_exists('pg_query') ? 'Installed' : 'Missing';
$apc=function_exists('apc_store') ? 'Installed' : 'Missing';
$sqlite=function_exists('sqlite_exec') ? 'Installed' : 'Missing';


?>

Testing environment for compatibility with HeavyMetal....

Required Extensions
==========================
JSON		<?php echo $json; ?>

MCrypt		<?php echo $mcrypt; ?>

SimpleXML	<?php echo $simplexml; ?>

Syck		<?php echo $syck; ?>

Optional Extensions
==========================
pcntl		<?php echo $pcntl; ?>

Memcached	<?php echo $memcached; ?>

PGSQL		<?php echo $pgsql; ?>

APC		<?php echo $apc; ?>

SQLite		<?php echo $sqlite; ?>

