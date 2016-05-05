<?php
	include "php/tools.php";
	include "php/dbConnection.php";
?>

<script language="javascript" type="text/javascript">
  currentTab = '<?php echo end(split('/',str_replace('\\','/',__FILE__)));?>';
</script>

<?php
	
	if(empty($userData = GetLoggedUser())){
		echo('<h3 style="margin-top:0;padding-left:1em;">No logged user data found...</h3>');
		continue; //hacking attempt
	}
	
	$active = GetGet("active", 1);
	$pid = GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']);
	
	$returned = $sql_tools->GetSearchsActive($pid, $active);
	
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	
	$sid = GetGet("sid", (count($returned) > 0)? current($returned)['id'] : "");
	$searchidStr = empty($sid) ? '' : "search_id='{$sid}'";
	
	$fromdate = GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
	$todate = GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));//$week_end;
	$temporalStr = $sql_tools->GetTemporalStr($fromdate, $todate, GetGet("fromhour",0),GetGet("tohour",24));
	
	$whereStr =  $sql_tools->CreateWhere(array($temporalStr,$sentimentStr,$searchidStr));

	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "openlayers",
		  "version" =>  "3.13.1",
		  "files"   =>  array("ol3.4.js")
		)
	  ),
	  "css"     =>  array(
		array(
		  "library" =>  "openlayers",
		  "version" =>  "3.13.1",
		  "files"   =>  array("ol.css")
		)
	  )
	));
	
?>
    
	<div id="content" style="width:100%">
		<center>
		<div id="map" class="map" style="width:100%; height:580px;"></div>
		</center>
	</div>
	<script>
  
    // Create a heatmap layer based on GeoJSON content
    var heatmapLayer = new ol.layer.Heatmap({
        source: new ol.source.GeoJSON({
            url: 'leaders_world_json.php?pid='+<?php echo '"'.$pid.'"'; ?>+'&active='+<?php echo '"'.$active.'"'; ?>+'&where='+<?php echo '"'.rawurlencode($whereStr).'"'; ?>,
            projection: 'EPSG:3857'
        }),
        opacity: 0.9
    });
    

    // Create a tile layer from OSM
    var osmLayer = new ol.layer.Tile({
        source: new ol.source.OSM()
    });

    // Create the map with the previous layers
    var map = new ol.Map({
        target: 'map',  // The DOM element that will contains the map
        renderer: 'canvas', // Force the renderer to be used
        layers: [osmLayer, heatmapLayer],
        // Create a view centered on the specified location and zoom level
        view: new ol.View({
            center: ol.proj.transform([-4.125, 40.507222], 'EPSG:4326', 'EPSG:3857'),
            zoom: 6
        })
    });
  </script>
  