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
	$temporalStr = $sql_tools->GetTemporalStr($fromdate, $todate, GetGet("fromhour",0),GetGet("tohour",24));
	
	$whereStr =  $sql_tools->CreateWhere(array($temporalStr,$sentimentStr,$searchidStr));
	
	$ids = array_column($returned,'id');
	$values = $sql_tools->GetLeaders($ids, $whereStr);
	$tweets = $sql_tools->CountTweets($ids, explode(",","'" . implode("','",array_column($values,"retweetedFrom")) . "'"), $whereStr);
	
	$tweetsDict = array();
	foreach ($tweets as $tweet)
	{
		$tweetsDict[$tweet["name"]] = $tweet["ct"];
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
	
	if(empty($tweetsDict)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
?>

<div style="width: 100%;">
	<center>
		<div id="chartdiv" style="width:100%;height:600px;font-size:11px;"></div>
	</center>
</div>

<style type="text/css">
	.amcharts-export-menu-top-right {
	  top: 10px;
	  right: 0;
	}	
</style>

<script language="javascript" type="text/javascript">
  
	var chart = AmCharts.makeChart( "chartdiv", {
  "type": "serial",
  "theme": "light",
  "dataProvider": [ 
  <?php 
	  $provider = array();
	  foreach ($values as $value)
		$provider[] = "{" .
		"\"leader\":\"{$value['retweetedFrom']}\"," .
		"\"retweets\":\"{$value['ctF']}\"," .
		"\"tweets\":\"" . (array_key_exists($value["retweetedFrom"], $tweetsDict) ? $tweetsDict[$value["retweetedFrom"]] : "0") . "\"," .
		"}";
	  echo implode(",\n",$provider);
  ?>
  ],
  "valueAxes": [ {
    "gridColor": "#FFFFFF",
    "gridAlpha": 0.2,
    "dashLength": 0
  } ],
  "legend": {
        "useGraphSettings": true,
        "markerSize":12,
        "valueWidth":0,
        "verticalGap":0
    },
  "gridAboveGraphs": true,
  "startDuration": 1,
  "graphs": [ {
    "balloonText": "[[category]]: <b>[[value]]</b> [[title]]",
    "fillAlphas": 0.8,
    "lineAlpha": 0.2,
    "type": "column",
    //"topRadius":1,
    "title": "Retweets",
    "valueField": "retweets"
  }, {
    "balloonText": "<span style='font-size:13px;'>[[category]]: <b>[[value]]</b> [[title]]</span>",
    "bullet": "round",
    "bulletBorderAlpha": 1,
    "bulletColor": "#FFFFFF",
    "useLineColorForBulletBorder": true,
    "fillAlphas": 0,
    "lineThickness": 2,
    "lineAlpha": 1,
    "bulletSize": 7,
    "title": "Tweets",
    "valueField": "tweets"
    } ],
  "depth3D": 40,
    "angle": 30,
    "rotate": true,
  "chartCursor": {
    "categoryBalloonEnabled": false,
    "cursorAlpha": 0,
    "zoomable": false
  },
  "categoryField": "leader",
  "categoryAxis": {
    "gridPosition": "start"
    //,
    //"gridAlpha": 0,
    //"labelRotation": 45
  },
  "export": {
    "enabled": true
  }

} );
</script>
