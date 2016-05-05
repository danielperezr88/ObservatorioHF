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
	$hashtags = array();
	foreach($values as $value)
	{
		$toDecode = "{".str_replace(']','',str_replace('[','', str_replace('",', '":', $value["hashtags"])))."}";
		$decoded =  json_decode($toDecode, true);
		if ($hashtags == array())
		{
			$hashtags = $decoded;
		}
		else
		{
			$hashtags = ArrayAdd($hashtags,$decoded);
		}
	}

	if (empty($hashtags)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
		
	arsort($hashtags);
	$hashtags = array_slice($hashtags, 0, 20);
	$maxHashtag = array_values($hashtags)[0];

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
	  foreach ($hashtags as $key => $value)
		$provider[] = "{" .
		"\"hashtag\":\"" . str_replace("'","\\'", $key) . "\"," .
		"\"tweets\":\"{$value}\"," .
		"\"color\":\"" . GetColour($value, $maxHashtag) . "\"," .
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
    "title": "Hashtag",
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
  "categoryField": "hashtag",
  "categoryAxis": {
    "gridPosition": "start"
  },
  "export": {
    "enabled": true
  }

} );
</script>