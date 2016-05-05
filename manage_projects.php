<?php

  $resultText = "";
  $errorString= "";
  $resultText2 = "";
  $errorString2= "";
  $pid = GetGet("pid", "");
  $command = GetGet("com", "");
  if ($pid == "")
  	$pid = GetPost("pid", "");
  $action = GetPost("action", "");
	if ($action != "")
	{
		if ($action == "create")
		{
			// Create new project
			$project_name = GetPost("project_name", "");
			$returned = $sql_tools->CreateProject($userData["id"], $project_name);
			if ($returned == 1)
				$resultText = "Created: ".$returned;
			else
				$errorString = "Error: ".$returned;
		}
		else if ($action == "archiveProject")
		{
			$projectId = GetPost("projectId", "");
    	$returned = $sql_tools->ArchiveProject($projectId, $userData["id"]);
      if ($returned == 1)
        $resultText2 = "Done";
      else
        $errorString2 = "Error: ".$returned;
		}
	}
	
	if ($pid != "")
	{
		if ($command == "rename")
			include "rename.php";
		else
			include "manage_project.php";
	}
	else
	{
		$this_page = "index.php?opt=manage_projects";
		$projectRows = $sql_tools->GetProjects($userData["id"]);
		foreach($projectRows as $key => $projectRow)
		{
			$projectRows[$key]["info"]	 = $sql_tools->GetProjectSearchsInfo($projectRow["id"]);
		}
		
		
?>

<div id="content" >
	<div class="dashboard-create-project well">
	<h2>Create project:</h2>
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
	        <td><label for="project_name">Name</label></td><td><input type="text" name="project_name" id="project_name" value="" class=" ui-widget ui-widget-content ui-corner-all"></td>
	        <td><input type="submit" value="Create" class="btn"><input type="hidden" name="action" value="create"></td>
	      </tr>
	    </table>
	  </form>
	</div>
	<div class="dashboard-list-projects well">
		<h2>Available projects:</h2>
    <?php if ($resultText2) { ?>
    <font color="green"><?php echo $resultText2; ?></font>
    <?php
      }
      else if ($errorString2)
      {
    ?>
    <font color="red"><?php echo $errorString2; ?></font>
    <?php } ?>
		<table>
		<?php foreach ($projectRows as $project) { 
			$archiveData = $project["info"]["active"].",'".$project["name"]."'";
			$disabledArchive = $project["info"]["active"] > 0 ? 'style="background: #dddddd"' : '';
			?>
		<tr>
			<td><b><?php echo $project["name"]; ?></b></td>
			<td>(Searchs: <?php echo $project["info"]["searchs"]; ?> | Active: <?php echo $project["info"]["active"]; ?> )</td>
			<td><button class="btn" onclick="javascript:window.location.href='<?php echo $this_page."&pid=".$project["id"]		; ?>'">Manage</button></td>
			<td><button class="btn" onclick="javascript:window.location.href='index.php?opt=output&pid=<?php echo $project["id"]; ?>'" >Graphics</button></td>
			<td><form action="<?php echo $this_page; ?>" method="post" onsubmit="return validateArchive(<?php echo $archiveData; ?>)"><button type="submit" class="btn" <?php echo $disabledArchive; ?>>
      		<span><img src="img/archive.png" height="16" width="16"/></span> Archive</button>
        <input type="hidden" name="action" value="archiveProject">
        <input type="hidden" name="projectId" value="<?php echo $project["id"]; ?>">
        </form></td>
		</tr>
		<?php } ?>
		
		</table>
	</div>
</div>
<script language="javascript" type="text/javascript">
	function validateArchive(active, projectName) {
    if (active > 0)
    {
      alert("Please, deactivate all searchs before archive it.");
      return false;
    }
    
    return confirm("Are you sure you want to archive the project : " + projectName + "?\n\nAll data will be stored during 30 days in case you would like to restore them.");
  }
</script>
<?php 
}
?>
