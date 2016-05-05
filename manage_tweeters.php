<?php

  $resultText = "";
  $errorString = "";
  $resultText2 = "";
  $errorString2 = "";
  //print_r($userData);
  
  if( $userData["id"] == "")
  {
    // Hacking attempt ?
  }
  else
  {
  	$twtAccounts = array();
  	$action = GetPost("action", "");
	  if ($action != "")
	  {
	    if ($action == "add" || $action == "manage")
	    {
	      include "manage_tweeter.php";
	    }
	    else if ($action == "delete")
	    {
	    	$tweeterId = getPost("tweeterId", "");
	    	$returned = $sql_tools->DeleteTweeterAccount($userData["id"], $tweeterId);
	    	$twtAccounts = $sql_tools->GetTweeterAccounts($userData["id"]);
	    }
	    
	    // else hacking attempt ?
	  }
	  else
  		$twtAccounts = $sql_tools->GetTweeterAccounts($userData["id"]);
  	
    if ($twtAccounts == array())
    {
      // Hacking attempt ?
    }
    else
    {
      $actionLink = "index.php?opt=manage_tweeters";

      //print_r($twtAccounts);
?>

<div id="content" >
  <div class="dashboard-manage-twitter-tokens well">
    <table >
      <tr>
        <th style="color:blue">consumer_key</th>
        <th style="color:blueviolet">consumer_secret</th>
        <th style="color:blue">access_token_key</th>
        <th style="color:blueviolet">access_token_secret</th>
      </tr>
      <?php foreach($twtAccounts as $key => $account) { ?>
      <tr>
        <td ><?php echo $account["ckey"]; ?></td>
        <td style="color:DimGray  "><?php echo $account["consumer_secret"]; ?></td>
        <td ><?php echo $account["access_token_key"]; ?></td>
        <td style="color:DimGray  "><?php echo $account["access_token_secret"]; ?></td>
        <td><form action="<?php echo $actionLink; ?>" method="post" ><input type="submit" value="Manage" class="btn">
	        <input type="hidden" name="action" value="manage">
	        <input type="hidden" name="tweeterId" value="<?php echo $account["id"]; ?>">
        </form>
        </td>
        <td><form action="<?php echo $actionLink; ?>" method="post" onsubmit="return onsubmitConfirm('<?php echo $account["ckey"]; ?>')"><input type="submit" value="Delete" class="btn">
	        <input type="hidden" name="action" value="delete">
	        <input type="hidden" name="tweeterId" value="<?php echo $account["id"]; ?>">
        </form></td>
      </tr>
      <?php } ?>
    </table>
  </div>
  <div class="dashboard-actions-twitter well">
    <table >
      <td><form action="<?php echo $actionLink; ?>" method="post" ><input type="submit" value="Add" class="btn">
	        <input type="hidden" name="action" value="add">
        </form></td>
    </table>
  </div>
</div>
<script>
	function onsubmitConfirm(consumer_key)
	{
		return confirm("Are you sure you want to delete tweeter account with consumer_key: " + consumer_key + "?\n\nAny search related with this account will be stoped.");
	}
</script>
<?php } } ?>