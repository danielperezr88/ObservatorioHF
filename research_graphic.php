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
	
	$returned = $sql_tools->GetSearchsActive(GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']),GetGet("active", 1));
	//error_log(serialize($returned));
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	
	$fromdate = GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
	$todate = GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));//$week_end;
	$temporalStr = $sql_tools->GetTemporalStr($fromdate, $todate, GetGet("fromhour",0),GetGet("tohour",24));
		
	$values = $sql_tools->GetResearch(array_column($returned,'id'), $fromdate, $todate, GetGet("searchVars", ""),array($temporalStr,$sentimentStr));

	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "amcharts",
		  "version" =>  "3.19.2",
		  "files"   =>  array("amcharts.js","serial.js","themes/light.js")
		),
		array(
		  "library" =>  "tag-it",
		  "version" =>  "2.0",
		  "files"   =>  array("tag-it.js")
		)
	  ),
	  "css"     =>  array(
		array(
		  "library" =>  "tag-it",
		  "version" =>  "2.0",
		  "files"   =>  array("jquery.tagit.css")
		),
		array(
		  "library" =>  "daterangepicker",
		  "version" =>  "2.1.19",
		  "files"   =>  array("daterangepicker.css")
		)
	  )
	));
	
	if(empty($values)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
?>

<div style="width: 100%;">
	<center>
		<div id="chartdiv" style="width:100%;height:600px;"></div>
	</center>
</div>

<script language="javascript" type="text/javascript">
  
	var chartData  = [
		<?php
			$previousCreated = "-1"; 
			foreach($values as $value) { 
				if ($previousCreated != $value["created_norm"] && $previousCreated != "-1") 
					echo "},{"; 
				else if ($previousCreated != $value["created_norm"])
					echo "{";
				else
					echo ",";
				if ($previousCreated != $value["created_norm"]) echo  '"created_norm": "'.$value["created_norm"].'",';
				
				if ($value["count"] < 0 ) $value["count"] = -$value["count"];
				echo '"field'.$value["search_id"].'" : '.$value["count"];
				
				$previousCreated = $value["created_norm"];
		  } 
		  echo "}";
		  ?>];
	var graphsData = [
	<?php
			$previousSearch = "-1"; 
			foreach($returned as $value) { 
				if ($previousSearch != "-1") 
					echo "},{"; 
				else 
					echo "{";
				if ($previousSearch == "-1") echo '"id": "g1",';
				 ?>
				"balloonText": <?php echo '"'.$value["name"].' '; ?>[[value]]",
        "bullet": "round",
        "title": <?php echo '"'.$value["name"].'"'; ?>,
        "valueField": <?php echo '"field'.$value['id'].'"'; ?>,
				"fillAlphas": 0
				<?php
				$previousSearch = $value['id'];
		  } 
		  echo "}";
		  ?>
	];

	var chart = AmCharts.makeChart("chartdiv", {
    "type": "serial",
    "theme": "light",
    "dataDateFormat": "YYYY-MM-DD JJ:NN",
    "legend": {
      "useGraphSettings": true,
      "valueAlign": "left"
    },
    
    "dataProvider": chartData,
    "valueAxes": [{
//        "integersOnly": true,
//        "maximum": 6,
//        "minimum": 1,
//        "reversed": true,
        "axisAlpha": 0,
        "dashLength": 5,
//        "gridCount": 10,
        "position": "left",
//        "title": "Place taken",
				"ignoreAxisWidth":true
    }],
    "startDuration": 0.5,
    "graphs": graphsData,
    "chartScrollbar": {
        "graph": "g1",
        "oppositeAxis":true,
        "offset":30,
        "scrollbarHeight": 80,
        "backgroundAlpha": 0,
        "selectedBackgroundAlpha": 0.1,
        "selectedBackgroundColor": "#888888",
        "graphFillAlpha": 0,
        "graphLineAlpha": 0.5,
        "selectedGraphFillAlpha": 0,
        "selectedGraphLineAlpha": 1,
        "autoGridCount":true,
        "color":"#AAAAAA"
    },
    "chartCursor": {
    		"categoryBalloonDateFormat": "YYYY-MM-DD JJ:NN",
				"cursorPosition": "mouse",
        "showNextAvailable": true,
    	  //"pan": true,
    	  "valueLineEnabled": true,
        "valueLineBalloonEnabled": true,
        //"cursorAlpha": 0,
        "cursorColor":"#258cbb",
        //"limitToGraph":"g1",
        "valueLineAlpha":0.2,
        "zoomable": true
    },
    "valueScrollbar":{
      "oppositeAxis":false,
      "offset":50,
      "scrollbarHeight":10
    },
    "categoryField": "created_norm",
    "categoryAxis": {
	    	"labelRotation": 45,
        "gridPosition": "middle",
        "axisAlpha": 0,
        "fillAlpha": 0.05,
        "fillColor": "#000000",
        "gridAlpha": 0,
        "position": "top",
        "dashLength": 1,
        "minorGridEnabled": true,
        "parseDates": true,
		    "minPeriod": "DD",
		    "dataDateFormat": "YYYY-MM-DD JJ:NN:SS",
    },
    "export": {
    	"enabled": true
     }
});

chart.addListener("rendered", zoomChart);

zoomChart();

function zoomChart() {
    chart.zoomToIndexes(chart.dataProvider.length - 40, chart.dataProvider.length - 1);
}

</script>

