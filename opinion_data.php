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
	
	$sentiment = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	
	$fromdate = GetGet("fromdate", "");
	$todate = GetGet("todate", "");
	
	$temporalStr = $sql_tools->GetTemporalStr($fromdate, $todate, GetGet("fromhour", ""), GetGet("tohour", ""));
	
	$values = $sql_tools->GetTotal(array_column($returned,'id'),$fromdate,$todate,array($sentiment,$temporalStr));
	
	$sentimentArray = array();
	foreach ($values as $value) {
		foreach ($value as $value1) {
			$sentimentArray[$value1["sentiment"]][$value1["search_id"]]  = $value1;
		}
	}
	
	if (count ($sentimentArray) <= 0){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "amcharts",
		  "version" =>  "3.19.2",
		  "files"   =>  array("amcharts.js","pie.js","themes/light.js")
		)
	  )
	));
	
?>

<div id="chartdiv2" style="width:100%;height:400px;font-size:11px;"></div>
<?php 
	$firstVal = reset($sentimentArray);
	for ($i=0; $i < count($firstVal); $i++) { ?>
<div id="chartdiv_<?php echo $i; ?>" class="chartdivPie" style="width:100%;height:400px;font-size:11px;"></div>
<?php } ?>
<!-- <div id="chartdiv"></div> -->

<script language="javascript" type="text/javascript">
  
<?php 
	if (count ($sentimentArray) > 1)
		$reference = $values;
	else
	  $reference = $sentimentArray;
	$counter = 0; 
	$whole = array();
	foreach($reference as $key => $value) {
		$each = array();
		foreach ($value as $value1)
			$each[] = "{" .
			"\"country\":\"{$returned[$value1['search_id']]['name']} {$value1['sentiment']}\"," .
			"\"value\":{$value1['count']}" .
			"}";
		$whole[] = "var chart = AmCharts.makeChart(\"chartdiv_{$counter}\",{" .
		'"type": "pie",' .
		'"theme": "light",' .
		'"dataProvider": [' . implode(",\n",$each) . '],' .
		'"valueField": "value",' .
		'"titleField": "country",' .
		'"outlineAlpha": 0.4,' .
		'"depth3D": 15,' .
		"\"balloonText\": \"[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>\"," .
		'"angle": 30,' .
		'"export": {' .
			'"enabled": true' .
		'}' .
		'} );';
		$counter ++; 
	}
	echo implode("\n",$whole);
?>
	
jQuery( '.chart-input' ).off().on( 'input change', function() {
  var property = jQuery( this ).data( 'property' );
  var target = chart;
  var value = Number( this.value );
  chart.startDuration = 0;

  if ( property == 'innerRadius' ) {
    value += "%";
  }

  target[ property ] = value;
  chart.validateNow();
} );

var chart = AmCharts.makeChart("chartdiv2", {
	"type": "serial",
     "theme": "light",
	"categoryField": "year",
	"rotate": true,
	"startDuration": 1,
	"categoryAxis": {
		"gridPosition": "start",
		"position": "left"
	},
	"trendLines": [],
	"graphs": [
	<?php 
		$whole = array();
		$firstVal = reset($sentimentArray);
		$counter = 0;
		foreach ($firstVal as $key => $value){
			$whole[] = "{" . 
			"\"balloonText\":\"{$returned[$value['search_id']]['name']}:[[value]]\"," .
			"\"fillAlphas\": 0.8," .
			"\"id\": \"AmGraph-{$counter}\"," .
			"\"lineAlpha\": 0.2," .
			"\"title\": \"{$returned[$value['search_id']]['name']}\"," .
			"\"type\": \"column\"," .
			"\"valueField\": \"search{$key}\"" .
			"}";
			$counter++;
		}
		echo implode(",\n",$whole);
	?>
	],
	"guides": [],
	"valueAxes": [
		{
			"id": "ValueAxis-1",
			"position": "top",
			"axisAlpha": 0
		}
	],
	"allLabels": [],
	"balloon": {},
	"titles": [],
	"dataProvider": [
	<?php 
		$whole = array();
		foreach ($sentimentArray as $key => $value) {
			$each = array("\"year\":\"{$key}\"");
			foreach ($value as $key1 => $value1) $each[] = "\"search{$key1}\":{$value1['count']}";
			$whole[] = "{" . implode(",\n",$each) . "}";
		}
		echo implode(",\n",$whole);
	?>
	],
    "export": {
    	"enabled": true
     }

});
</script>