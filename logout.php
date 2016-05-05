<?php
	include "php/tools.php";
	//onsubmit="return checkForm(this);"
	//session_start();
	$userData = GetLoggedUser();
	if($userData != array())
	{
		UnsetLoggedUser();
	}
	header("Location: index.php");
?>
