<?php
	$dbhost = 'localhost';
	$dbuser = 'osuserve_dbman';
	$dbpass = 'pass goes here';
	$database = 'osuserver';
	
	$db = new PDO('mysql:host='.$dbhost.';dbname='.$database.';charset=utf8', $dbuser, $dbpass);
	//$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	//$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
?>
