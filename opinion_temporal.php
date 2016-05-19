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
	
	$sentimentStr = $sql_tools->GetSentimentStr(GetGet("sentiment", ""));
	$temporalStr = $sql_tools->GetTemporalStr(GetGet("fromdate", ""), GetGet("todate", ""), GetGet("fromhour", ""), GetGet("tohour", ""));
	$where = $sql_tools->CreateWhere(array("lang = 'es'",$sentimentStr,$temporalStr));
	
	$returned = $sql_tools->GetSearchsActive(GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']), GetGet("active", 1));
	
	$results = $sql_tools->GetAverageValues(array_column($returned,'id'), "day", $where, GetGet("catType", ""));
	
	if(empty($results)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "amcharts",
		  "version" =>  "3.19.2",
		  "files"   =>  array("amcharts.js","themes/light.js","serial.js","amstock.js")
		)
	  )
	));
	
?>

<div id="content" style="width:100%">
	<center>
		<div id="chartdiv" style="width:95%;height:500px;">Chart will render here</div>
	</center>
</div>

<script language="javascript" type="text/javascript">

  <?php
  if (!empty($sentimentStr)) echo "$('#category-type-filter').hide();";
  ?>

  <?php 
	
  	$counter = 1;
	foreach($results as $values){
		
		$pieces = array();
		foreach($values as $value)
			$pieces[] = '{' . implode(",\n",array("date:new Date('" . explode(' ',$value["created_at"])[0] . "')","value:{$value['count']}","volume:{$value['count']}")) . '}';
		
		echo "var chartData{$counter} = [" . implode(",\n",$pieces) . "];\n";
		$counter++;
	}
  ?>

  var chart = AmCharts.makeChart( "chartdiv", {
    type: "stock",
    "theme": "light",

    dataSets: [
  <?php 
  	$counter = 1;
	$dataSets = array();
  	foreach ($results as $search_id => $value) { 
		$dataSets[] = "{" .
		"\"title\": \"{$returned[$search_id]['name']}\"," .
		'"fieldMappings":[{"fromField":"value","toField":"value"},{"fromField":"volume","toField":"volume"}],' .
		"\"dataProvider\":chartData{$counter}," .
		(($counter > 1) ? "\"compared\": true," : "") .
		'"categoryField": "date"' .
		"}";
		$counter++; 
    }
	
	echo implode(",\n",$dataSets);
  ?>
	],

    panels: [ {

		"title": "Value",
		"showCategoryAxis": false,
		parseDates: true,
		"percentHeight": 70,
		"valueAxes": [ {
			"id": "v1",
			"dashLength": 5
		} ],

        stockGraphs: [ {
          id: "g1",

          valueField: "value",
          comparable: true,
          compareField: "value",
          balloonText: "[[title]]:<b>[[value]]</b>",
          compareGraphBalloonText: "[[title]]:<b>[[value]]</b>"
        } ],

        stockLegend: {
          periodValueTextComparing: "[[percents.value.close]]%",
          periodValueTextRegular: "[[value.close]]"
        }
      },

      {
        title: "Volume",
        percentHeight: 30,
		"marginTop": 1,
		"showCategoryAxis": true,
		"valueAxes": [ {
		  "dashLength": 5,
		  "stackType": "regular"
		} ],
		"categoryAxis": {
		  "dashLength": 5
		},
        stockGraphs: [ {
          valueField: "volume",
          type: "column",
          showBalloon: false,
          fillAlphas: 1,
		  comparable: true,
          compareGraphType : 'column',
          compareGraphFillAlphas: 1,
          clustered: true
        } ],


        stockLegend: {
          periodValueTextRegular: "[[value.close]]"
        }
      }
    ],

    chartScrollbarSettings: {
      graph: "g1",
	  //graphType: "line",
      //usePeriod: "WW"
    },

    chartCursorSettings: {
      valueBalloonsEnabled: true,
      fullWidth: true,
      cursorAlpha: 0.1,
      valueLineBalloonEnabled: true,
      valueLineEnabled: true,
      valueLineAlpha: 0.5
    },
	
	panelsSettings: {
        recalculateToPercents: "never"
    },

    periodSelector: {
      position: "left",
      periods: [ 
      {
        period: "DD",
        //selected: true,
        count: 10,
        label: "10 days"
      },{
        period: "MM",
        selected: true,
        count: 1,
        label: "1 month"
      }, {
        period: "YYYY",
        //selected: true,
        count: 1,
        label: "1 year"
      }, {
        period: "YTD",
        label: "YTD"
      }, {
        period: "MAX",
        label: "MAX"
      } ]
    },

    dataSetSelector: {
      position: "left"
    },
    "export": {
      "enabled": true
    }
  } );
</script>
