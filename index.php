<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8;" />
</head>
<?php
	include "header.php";
	include "php/tools.php";
	include "php/dbConnection.php";
	
	//session_start();

	$userData = GetLoggedUser();
	if($userData == array())
	{
		include "login.php";
	}
	else
	{
		include "menu.php";
	}
	//session_destroy();
?>
</html>
