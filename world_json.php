<?php
	include "php/tools.php";
	include "php/dbConnection.php";
	include "php/dbTools.php";
	
	if(empty($userData = GetLoggedUser())){
		exit; //hacking attempt
	}
	
	$sid = GetGet('search_id','');
	$searchidStr = empty($sid) ? '' : "search_id='{$sid}'";
	$fromdate = GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
	$todate = GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));
	$temporalStr = $sql_tools->GetTemporalStr($fromdate,$todate,GetGet("fromhour",0),GetGet("tohour",24));
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	
	$latLonInfo = $sql_tools->GetLatLon($fromdate, $todate, array($searchidStr,$temporalStr,$sentimentStr));
	
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
		{ "type": "Feature", "geometry": { "type": "Point", "coordinates": [ <?php echo $latLon["geolon"].",".$latLon["geolat"] ?> ] } }
		<?php 
			if (!($counterA == $maxLatLonInfo-1  && $i == $latLon["total"]-1) ) echo ",";
		}
		$counterA ++; 
	} ?>
]
}
