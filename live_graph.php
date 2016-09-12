<?php
	include "php/tools.php";
	include "php/dbConnection.php";
	
    if(empty($userData = GetLoggedUser())){
        echo('<h3 style="margin-top:0;padding-left:1em;">No logged user data found...</h3>');
        continue; //hacking attempt
    }
	
	$returned = $sql_tools->GetSearchsActive(GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']));

	$refreshInterval = 4; // seconds
	$minutesDelay = 5; // minutes
	
    loadDependencies(array(
      "scripts"     =>  array(
        array(
          "library" =>  "fusioncharts",
          "version" =>  "3.10.1",
          "files"   =>  array("fusioncharts.js","fusioncharts.charts.js","themes/fusioncharts.theme.fint.js")
        )
      )
    ));

?>
	
	<div id="content" style="width:90%" >
		<center>
			<div id="chart-real-time">Chart will render here</div>
		</center>
	</div>
<script type="text/javascript">

    if(document.getElementById("stockMonitor"))
        document.getElementById("stockMonitor").dispose();

	FusionCharts.ready(function(){
    var myChart = new FusionCharts({
        type: 'realtimeline',
        dataFormat: 'json',
        id: 'stockMonitor',
        renderAt: 'chart-real-time',
        width: '100%',
        height: '400',
        dataSource: {
            "dataset": [
            <?php 
                $dataset = array();
                $ids = array();
                foreach ($returned as $row){
                    $dataset[] = "{" .
                    "\"seriesname\":\"{$row['name']}\"," .
                    '"showvalues":"0","data":[{"value":"0"}]' .
                    "}";
                    $ids[] = $row['id'];
                }
                echo implode(",\n",$dataset);
                $idsStr = empty($ids) ? '' : '?searchs=' . implode(',',$ids) . '&refresh=' . $refreshInterval . '&delay=' . $minutesDelay;
            ?>
            /*"dataset": [
                {
                    "seriesname": "HRYS Price",
                    "showvalues": "0",
                    "data": [
                        { "value": "35.1" }
                    ]
                },
                {
                    "seriesname": "NYSE Index",
                    "showvalues": "0",
                    "parentyaxis": "S",
                    "data": [
                        { "value": "10962.87" }
                    ]
                }
            ],*/
            ],
            "chart": {
                "caption": "Temporal Evolution",
                "subCaption": "Summatory",
                "captionFontSize": "14",
                "subcaptionFontSize": "14",
                "baseFontColor" : "#333333",
                "baseFont" : "Helvetica Neue,Arial",                        
                "subcaptionFontBold": "0",
                "paletteColors" : "#0075c2,#1aaf5d,#f2c500",
                "bgColor" : "#ffffff",
                "canvasBgColor" : "#ffffff",
                "showBorder" : "0",
                "showShadow" : "0",
                "showCanvasBorder": "0",
                "showRealTimeValue": "0",
                "legendBorderAlpha": "0",
                "legendShadow": "0",
                "numberprefix": "",
                /*"setadaptiveymin": "1",
                "setadaptivesymin": "1",*/
                "xaxisname": "Time",
                "labeldisplay": "Rotate",
                /*"slantlabels": "1",*/
                "yaxisminValue": "0",
                "yaxismaxValue": "1",
                /*"pyaxisminvalue": "35",
                "pyaxismaxvalue": "36",
                "syaxisminvalue": "10000",
                "syaxismaxvalue": "12000",*/
                "divlineAlpha" : "100",
                "divlineColor" : "#999999",
                "showAlternateHGridColor" : "0",
                "divlineThickness" : "1",
                "divLineIsDashed" : "1",
                "divLineDashLen" : "1",
                "divLineGapLen" : "1",
                "numDisplaySets": "10",
                "dataStreamUrl": "real_time.php<?php echo $idsStr;?>",
                "refreshInterval": "<?php echo $refreshInterval;?>"
            },
            "categories": [
                {
                    "category": [
                        { "label": "Day Start" }
                    ]
                }
            ],
            /*"trendlines": [
                {
                    "line": [
                        {
                            "parentyaxis": "P",
                            "startvalue": "35.1",
                            "displayvalue": "Open",
                            "thickness": "1",
                            "color": "#0075c2",
                            "dashed": "1"
                        },
                        {
                            "parentyaxis": "S",
                            "startvalue": "10962.87",
                            "displayvalue": "Open",
                            "thickness": "1",
                            "color": "#1aaf5d",
                            "dashed": "1"
                        }
                    ]
                }
            ]*/
        },
        /*"events": {
            "initialized": function (e) {
                function formatTime(num){
                    return (num <= 9)? ("0"+num) : num;
                }
                function updateData() {
                    // Get reference to the chart using its ID
                    var chartRef = FusionCharts("stockMonitor"),
                        //We need to create a querystring format incremental update, containing
                        //label in hh:mm:ss format
                        //and a value (random).
                        currDate = new Date(),
                        label = formatTime(currDate.getHours()) + ":" + formatTime(currDate.getMinutes()) + ":" + formatTime(currDate.getSeconds()),
                        //Get random number between 35.25 & 30.75 - rounded to 2 decimal places
                        hrys = Math.floor(Math.random()     
                                          * 50) / 100 + 35.25,
                        //Get random number between 10962.87 & 11052.87
                        //nyse = Math.floor(Math.random()     
                        //                  * 9000)/100 + 10962.87,
                        nyse = Math.floor(Math.random()     
                                          * 50) / 100 + 35.25,
                        //Build Data String in format &label=...&value=...
                        strData = "label=" + label + "&value=" + hrys + "|" + nyse;
                    //Feed it to chart.
                    chartRef.feedData(strData);
                    console.log(nyse);
                }
                var myVar = setInterval(function () {
                    updateData();
                }, 1000);
            }
        }*/
    }).render();
	});

	</script>
