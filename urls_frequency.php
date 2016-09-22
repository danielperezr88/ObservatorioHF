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
	
	$returned = $sql_tools->GetSearchsActive(GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']), GetGet("active", 1));
	
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	
	$sid = GetGet("sid", (count($returned) > 0)? current($returned)['id'] : "");
	$searchidStr = empty($sid) ? '' : "search_id='{$sid}'";
	
	$fromdate = GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
	$todate = GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));//$week_end;
	$temporalStr = $sql_tools->GetTemporalStr($fromdate, $todate, 0, 24);
	
	$values = $sql_tools->GetFrecuents(array_column($returned,'id'), array($temporalStr,$sentimentStr,$searchidStr));
	$urls = array();
	foreach($values as $value)
	{
		$toDecode = "{".str_replace(']','',str_replace('[','', str_replace('",', '":', $value["urls"])))."}";
		$decoded =  json_decode($toDecode, true);
		if ($urls == array())
		{
			$urls = $decoded;
		}
		else
		{
			$urls = ArrayAdd($urls,$decoded);
		}
	}

	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "amcharts",
		  "version" =>  "3.19.2",
		  "files"   =>  array("amcharts.js","serial.js","themes/light.js")
		)
	  )
	));
	
	if (empty($urls)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
	arsort($urls);
	$urls = array_slice($urls, 0, 20);
	$maxUrls = array_values($urls)[0];
	
?>

<div style="width: 100%;">
	<center>
		<div id="chartdiv" style="width:100%;height:500px;font-size:11px;"></div>
	</center>
</div>

<script language="javascript" type="text/javascript">
  
	var chart = AmCharts.makeChart( "chartdiv", {
  "type": "serial",
  "theme": "light",
  "dataProvider": [ 
  <?php 
	  $provider = array();
	  foreach ($urls as $key => $value)
		$provider[] = "{" .
		"\"url\":\"" . str_replace("'","\\'", $key) . "\"," .
		"\"tweets\":\"{$value}\"," .
		"\"color\":\"" . GetColour($value, $maxUrls) . "\"," .
		"}";
	  echo implode(",\n",$provider);
  ?>
  ],
  "valueAxes": [ {
    "gridColor": "#FFFFFF",
    "gridAlpha": 0.2,
    "dashLength": 0
  } ],
  "gridAboveGraphs": true,
  "startDuration": 1,
  "graphs": [ {
    "balloonText": "[[category]]: <b>[[value]]</b>",
    "fillColorsField": "color",
    "fillAlphas": 0.8,
    "lineAlpha": 0.2,
    "topRadius":1,
    "type": "column",
    "title": "Url",
    "valueField": "tweets"
  } ],
  "depth3D": 20,
    "angle": 60,
    "rotate": true,
  "chartCursor": {
    "categoryBalloonEnabled": false,
    "cursorAlpha": 0,
    "zoomable": false
  },
  "categoryField": "url",
  "categoryAxis": {
    "gridPosition": "start"
  },
  "export": {
    "enabled": true
  }

} );
</script>