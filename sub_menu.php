<?php

	$userData = GetLoggedUser();
	if($userData == array())
	{
		exit; //hacking attempt
	}
	$graph = "";
  if (isset($_GET["graph"]))
  	$graph = $_GET["graph"];
  
  $submenuArray = [
		"live.php" => 					"Live",
		"opinion.php" => 				"Opinion",
		"frequently_concepts.php" => 	"CONCEPTS",
		"hashtags.php" => 				"HASHTAGS",
		"urls.php" => 					"URLS",
		"ner.php" => 					"ENTITIES",
		"research.php" => 				"ANALYSIS",
		"leaders.php" => 				"LEADERS"
	];
	if ($graph == "")
	{
		reset($submenuArray);
		$graph = str_replace(".php", "", key($submenuArray));
	}
	
	$projectRows = $sql_tools->GetProjects($userData["id"]);
	$pid = GetGet("pid", "");
	if ($pid == "")
	{
		$first_proj = array_values($projectRows)[0]; 
		$pid = $first_proj["id"];
	}
	$baseLink = "index.php?opt=sub_menu&pid=".$pid;

	$onChangeLink = GetBaseLinkWithoutParam("pid")."&pid=";
?>
<script language="javascript" type="text/javascript">
	function OnSelectPid()
	{
		var e = document.getElementById("pidSelector");
		var strVal = e.options[e.selectedIndex].value;
		window.location.replace(<?php echo '"'.$onChangeLink.'"' ?> + strVal);
	}
</script>
<table style="width:100%;height:100%;border-spacing: 0;">

	<tr>
		<td style="vertical-align: top;background-color: #f2f2f2;width: 198px;">
		<div class="nav-container"> 
		<div> 
			<table><tr>
				<td>
					<label for="pidSelector" style="color:#555555;font-family: Arial, Helvetica">PROJECT: </label>
				</td>
				</tr>
				<tr>
				<td style="vertical-align: top">
					<select id="pidSelector" name="pidSelector" onchange="OnSelectPid();" class="ui-widget">
						<?php foreach ($projectRows as $project) { 
							$selected = $pid == $project["id"]? "selected": ""; ?>
						<option value="<?php echo $project["id"]; ?>" <?php echo $selected; ?>><?php echo $project["name"]; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr></table><p><br></p>
		  <ul class="nav">
				<?php foreach($submenuArray as $key => $value){
					//$graphPart = 
					 ?>
					<li <?php if ($graph.".php" == $key) echo "class=\"active\""; ?> ><a href="<?php echo $baseLink."&graph=".str_replace(".php", "", $key); ?>"><span class="text"><?php echo $value; ?></span></a></li>
				<?php } ?>
		  </ul>
		</div>
	</td>
	<td style="vertical-align: top;">
<?php 
	if (array_key_exists ($graph.".php", $submenuArray))
	{
		include $graph.".php";
	}	
?>
	</td>
</tr></table>
<script>
	$('li').click(function(){
  $(this).addClass('active')
   .siblings()
   .removeClass('active');
});
</script>