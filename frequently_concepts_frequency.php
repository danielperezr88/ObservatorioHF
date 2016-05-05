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
	
	$whereStr =  $sql_tools->CreateWhere(array($temporalStr,$sentimentStr,$searchidStr));
	
	$values = $sql_tools->GetFrecuents(array_column($returned,'id'), $whereStr);
	
	$concepts = array();
	foreach($values as $value)
	{
		$toDecode = "{".str_replace(']','',str_replace('[','', str_replace('",', '":', $value["concepts"])))."}";
		$decoded =  json_decode($toDecode, true);
		if ($concepts == array())
		{
			$concepts = $decoded;
		}
		else
		{
			$concepts = ArrayAdd($concepts,$decoded);
		}
	}
	if (empty($concepts)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
	arsort($concepts);
	$concepts = array_slice($concepts, 0, 20);
	$maxConcept = array_values($concepts)[0];
	
	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "amcharts",
		  "version" =>  "3.19.2",
		  "files"   =>  array("amcharts.js","serial.js","themes/light.js")
		)
	  )
	));
?>

<div style="width: 100%;">
	<center>
		<div id="chartdiv"></div>
	</center>
</div>

<script language="javascript" type="text/javascript">

	var chart = AmCharts.makeChart( "chartdiv", {
  "type": "serial",
  "theme": "light",
  "dataProvider": [ 
  <?php 
	  $provider = array();
	  foreach ($concepts as $key => $value)
		$provider[] = "{" .
		"\"concept\":\"" . str_replace("'","\\'", $key) . "\"," .
		"\"tweets\":\"{$value}\"," .
		"\"color\":\"" . GetColour($value, $maxConcept) . "\"," .
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
    "title": "Concept",
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
  "categoryField": "concept",
  "categoryAxis": {
    "gridPosition": "start"
  },
  "export": {
    "enabled": true
  }

} );
</script>