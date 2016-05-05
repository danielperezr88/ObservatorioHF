/*!
 * Tools.js
 * http://technologyforemotions.com/
 * Version: 1.0.0
 *
 * Copyright 2015 Oscar Zaragoza
 * Released under the MIT license
 */
function loadTabContent(tabUrl){
  $("#preloader").show();
	$.ajax({
      url: tabUrl, 
      cache: false,
      success: function(result){
        $("#tabcontent").html(result).trigger("load-filters");
        $("#preloader").hide();
    }});
}

function trimTextBoxes()
{
    $('#validatedForm input[type="text"]').each( function()
    {
      var $el = $(this);
      $el.val($.trim($el.val()));
    });
}

function CheckValidDate(objectDate, errorText) {
  var res = objectDate.val().split("-");
  if (res.length < 2)
  {
    alert(errorText);
    objectDate.focus();
    return false;
  }
  var d = new Date(res[2], res[1], res[0]); // Date(year, month, day)
  if ( Object.prototype.toString.call(d) === "[object Date]" ) {
    // it is a date
    if ( isNaN( d.getTime() ) ) {  // d.valueOf() could also work
      // date is not valid
      alert(errorText);
      objectDate.focus();
      return false;
    }
    else {
      // date is valid
      return true;
    }
  }
  else {
    // not a date
    alert(errorText);
    objectDate.focus();
    return false;
  }
}

function SetDatepicks(langTag)
{
  $('#tabcontent').on('filters-loaded',function(){
	  $('.datepick').each(function(){
		if (langTag == "es-ES")
		{
		  $(this).datepicker({
			firstDay: 1,
			monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
			'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
			dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
			dateFormat: 'dd-mm-yy',
		  });
		}
		else
		  $(this).datepicker({
			dateFormat: 'dd-mm-yy',
		  });
		  
	  });
  });
}

function GetCanvasJS( container, titleStr, titleY, titleX, chartData, onClickMethod, emotionsShades, isPercent)
{
  var chartObj = new CanvasJS.Chart(container,
	{
	  colorSet: emotionsShades,
		title:{
			text: titleStr
		},   
    animationEnabled: true,  
		axisY:{ 
			title: titleY,
			includeZero: true                    
		},
		axisX: {
			title: titleX,
			interval: 1,
			intervalType: "week",
			valueFormatString: "DD/MM/YY", 
			labelAngle: -45 
		},
		toolTip: {
			shared: true,
			content: function(e){
				var body = new String;
				var head ;
				for (var i = 0; i < e.entries.length; i++){
					var  str = "<span style= 'color:"+e.entries[i].dataSeries.color + "'> " + e.entries[i].dataSeries.name + "</span>: <strong>"+  e.entries[i].dataPoint.y + "</strong>" ;
					str += isPercent? " % ": "";
					str += "<br/>";
					body = body.concat(str);
				}

				head = "<span style = 'color:DodgerBlue; '><strong>"+ (e.entries[0].dataPoint.x.getDate()) + "-" + (e.entries[0].dataPoint.x.getMonth()+1) + "-" + (e.entries[0].dataPoint.x.getFullYear()) +"</strong></span><br/>";

				return (head.concat(body));
			}
		},
		legend: {
			horizontalAlign :"center"
		},
		data: chartData,
        legend :{
          cursor:"pointer",
          itemclick : function(e) {
            if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
			        e.dataSeries.visible = false;
            }
            else{
			        e.dataSeries.visible = true;
            }
            onClickMethod();
          }
        }
	});
	chartObj.render();
	return chartObj;
}


function GetLineChartData(chartID, labelsArray, label1, dataset1, label2, dataset2)
{
  var datasetsArray = [{
				label: label1,
				//fillColor: "rgba(151,187,205,0.2)",
				//strokeColor: "rgba(151,187,205,1)",
				//highlightFill : "rgba(74,211,97,0.75)",
        //highlightStroke : "rgba(74,211,97,1)",
        fillColor: "rgba(151,187,205,0.2)",
				strokeColor: "rgba(151,187,205,1)",
				highlightFill : "rgba(151,187,205,0.75)",
        highlightStroke : "rgba(151,187,205,1)",
				data: dataset1
			}];
	if (typeof label2 !== 'undefined' && typeof dataset2 !== 'undefined')
	{
	  datasetsArray.push({
				label: label2,
				//fillColor: "rgba(220,220,220,0.2)",
				//strokeColor: "rgba(220,220,220,1)",
				//highlightFill: "rgba(88,196,246,0.75)",
        //highlightStroke: "rgba(88,196,246,1)",
        fillColor: "rgba(220,220,220,0.2)",
				strokeColor: "rgba(220,220,220,1)",
				highlightFill: "rgba(220,220,220,0.75)",
        highlightStroke: "rgba(220,220,220,1)",
				data: dataset2
			});
	}
  var linechartData = {
		labels: labelsArray,
		datasets: datasetsArray };
	
	return new Chart(document.getElementById(chartID).getContext("2d")).Bar(linechartData, {
    barShowStroke: true,
    tooltipFillColor: "rgba(0,0,0,0.8)",
    multiTooltipTemplate: "<%= datasetLabel %> - <%= value %> %"
    });
}

function GetRadarChartData(chartID, labelsArray, label1, dataset1, label2, dataset2)
{
  var datasetsArray = [{
				label: label1,
				fillColor: "rgba(151,187,205,0.2)",
  			strokeColor: "rgba(151,187,205,1)",
  			pointColor: "rgba(151,187,205,1)",
  			pointStrokeColor: "#fff",
  			pointHighlightFill: "#fff",
  			pointHighlightStroke: "rgba(220,220,220,1)",
				data: dataset1
			}];
	if (typeof label2 !== 'undefined' && typeof dataset2 !== 'undefined')
	{
	  datasetsArray.push({
	    label: label2,
	    fillColor: "rgba(220,220,220,0.2)",
			strokeColor: "rgba(220,220,220,1)",
			pointColor: "rgba(220,220,220,1)",
			pointStrokeColor: "#fff",
			pointHighlightFill: "#fff",
			pointHighlightStroke: "rgba(151,187,205,1)",
			data: dataset2
			});
	}
  
  var radarChartData = {
  	labels: labelsArray,
  	datasets: datasetsArray 
  	 };
  	
  return new Chart(document.getElementById(chartID).getContext("2d")).Radar(radarChartData, {
		responsive: true,
		tooltipFillColor: "rgba(0,0,0,0.8)",
		multiTooltipTemplate: "<%= datasetLabel %> - <%= value %> %",
		scaleShowLabels : true
		});
}
