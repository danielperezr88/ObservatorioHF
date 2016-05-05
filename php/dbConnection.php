<?php
	if(!class_exists("sql_tools"))
		include "php/dbTools.php";
	if (!function_exists('StartsWith'))
		include "php/tools.php";

	$base = dirname(__FILE__);
	$dbhostname = "";
	$dbusername = "";
	$dbpassword = "";
	$dbdatabase = "";
	
	foreach (glob($base."/../config*.py") as $filename)
	{
		$lines = file_get_contents($filename);
		$hasDBInfo =  Contains($lines,'dbdatabase');
		if ($hasDBInfo)
		{
			$fileLines = file($filename);
			foreach ($fileLines as $numLine => $line)
			{
				$trimmed = trim($line);
				if ($trimmed != "")
				{ 
					list($key, $value) = split('=', $trimmed);
					$key = trim($key);
					$value = trim($value);
					if (StartsWith($key, 'dbhost')) $dbhostname = str_replace('"','',$value);
					else if (StartsWith($key, 'dbuser')) $dbusername = str_replace('"','',$value);
					else if (StartsWith($key, 'dbpassword')) $dbpassword = str_replace('"','',$value);
					else if (StartsWith($key, 'dbdatabase')) $dbdatabase = str_replace('"','',$value);
				}
			}
			//print($dbhostname."-".$dbusername."-".$dbpassword."-".$dbdatabase);
		}
	}
	if ($dbdatabase != "")
	{
		$conn = mysql_connect($dbhostname	, $dbusername, $dbpassword);
		if (!$conn) {
	    die('No se pudo conectar : ' . mysql_error());
		}
		$bd_seleccionada = mysql_select_db($dbdatabase,$conn);
		if (!$bd_seleccionada) {
    	die ('No se puede usar '.$dbdatabase.' : ' . mysql_error());
		}
		
		$sql_tools = new sql_tools($conn);
	}
?>