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
	
	$fromdateStr = "fromdate=".GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
	$todateStr = "todate=".GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));
	$fromhourStr = "fromhour=".GetGet("fromhour",0);
	$tohourStr = "tohour=".GetGet("tohour",24);
	
	$returned = $sql_tools->GetSearchsActive(GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']), GetGet("active", 1));
	$sid = GetGet("sid", (count($returned) > 0)? current($returned)['id'] : "");
	$searchidStr = empty($sid) ? '' : "search_id='{$sid}'";
	
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));	
	
	$attributes = implode("&",array_filter(array($searchidStr,$fromdateStr,$todateStr,$fromhourStr,$tohourStr,$sentimentStr,$searchidStr), function($val){
		return !empty($val);
	}));
	
	if(empty($sid)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
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
<script language="javascript" type="text/javascript">

    // Create a heatmap layer based on GeoJSON content
    var heatmapLayer = new ol.layer.Heatmap({
        source: new ol.source.GeoJSON({
            url: 'world_json.php?<?php echo $attributes;?>',
            projection: 'EPSG:3857'
        }),
        opacity: 0.5,
	radius: 8
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
