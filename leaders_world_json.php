<?php
	include "php/tools.php";
	include "php/dbConnection.php";
	include "php/dbTools.php";
	
	if(empty($userData = GetLoggedUser())){
		echo('<h3 style="margin-top:0;padding-left:1em;">No logged user data found...</h3>');
		continue; //hacking attempt
	}
	
	//($whereStr = GetGet("where",false)) ? $whereStr = rawurldecode($whereStr) : $whereStr = "";
	//($pid = GetGet("pid",false)) ? $pid = base64_decode($pid) : $pid = current($sql_tools->GetProjects($userData["id"]))['id'];
	$fromdate = GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
	$todate = GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));
	
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	$sid = GetGet("search_id", "");
	$searchidStr = empty($sid) ? '' : "search_id='{$sid}'";
	$temporalStr = $sql_tools->GetTemporalStr($fromdate, $todate, GetGet("fromhour",0),GetGet("tohour",24));
	
	$leaders = $sql_tools->GetLeaders(array_column($returned,'id'), $whereStr);
	
	$latLonInfo = $sql_tools->GetOriginalLatLon(explode(",","'" . implode("','",array_column($leaders,"original_from")) . "'"), $fromdate, $todate, array($sentimentStr,$searchidStr,$temporalStr));
	
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
		{ "type": "Feature", "geometry": { "type": "Point", "coordinates": [ <?php echo $latLon["original_lon"].",".$latLon["original_lat"] ?> ] } }
		<?php 
			if (!($counterA == $maxLatLonInfo-1  && $i == $latLon["total"]-1) ) echo ",";
		}
		$counterA ++; 
	} ?>
]
}
