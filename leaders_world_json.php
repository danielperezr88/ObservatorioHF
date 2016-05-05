<?php
	include "php/tools.php";
	include "php/dbConnection.php";
	include "php/dbTools.php";
	
	if(empty($userData = GetLoggedUser())){
		echo('<h3 style="margin-top:0;padding-left:1em;">No logged user data found...</h3>');
		continue; //hacking attempt
	}
	
	($whereStr = GetGet("where",false)) ? $whereStr = rawurldecode($whereStr) : $whereStr = "";
	//($pid = GetGet("pid",false)) ? $pid = base64_decode($pid) : $pid = current($sql_tools->GetProjects($userData["id"]))['id'];
	
	$returned = $sql_tools->GetSearchsActive(GetGet("pid",current($sql_tools->GetProjects($userData["id"]))['id']), GetGet("active", 1));
	$leaders = $sql_tools->GetLeaders(array_column($returned,'id'), $whereStr);
	
	$latLonInfo = $sql_tools->GetRetweetedLatLon(explode(",","'" . implode("','",array_column($leaders,"retweetedFrom")) . "'"), $whereStr);
	
	$maxLatLonInfo = count($latLonInfo);
	
	$latLonInfo = Normalize($latLonInfo, "total");
	
?>

{
"type": "FeatureCollection",
"features": [
<?php 
 $counterA = 0;
	foreach($latLonInfo as $latLon) { 
		//print(count ($latLon));
		for ($i=0; $i< $latLon["total"]; $i++) {
		?>
		{ "type": "Feature", "geometry": { "type": "Point", "coordinates": [ <?php echo $latLon["retweetedLon"].",".$latLon["retweetedLat"] ?> ] } }
		<?php 
			if (!($counterA == $maxLatLonInfo-1  && $i == $latLon["total"]-1) ) echo ",";
		}
		$counterA ++; 
	} ?>
]
}
