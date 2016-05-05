<?php

	$opt = strtolower(GetGet("opt", ""));
	$homeLink = "index.php";
	$baseLink = $homeLink."?opt=";
	
	$menuArray = [
		"home.php" => 				"Home",
		//"edit_config.php" => 		"Console",
		"manage_projects.php" =>	"Dashboard",
		"manage_tweeters.php" => 	"Tweeter Accounts",
		"sub_menu.php" => 			"Reports",
		"logout.php" => 			"Logout"
	];
	if ($opt != "")
	{
		foreach($menuArray as $key => $value)
		{
			if ($opt.".php" == $key) $activeTab = $key;
		}
	}
	if(!isset($activeTab))
	{
		reset($menuArray);
		$activeTab = key($menuArray);
	}
?>

<body>
<div style="width: 200px; height: 30px; display: inline-block; position: relative;">
	<div style="top: 50%; left: 50%; position: absolute; transform: translateX(-50%) translateY(-50%);">
		<a href="/applications" style="background-color: ##f2f2f2;color:white"><img src="img/hf-logo-trasnparent.png"  height="30" alt="Human Forecast"></a>
	</div>
</div>

<div id="cssmenu" class="cssmenu" style="position: absolute; left: 200px; display: inline-block; right: 0;">
	<ul >
		<?php foreach($menuArray as $key => $value){ 
			if ($key == "home.php")	{	?>
			<li <?php if ($activeTab == $key) echo "class=\"active\""; ?> ><a href="<?php echo $homeLink; ?>"><?php echo $value; ?></a></li>
		<?php } else { ?>
			<li <?php if ($activeTab == $key) echo "class=\"active\""; ?> ><a href="<?php echo $baseLink.str_replace(".php", "", $key); ?>"><?php echo $value; ?></a></li>
		<?php } } ?>
  </ul>
</div>
<div class="maincontent">

<?php 
	if (isset($userData))
	{
		if (array_key_exists ($opt.".php", $menuArray))
			include $opt.".php";
		else if ($opt == "management")
			include "management.php";
		else
		{
			$projectRows = $sql_tools->GetProjects($userData["id"]);
			?>
			<div class="dashboard-select-available-project well">
				<h2>Available projects:</h2>
				<ul class="home">
					<?php foreach ($projectRows as $project) { ?>
					<li><a href="index.php?opt=sub_menu&pid=<?php echo $project["id"]; ?>"><?php echo $project["name"]; ?></a></li>
					<?php } ?>
				</ul>
			</div> <?php 
		}
	}
?>
</div>
</body>

