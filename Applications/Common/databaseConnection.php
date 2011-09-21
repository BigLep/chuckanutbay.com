<?php
	require_once("setUpEnvironment.php");
	$dbSettings = getDatabaseSettings();
	$dbConnection = mysql_connect($dbSettings["host"], $dbSettings["login"], $dbSettings["password"])
		or die('Could not connect to the database: ' . mysql_error());
	mysql_select_db($dbSettings["name"]) or die("Could not select the " . $dbSettings["name"] . " database.");
?>