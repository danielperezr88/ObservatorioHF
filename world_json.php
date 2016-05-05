<?php
	include "php/tools.php";
	include "php/dbConnection.php";
	include "php/dbTools.php";
	
	if(empty($userData = GetLoggedUser())){
		exit; //hacking attempt
	}
	
	$sid = GetGet('sid','');
	$where = rawurldecode(GetGet('where',''));
	
	$latLonInfo = $sql_tools->GetLatLon($sid, $where);
	
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
		{ "type": "Feature", "geometry": { "type": "Point", "coordinates": [ <?php echo $latLon["geoLon"].",".$latLon["geoLat"] ?> ] } }
		<?php 
			if (!($counterA == $maxLatLonInfo-1  && $i == $latLon["total"]-1) ) echo ",";
		}
		$counterA ++; 
	} ?>
]
}
