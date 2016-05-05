<?php

	$configFile = "";
	$pythonFile = "";
	// Read file config.py
	if (file_exists("config.py"))
	{
		$pythonFile = "config.py";
		$configFile = "config.txt";
	}
	else if (file_exists("templates/config.py"))
	{
		$pythonFile = "templates/config.py";
		$configFile = "templates/config.txt";
	}
	else
	{
		echo "config.py not found";
	}
	if ($pythonFile != "")
	{
		$pythonLines = file($pythonFile);
		$configLines = file($configFile);
		if(isset($_POST["action"]))
  	{
  		$modified = false;
  		$action = htmlspecialchars($_POST["action"]);
  		if ($action == "modify")
  		{
  			foreach ($configLines as $numLine => $line)
  			{
  			}
  			foreach ($pythonLines as $numLine => $line)
  			{
  				$trimmed = trim($line);
  				if (!StartsWith($trimmed, "#") && $trimmed != "") { 
	  				list($key, $value) = split('=', $trimmed);
						$key = trim($key);
						$value = trim($value);
						if (StartsWith($value, '"') && str_replace('"', '', $value) != $_POST[$key])
		  			{
		  				//echo $key ."=".$_POST[$key]."<br>";
		  				$pythonLines[$numLine] = $key ." = \"".$_POST[$key]."\"\r\n";
		  				$modified = true;
		  			}
		  		}
	  		}
  		}
  		if ($modified) file_put_contents($pythonFile, $pythonLines);
  	}
	}
	
?>
  <form action="edit_config.php" method="post">
  	<h3><?php echo basename($configFile); ?></h3>
  	<table>
  		<?php if ($configFile != "") foreach ($configLines as $line) { 
    		$trimmed = trim($line);
				if (!StartsWith($trimmed, "#") && !StartsWith($trimmed, "[") && $trimmed != "") { 
					list($key, $value) = split('=', $trimmed);
					$key = trim($key);
					$value = trim($value);
					?>
      <tr>
      	<td><label for="<?php echo $key; ?>"><?php echo $key; ?> =</label></td>
      	<?php if (StartsWith($value, '"')) { ?>
      		<td><input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>" size="30" value="<?php echo str_replace('"', '', $value); ?>" required="" style="width: 650px;"></td>
      	<?php } else { ?>
      		<td><?php echo $value; ?></td>
      	<?php } ?>
      </tr>
			<?php } } ?>
  	</table>
		<h3><?php echo basename($pythonFile); ?></h3>
    <table  border="0" >
    	<?php if ($pythonFile != "") foreach ($pythonLines as $line) { 
    		$trimmed = trim($line);
				if (!StartsWith($trimmed, "#") && $trimmed != "") { 
					list($key, $value) = split('=', $trimmed);
					$key = trim($key);
					$value = trim($value);
					?>
      <tr>
      	<td><label for="<?php echo $key; ?>"><?php echo $key; ?> =</label></td>
      	<?php if (StartsWith($value, '"')) { ?>
      		<td><input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>" size="30" value="<?php echo str_replace('"', '', $value); ?>" required="" style="width: 450px;"></td>
      	<?php } else { ?>
      		<td><?php echo $value; ?></td>
      	<?php } ?>
      </tr>
			<?php } } ?>
    </table>
    <input type="submit" value="Submit">
    <input type="hidden" name="action" value="modify">
  </form>
