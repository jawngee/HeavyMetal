#!/usr/bin/php
<?php 
$json=function_exists('json_encode') ? 'Installed' : 'Missing';
$mcrypt=function_exists('mcrypt_encrypt') ? 'Installed' : 'Missing';
$mhash=function_exists('mhash') ? 'Installed' : 'Missing';
$pcntl=function_exists('pcntl_fork') ? 'Installed' : 'Missing';
$simplexml=function_exists('simplexml_load_string') ? 'Installed' : 'Missing';
$yaml=function_exists('yaml_parse') ? 'Installed' : 'Missing';
$memcached=function_exists('memcache_pconnect') ? 'Installed' : 'Missing';
$pgsql=function_exists('pg_query') ? 'Installed' : 'Missing';
$apc=function_exists('apc_store') ? 'Installed' : 'Missing';
$sqlite=function_exists('sqlite_exec') ? 'Installed' : 'Missing';
$sqlite3=class_exists('SQLite3') ? 'Installed' : 'Missing';
$mongo=class_exists('Mongo') ? 'Installed' : 'Missing';

?>

Testing environment for compatibility with HeavyMetal....

Required Extensions
==========================
JSON		<?php echo $json; ?>

MCrypt		<?php echo $mcrypt; ?>

MHash		<?php echo $mhash; ?>

SimpleXML	<?php echo $simplexml; ?>

YAML		<?php echo $yaml; ?>


Optional Extensions
==========================
pcntl		<?php echo $pcntl; ?>

Memcached	<?php echo $memcached; ?>

PGSQL		<?php echo $pgsql; ?>

APC		<?php echo $apc; ?>

SQLite		<?php echo $sqlite; ?>

SQLite3		<?php echo $sqlite3; ?>

Mongo		<?php echo $mongo; ?>

