<?php
  include "php/tools.php";
  
  $configFile = "";
  
  $dir = "searchs";
  
  if (file_exists($dir."/config.txt"))
  {
    $configFile = $dir."/config.txt";
  }
  else
  {
    echo "config.txt not found";
  }
  if ($configFile != "")
  {
    $configLines = file($configFile);
    if(isset($_POST["action"]))
    {
      $modified = false;
      $action = htmlspecialchars($_POST["action"]);
      if ($action == "modify")
      {
        foreach ($configLines as $numLine => $line)
        {
          $trimmed = trim($line);
          if (!StartsWith($trimmed, "#") && !StartsWith($trimmed, "[") && $trimmed != "") {
            list($key, $value) = split('=', $trimmed);
            $key = trim($key);
            $value = trim($value);
            if (StartsWith($value, '"') && str_replace('"', '', $value) != $_POST[$key])
            {
              //echo $key ."=".$_POST[$key]."<br>";
              $configLines[$numLine] = $key ." = \"".$_POST[$key]."\"\r\n";
              $modified = true;
            }
          }
        }
        if ($modified) file_put_contents($configFile, $configLines);
      }
    }
    $configArray = array();
    foreach ($configLines as $line) { 
      $trimmed = trim($line);
      if (!StartsWith($trimmed, "#") && $trimmed != "" && !StartsWith($trimmed, "[")) { 
        list($key, $value) = split('=', $trimmed);
        $key = trim($key);
        $value = trim($value);
        $configArray[$key] = $value;
      }
    }
  }
  $phpSelf =  $_SERVER['PHP_SELF'];
  $baseGets = GetParamsWithout(array("start","stop"));
  $url = $phpSelf."?".$baseGets;
?>

<table width="100%">
  <tr><td>
<div class="maincontent">
  <?php
  $base = realpath(dirname(__FILE__))."/searchs";
  
  // Get list of scripts to run
  $pythonFiles = array();
  foreach (glob($base."/*.py") as $filename)
  {
    //echo basename($filename)."<br>";
    if (!StartsWith(basename($filename), "config"))
    {
      $pythonFiles[] = $filename;
    }
  }
  ?>
  <table>
    
  <?php
  $stopFile = GetGet("stop", "");
  $startFile = GetGet("start", "");
  $redirect = false;
  
  $allPids = explode("\n", shell_exec("tasklist /v /fo csv | findstr /i python"));
  $pidsNumber = array();
  foreach ($allPids as $key => $val)
  {
  	$timmed = trim($val);
  	if ($timmed != "")
  		$pidsNumber[] = str_replace('"', '', explode(",", $val)[1]);
  }

  foreach ($pythonFiles as $pyFile)
  {
    echo "<tr><td>";
    $pyFile = basename($pyFile);
    $pidFile = $base."/".$pyFile.".pid";
    $info = pathinfo($pyFile);
    $pyConfigFile = $base."/config".$pyFile;//$base."/".basename($pyFile,'.'.$info['extension']).".txt";

    $isRunning = false;
    if (file_exists($pidFile))
    {
      // Get pid from file
      $pid = file($pidFile);
      if (is_array($pid)) $pid = $pid[0];
      
      // Check if the pid is still running
      $isRunning = in_array ($pid, $pidsNumber);
      if ($stopFile == basename($pyFile))
      {
        shell_exec("taskkill /PID ".$pid." /f");
        $redirect = true;
      }
    }
    if ($startFile == basename($pyFile))
    {
      if (!$isRunning)
      {
        $commandStr = "start /b \"\" ".$configArray["python_exe"].' '.$base.'/'.$pyFile;
        $command = escapeshellcmd($commandStr);
        pclose(popen($commandStr, 'r'));
        
        $redirect = true;
      }
    }
    ?>
    <input type="checkbox" name="<?php echo $pyFile; ?>" value="<?php echo trim($pyFile); ?>"><?php echo trim($pyFile); ?><?php 
    // if is running
    if ($isRunning) 
    {
      $redirectButton = $url."&stop=".basename($pyFile);
    ?>
    </td><td><img src="img/Clock.gif" alt="Running pid" height="16" width="16">
  <?php 
    echo "(".$pid.")";    // taskkill /PID /f 9028
  ?>
    <button type="but" onclick="window.location.href='<?php echo $redirectButton; ?>';"><?php echo "Stop"; ?></button>
  <?php
    }
    else
    {
      $redirectButton = $url."&start=".basename($pyFile);
  ?>
    </td><td><button type="but" onclick="window.location.href='<?php echo $redirectButton; ?>';"><?php echo "Run"; ?></button>
  <?php
      if (file_exists($pidFile))
      {
        //echo "removing ".$pidFile;
        unlink ($pidFile); // It should not exist
      }
    }
    
    echo "</td></tr>";
  } ?>
    
  </table>
<?php if ($redirect) { sleep(2); ?>
<script>
  window.location.replace(<?php echo "'".$url."'"; ?>);
</script>
<?php } ?>
  <?php
  //// Run file without waiting
  //
  //$commandStr = "start /b \"\" ".$configArray["python_exe"].' '.$base.'/test.py';
  //$command = escapeshellcmd($commandStr);
  ////print("command: ".$commandStr."<br>");
  ////$output = shell_exec($command);
  //pclose(popen($commandStr, 'r'));
  ////echo $output;
  
  //$pids = shell_exec(escapeshellcmd("tasklist /v /fo csv | findstr /i python"));
  ?>
</div>
</td><td width="50%">
<form action="<?php echo $url; ?>" method="post">
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
  <input type="submit" value="Submit">
  <input type="hidden" name="action" value="modify">
</form>
</td></tr></table>