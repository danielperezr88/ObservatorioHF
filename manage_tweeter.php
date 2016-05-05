<?php

  $resultText = "";
  $errorString = "";
  //$resultText2 = "";
  //$errorString2 = "";
  //print_r($userData);
  
  //print($action);

	//print_r($GetFirstFreeConfigs);
	$tweeterId = getPost("tweeterId", "");
	$actionLink = "index.php?opt=manage_tweeters";
 	$saving = GetPost("saving", "");
 	if ($action == "add")
 	{
 		$saveButton = "Add";
 		if ($saving == "1")
 		{
 			$consumer_key = GetPost("consumer_key", "");
 			$consumer_secret = GetPost("consumer_secret", "");
 			$access_token_key = GetPost("access_token_key", "");
 			$access_token_secret = GetPost("access_token_secret", "");
 			$returned = $sql_tools->InsertTweeterAccount($userData["id"], $consumer_key, $consumer_secret, $access_token_key, $access_token_secret);
 			// Insert successfully
 			//if ($returned == 1)
 			if (mysql_affected_rows() == 1)
 			{
				$twtAccount = $sql_tools->GetTweeterAccountWhere($userData["id"], " ckey='".$consumer_key."' ORDER BY id DESC LIMIT 1");
				if ($twtAccount)
				{
					$resultText = "Created";
					
					$saveButton = "Save";
					$action = "manage"; // To save instead of creating on next time
				
	 				$twtAccount= $twtAccount[0];
	 			}
	 			else
	 			{
	 				$errorString = "Error: Could not be possible to insert it into database: Unknown reason.";
	 				$twtAccount = array();
					$twtAccount["id"] = "";
					$twtAccount["ckey"] = $consumer_key;
			 		$twtAccount["consumer_secret"] = $consumer_secret;
			 		$twtAccount["access_token_key"] = $access_token_key;
			 		$twtAccount["access_token_secret"] = $access_token_key;
	 			}
			}
			// Error found during insert
			else
			{
				$errorString = "Error: ".$returned;
				if ($returned == "") $errorString .= "Could not be possible to insert it into database: Unknown reason.";
				
				$twtAccount = array();
				$twtAccount["id"] = "";
				$twtAccount["ckey"] = $consumer_key;
		 		$twtAccount["consumer_secret"] = $consumer_secret;
		 		$twtAccount["access_token_key"] = $access_token_key;
		 		$twtAccount["access_token_secret"] = $access_token_key;
			}
 		}
 		// Adding new row (empty)
 		else
 		{
	 		$twtAccount = array();
	 		$twtAccount["id"] = "";
	 		$twtAccount["ckey"] = "";
	 		$twtAccount["consumer_secret"] = "";
	 		$twtAccount["access_token_key"] = "";
	 		$twtAccount["access_token_secret"] = "";
	 	}
 	}
 	else if ($action == "manage")
 	{
 		$saveButton = "Save";
 		if ($saving == "1")
 		{
 			$tweeterId = GetPost("tweeterId", "");
 			$consumer_key = GetPost("consumer_key", "");
 			$consumer_secret = GetPost("consumer_secret", "");
 			$access_token_key = GetPost("access_token_key", "");
 			$access_token_secret = GetPost("access_token_secret", "");
 			$returned = $sql_tools->UpdateTweeterAccount($userData["id"], $tweeterId, $consumer_key, $consumer_secret, $access_token_key, $access_token_secret);
 			// Save successfully
 			if (mysql_affected_rows() == 1)
 			{
 				$resultText = "Saved";

 				$twtAccount = $sql_tools->GetTweeterAccount($userData["id"], $tweeterId);
		 		if ($twtAccount)
		 			$twtAccount= $twtAccount[0];
 			}
 			// Error found during update
 			else
 			{
 				$errorString = "Error: ".$returned;
				
				$twtAccount = array();
				$twtAccount["id"] = "";
				$twtAccount["ckey"] = $consumer_key;
		 		$twtAccount["consumer_secret"] = $consumer_secret;
		 		$twtAccount["access_token_key"] = $access_token_key;
		 		$twtAccount["access_token_secret"] = $access_token_key;
 			}
 		}
 		else
 		{
	 		$twtAccount = $sql_tools->GetTweeterAccount($userData["id"], $tweeterId);
	 		if ($twtAccount)
	 			$twtAccount= $twtAccount[0];
	 		
	 	}
 	}
 	//print_r($twtAccount);
 	if ($twtAccount) {
 		$tweeterId = $twtAccount["id"];
?>

<div class="dashboard-edit-twitter-token well">
	<h1><?php echo ucfirst($action); ?> Tweeter</h1>
	<form action="<?php echo $actionLink; ?>" method="post" >
	<table >
	  <tr>
      <td colspan=2>
        <?php 
          if ($resultText)
          {
        ?>
        <font color="green"><?php echo $resultText; ?></font>
        <?php
          }
          else if ($errorString)
          {
        ?>
        <font color="red"><?php echo $errorString; ?></font>
        <?php
          }
        ?>
      </td>
    </tr>
		<tr>
      <td><label for="consumer_key">consumer_key *</label></td>
      <td><input type="text" name="consumer_key" id="consumer_key" value="<?php echo $twtAccount["ckey"]; ?>" class=" ui-widget ui-widget-content ui-corner-all" required></td>
    </tr>
    <tr>
      <td><label for="consumer_secret">consumer_secret *</label></td>
      <td><input type="text" name="consumer_secret" id="consumer_secret" value="<?php echo $twtAccount["consumer_secret"]; ?>" class=" ui-widget ui-widget-content ui-corner-all" required></td>
    </tr>
    <tr>
      <td><label for="access_token_key">access_token_key *</label></td>
      <td><input type="text" name="access_token_key" id="access_token_key" value="<?php echo $twtAccount["access_token_key"]; ?>" class=" ui-widget ui-widget-content ui-corner-all" required></td>
    </tr>
    <tr>
    	<td><label for="access_token_secret">access_token_secret *</label></td>
    	<td><input type="text" name="access_token_secret" id="access_token_secret" value="<?php echo $twtAccount["access_token_secret"]; ?>" class=" ui-widget ui-widget-content ui-corner-all" required></td>
    </tr>
    <tr>
	    <td><input type="submit" value="<?php echo $saveButton; ?>" class="btn">
	        <input type="hidden" name="action" value="<?php echo $action; ?>">
	        <input type="hidden" name="saving" value="1"> 
	        <input type="hidden" name="tweeterId" value="<?php echo $tweeterId; ?>"> 
	      </td>
	    <td><input type="button" class="btn" onclick="location.href='index.php?opt=manage_tweeters'" value="Cancel" /></td>
	  </tr>
  </table>
  </form>
</div>
<?php } ?>