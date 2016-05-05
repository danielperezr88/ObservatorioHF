<?php

  $resultText = "";
  $errorString = "";
  $resultText2 = "";
  $errorString2 = "";
  $command = getGet("com", "");
  $pid = getGet("pid", "");
  $sid = getGet("sid", "");
  if ($pid == "" && $sid == "")
    exit; // hacking attempt
  
  $action = getPost("action", "");
  $nameStr = "";
  
  if ($action == "saving")
  {
    $nameStr = getPost("form_name", "");
    if ($nameStr == "")
    {
      $errorString = "The new name cannot be empty";
    }
    else
    {
      if ($pid != "")
      {
      	if ($sid != "")
	      {
	        $sql_tools->SetNameSearch($userData["id"], $sid, $nameStr);
	        $search = $sql_tools->GetSearch($userData["id"], $sid);
	        if ($search)
	          $redirectOpt =  "&pid=".$search["project_id"];
	      }
      	else
      	{
	        $sql_tools->SetNameProject($userData["id"], $pid, $nameStr);
	        $redirectOpt =  "&pid=".$pid;
      	}
      }
      
      $exitLink = "index.php?opt=manage_projects&pid=".$redirectOpt;
      header("Location: ".$exitLink);
      exit;
    }
  }
  
  
  $renameType = "";
  $this_link = "index.php?opt=manage_projects&com=".$command;
  if ($pid != "")
  {
  	if ($sid != "")
	  {
	    $search = $sql_tools->GetSearch($userData["id"], $sid);
	    if ($search && $nameStr == "")
	    {
	      $nameStr = $search["name"];
	      $searchStr = $search["search"];
	    }
	    $this_link .= "&pid=".$pid."&sid=".$sid;
	    $renameType = "search";
	  }
	  else
	  {
	    $project = $sql_tools->GetProject($userData["id"], $pid);
	    if ($project && $nameStr == "")
	      $nameStr = $project["name"];
	    $this_link .= "&pid=".$pid;
	    $renameType = "project";
	  }
  }
  
  $exitLink = "index.php?opt=manage_projects&pid=".$pid;
?>

<div id="content" >
  
  <div class="well">
  <h2>Rename <?php echo $renameType; ?>:</h2>
  <form action="<?php echo $this_link; ?>" method="post">
      <table  border="0" >
        <tr>
          <td colspan=2>
            <?php if ($resultText) { ?>
            <font color="green"><?php echo $resultText; ?></font>
            <?php
              }
              else if ($errorString)
              {
            ?>
            <font color="red"><?php echo $errorString; ?></font>
            <?php } ?>
          </td>
        </tr>
        <tr>
          <td><label for="form_name">Name *</label></td>
          <td><input type="text" name="form_name" id="form_name" value="<?php echo $nameStr; ?>" class=" ui-widget ui-widget-content ui-corner-all" required></td>        
        </tr>
        <?php if ($sid != "") { ?>
        <tr>
          <td><label >Search</label></td>
          <td valign="middle"><?php echo $searchStr; ?></td>
        </tr>
        <?php } ?>
        <tr >
        	<td colspan=3>
        		<input type="submit" value="Save" class="btn">
        		<input type="hidden" name="action" value="saving">
        		<input value="Exit" class="btn" onclick="window.location.href='<?php echo $exitLink; ?>'"></td>
        </tr>
        
      </table>
    </form>
  </div>
</div>
<script language="javascript" type="text/javascript">
  
  function validateForm() {
    if (<?php echo $searchStats["currentActiveSearchs"] >= $searchStats["maxActiveSearchs"]? 1 : 0; ?> )
    {
      alert("Max active searchs reached for this user. Deactivate one of the active searchs before activating other.");
      return false;
    }
    return true;
  }
  
  
</script>
