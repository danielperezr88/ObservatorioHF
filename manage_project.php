<?php

  $resultText = "";
  $errorString = "";
  $resultText2 = "";
  $errorString2 = "";
  $pid = getGet("pid", "");
  if ($pid == "")
    $pid = GetPost("pid", "");
  $searchStats = $sql_tools->GetSearchStats($userData["id"], $pid);
  $action = GetPost("action", "");
  if ($action != "")
  {
    if ($action == "createSearch")
    {
    	
      // Create new search
      $search_name = GetPost("search_name", "");
      //$search_str = GetPost("search_str", "");
      $mysearch_str = GetPost("mysearch_str", "");
      $searchsExploded = explode( ',', $mysearch_str );
      $mysearch_str = "'".trim(implode("','",$searchsExploded))."'";
      //print_r($mysearch_str);
      
      // Be sure the pid belongs to userId
      $projectRows = $sql_tools->GetProject($userData["id"], $pid);
      if ($search_name == "")
      {
        $errorString = "Error: Name is required.";
        // Hacking attempt ?
      }
      else if ($mysearch_str == "''")
      {
        $errorString = "Error: Search is required.";
        // Hacking attempt ?
      }
      else if ($projectRows == array())
      {
        $errorString = "Error: Hacking attempt. Storing IP for future legal actions";
        // Hacking attempt ?
      }
      else
      {
        $returned = $sql_tools->CreateSearch($userData["id"], $pid, $search_name, $mysearch_str, $searchStats["currentActiveSearchs"] < $searchStats["maxActiveSearchs"]);
        if ($returned == 1)
          $resultText = "Created";
        else
          $errorString = "Error: ".$returned;
      }
    }
    else if ($action == "activeSearch" || $action == "inactiveSearch")
    {
      if ($action == "activeSearch" && $searchStats["currentActiveSearchs"] >= $searchStats["maxActiveSearchs"])
      {
        // Hacking attempt ?
      }
      else
      {
        $searchId = GetPost("searchId", "");
        $activeValue = $action == "activeSearch"? 1 : 0;
        $returned = $sql_tools->SetActiveValueSearch($userData["id"], $searchId, $activeValue);
        if ($returned == 1)
          $resultText2 = "Done";
        else
          $errorString2 = "Error: ".$returned;
      }
    }
    else if ($action == "archiveSearch")
    {
    	$searchId = GetPost("searchId", "");
    	$returned = $sql_tools->ArchiveSearch($searchId, $userData["id"]);
      if ($returned == 1)
        $resultText2 = "Done";
      else
        $errorString2 = "Error: ".$returned;
    }
    // else hacking attempt ?
  }
  
  if( $pid == "")
  {
    // Hacking attempt ?
  }
  else
  {
    $searchStats = $sql_tools->GetSearchStats($userData["id"], $pid);
    $projectRows = $sql_tools->GetProjects($userData["id"]);
    $thisProj = $sql_tools->GetProject($userData["id"], $pid);
    if ($thisProj == array())
    {
      // Hacking attempt ?
    }
    else
    {
      //$thisProj = reset($thisProj);
      $onChangeLink = "index.php?opt=manage_projects&pid=";
      $this_page = $onChangeLink.$pid;
      $renameLink = "index.php?opt=manage_projects&com=rename";
      $searchs = $sql_tools->GetSearchs($pid);
      //print_r($searchStats);
?>
<link href="css/tag-it_v2.0/jquery.tagit.css" rel="stylesheet" type="text/css">
<script src="scripts/tag-it_v2.0/tag-it.js" type="text/javascript" charset="utf-8"></script>

<div id="content" >
  <div class="dashboard-select-project well">
  	<table>
  		<tr>
  			<td>Project:</td>
  			<td><select id="pidSelector" name="pidSelector" onchange="OnSelectPid();" class="ui-widget">
            <?php foreach( $projectRows as $project) {
            	
              $selected = $pid == $project["id"]? "selected": ""; ?>
            <option value="<?php echo $project["id"]; ?>" <?php echo $selected; ?>><?php echo $project["name"]; ?></option>
            <?php } ?>
          </select></td>
  			<td><button class="btn" onclick="javascript:window.location.href='<?php echo $renameLink."&pid=".$pid; ?>'">Rename</button></td>
  		</tr>
  	</table>
    
    <p>
      <table >
        <tr>
          <th style="color:blue">Active searchs in project</th>
          <th style="color:blueviolet">Total active searchs</th>
          <th style="color:blue">Max active searchs</th>
        </tr>
        <tr>
          <td align="center"><?php echo $searchStats["projectActiveSearchs"]; ?></td>
          <td align="center"><?php echo $searchStats["currentActiveSearchs"]; ?></td>
          <td align="center"><?php echo $searchStats["maxActiveSearchs"]; ?></td>
        </tr>
      </table>
    </p>
  </div>
  <div class="dashboard-create-search well">
  <h2>Create search:</h2>
  <form action="<?php echo $this_page; ?>" method="post">
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
          <td><label for="search_name">Name *</label></td>
          <td><input type="text" name="search_name" id="search_name" value="" class=" ui-widget ui-widget-content ui-corner-all" required></td>
        </tr>
        <tr>
          <td><label for="search_str">Search *</label></td>
          <td valign="middle"><ul name="search_str" id="search_str" required></ul><input name="mysearch_str" id="mysearch_str" value="" type="hidden" required></td>
          <td><input type="submit" value="Create" class="btn"><input type="hidden" name="action" value="createSearch"></td>
        </tr>
        
      </table>
    </form>
  </div>
  <div class="dashboard-manage-searchs well">
    <h2>Searchs:</h2>
    <?php if ($resultText2) { ?>
    <font color="green"><?php echo $resultText2; ?></font>
    <?php
      }
      else if ($errorString2)
      {
    ?>
    <font color="red"><?php echo $errorString2; ?></font>
    <?php } ?>
    <?php if (count($searchs) >0) { ?>
    <table border="0"  style="padding:10px"  >
      <tr><th style="color:blue">Name</th>
      	<th></th>
        <th style="color:blueviolet">Searching</th>
        <th style="color:blue">Active</th>
        <th></th>
        <th style="color:blue">Archive</th>
      </tr>
    <?php foreach ($searchs as $search) { 
      if ($search["active"] == 1)
      {
        $color = "green";
        $buttonName = "Set Inactive";
        $action = "inactiveSearch";
        $disabled = "";
        $validateForm = "";
        $disabledArchive = 'style="background: #dddddd"';
        
      }
      else
      {
        $color = "red";
        $buttonName = "Set Active";
        $action = "activeSearch";
        $disabled = $searchStats["currentActiveSearchs"] < $searchStats["maxActiveSearchs"]? "" : 'style="background: #dddddd"';
        $validateForm = 'onsubmit="return validateForm()"';
        $disabledArchive = '';
      }
      $archiveData = $search["active"].",'".$search["name"]."'";
      ?>
    <tr>
      <td><b><?php echo $search["name"]; ?></b></td>
      <td><button class="btn" onclick="javascript:window.location.href='<?php echo $renameLink."&pid=".$pid."&sid=".$search["id"]; ?>'">Rename</button></td>
      <td><?php echo $search["search"]; ?></td>
      <td style="color:<?php echo $color; ?>"><b><?php echo $search["active"]; ?></b></td>
      <td><form action="<?php echo $this_page; ?>" method="post" <?php echo $validateForm; ?>><input type="submit" value="<?php echo $buttonName; ?>" class="btn" <?php echo $disabled; ?>>
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="searchId" value="<?php echo $search["id"]; ?>">
        </form></td>
      <td><form action="<?php echo $this_page; ?>" method="post" onsubmit="return validateArchive(<?php echo $archiveData; ?>)"><button type="submit" class="btn" <?php echo $disabledArchive; ?>>
      		<span><img src="img/archive.png" height="16" width="16"/></span> Archive</button>
        <input type="hidden" name="action" value="archiveSearch">
        <input type="hidden" name="searchId" value="<?php echo $search["id"]; ?>">
        </form></td>
    </tr>
    <?php } ?>
    
    </table>
    <?php } ?>
  </div>
</div>
<script language="javascript" type="text/javascript">
	$(function() {
		$('#search_str').tagit({
        // This will make the user has to press the backspace key twice to remove the last tag
        removeConfirmation: true,
        allowSpaces: true,
        // This will make Tag-it submit a single form value, as a comma-delimited field.
        singleField: true, 
        singleFieldNode: $('#mysearch_str')
    });
    
    //$( "#submitSearch" ).click(function(){
    //	var wordsToSearch = $("#myWordsToSearch").val().trim();
    //	if (wordsToSearch != "")
		//		searchVars = "&searchVars=" + wordsToSearch;
		//	else
		//		searchVars = "";
    /*	window.location.href='<?php echo $this_page; ?>' + searchVars;" */
    //});
  });
  function OnSelectPid()
  {
    var e = document.getElementById("pidSelector");
    var strVal = e.options[e.selectedIndex].value;
    window.location.replace(<?php echo '"'.$onChangeLink.'"' ?> + strVal);
  }
  
  function validateForm() {
    if (<?php echo $searchStats["currentActiveSearchs"] >= $searchStats["maxActiveSearchs"]? 1 : 0; ?> )
    {
      alert("Max active searchs reached for this user. Deactivate one of the active searchs before activating other.");
      return false;
    }
    return true;
  }
  
  function validateArchive(active, searchName) {
    if (active == 1)
    {
      alert("Please, deactivate it before archive it.");
      return false;
    }
    
    return confirm("Are you sure you want to archive the search : " + searchName + "?\n\nAll data will be stored during 30 days in case you would like to restore them.");
  }
  
</script>
<?php } } ?>
