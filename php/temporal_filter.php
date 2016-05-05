<?php 
	
  $defaultFromDate = date('d-m-Y', strtotime(date('d-m-Y')." -30 days"));
  $defaultToDate = date('d-m-Y', strtotime(date('d-m-Y')));//$week_end;
  $defaultFromHour = 0;
  $defaultToHour = 24;
      
  $fromDate = GetGet("fromdate",$defaultFromDate);
  $toDate = GetGet("todate",$defaultToDate);//$week_end;
  $fromHour = GetGet("fromhour",$defaultFromHour);
  $toHour = GetGet("tohour",$defaultToHour);
  
  $isTemporalFilter = true;
  
  loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "moment",
		  "version" =>  "2.12.0",
		  "files"   =>  array("moment.min.js")
		),
		array(
		  "library" =>  "daterangepicker",
		  "version" =>  "2.1.19",
		  "files"   =>  array("daterangepicker.js")
		)
	  ),
	  "css" 	    =>  array(
		array(
		  "library" =>  "daterangepicker",
		  "version" =>  "2.1.19",
		  "files"   =>  array("daterangepicker.css")
		)
	  )
	));
  
?>

  <div class="picker-date container" id="temporal-filter" style="position: absolute; top: 2px; right: 2px; width: auto;">
	<div class="row" style="background: #fff; padding: 5px 10px; border: 1px solid rgb(173, 173, 173); width: auto; max-height: 33px;border-radius: 7px;">
		<i class="glyphicon glyphicon-calendar fa fa-calendar" style="width: 0.7em;"></i>&nbsp;
		<div id="reportrange" class="pull-right" style="cursor: pointer; width: 21.5em; display:none;">
			<span></span> <b class="caret" style="margin-left: 12px;border-top-width: 11px;border-right-width: 6px;border-left-width: 6px;"></b>
		</div>
	</div>
  </div>
  
  <script type="text/javascript">
		
	$(".picker-date > div.row > i").unbind('click');
	
	$(".picker-date > div.row > i").on("click",function() {
		
		$(this).parents("div.picker-date").attr('extended', function(index, attr){
			
			if (attr == 'extended'){
			
				$(this).find('div.row > i').unbind('mouseenter mouseleave').hover(
					function(){$(this).removeClass('glyphicon-calendar').removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-left');},
					function(){$(this).removeClass('glyphicon-chevron-left').removeClass('glyphicon-chevron-right').addClass('glyphicon-calendar');}
				);
			
				$('#reportrange').toggle('slide','right',500,function(){$(".picker-date > .row").animate({"width":"36px"},200)});
			
			} else {
			
				$(this).find('div.row > i').unbind('mouseenter mouseleave').hover(
					function(){$(this).removeClass('glyphicon-calendar').removeClass('glyphicon-chevron-left').addClass('glyphicon-chevron-right');},
					function(){$(this).removeClass('glyphicon-chevron-right').removeClass('glyphicon-chevron-left').addClass('glyphicon-calendar');}
				);
			
				$(this).find('.row').animate({"width":"24.5em"},500,function(){$('#reportrange').toggle('slide','right',200);});
			
			}
			
			return attr == 'extended' ? null : 'extended';
		});
		
	});
	
	$(".picker-date > div.row > i").hover(
		function(){$(this).removeClass('glyphicon-calendar').addClass('glyphicon-chevron-left');},
		function(){$(this).removeClass('glyphicon-chevron-left').addClass('glyphicon-calendar');}
	);
  
  </script>

  <script type="text/javascript">
		
	var todayDate = '<?php echo sprintf("%sT%02d",$defaultToDate,0); ?>';
	var fromDate = '<?php echo sprintf("%sT%02d",$fromDate,$fromHour); ?>';
	var toDate = '<?php echo sprintf("%sT%02d",$toDate,$toHour); ?>';
	
	var formatDateBack = 'DD-MM-YYYY';
	var formatHourBack = 'HH:mm';
	var formatWholeBack = 'DD-MM-YYYYTHH';
	var formatDateFront = 'MMMM D, YYYY H:mm';
	
	var tempVars = 	"&fromdate=" 	+ <?php echo '"'.$fromDate.'"'; 	?> +
					"&todate=" 		+ <?php echo '"'.$toDate.'"'; 		?> +
					"&fromhour=" 	+ <?php echo '"'.$fromHour.'"';		?> +
					"&tohour=" 		+ <?php echo '"'.$toHour.'"'; 		?>;
					
	function submitDateTime(start,end){
		
		var fDate = start.format(formatDateBack);
		var fHour = start.format(formatHourBack);
		var tDate = end.format(formatDateBack);
		var tHour = end.format(formatHourBack);
		
		if(tHour == '23:59'){
			tHour = 24;
			tDate = end.add(1,'day').startOf('day').format(formatDateBack);
		}
		
		tempVars = "&fromdate=" + fDate;
		tempVars = tempVars + "&todate=" + tDate;
		tempVars = tempVars + "&fromhour=" + fHour.substring(0,2);
		tempVars = tempVars + "&tohour=" + tHour.substring(0,2);
		
		submitTab(currentTab);
		
	}
		
	function cb(start, end) {
		$('#reportrange span').html(start.format(formatDateFront) + ' - ' + end.format(formatDateFront));
	}
	cb(moment(fromDate,formatWholeBack), moment(toDate,formatWholeBack));

	$('#reportrange').daterangepicker({
		timePicker: true,
		timePicker24Hour: true,
		timePickerIncrement: 30,
		linkedCalendars: false,
		autoUpdateInput: false,
		ranges: {
		   'Today': [moment(todayDate,formatWholeBack).hours(0).minutes(0).seconds(0).milliseconds(0), moment(todayDate,formatWholeBack).hours(24)],
		   'Yesterday': [moment(todayDate,formatWholeBack).subtract(1,'day').hours(0).minutes(0).seconds(0).milliseconds(0), moment(todayDate,formatWholeBack).add(1,'day').endOf("day")],
		   'Last 7 Days': [moment(todayDate,formatWholeBack).subtract(6, 'days'), moment(todayDate,formatWholeBack).endOf("day")],
		   'Last 30 Days': [moment(todayDate,formatWholeBack).subtract(29, 'days'), moment(todayDate,formatWholeBack).endOf("day")],
		   //'-1 Day': [moment(fromDate,formatWholeBack).subtract(1, 'days'), moment(toDate,formatWholeBack).subtract(1, 'days')],
		   //'+1 Day': [moment(fromDate,formatWholeBack).subtract(1, 'days'), moment(toDate,formatWholeBack).subtract(1, 'days')],
		   //'-1 Week': [moment(fromDate,formatWholeBack).subtract(1, 'week'), moment(toDate,formatWholeBack).subtract(1, 'week')],
		   //'+1 Week': [moment(fromDate,formatWholeBack).subtract(1, 'week'), moment(toDate,formatWholeBack).subtract(1, 'week')],
		   //'-1 Month': [moment(fromDate,formatWholeBack).subtract(1, 'month'), moment(toDate,formatWholeBack).subtract(1, 'month')],
		   //'+1 Month': [moment(fromDate,formatWholeBack).subtract(1, 'month'), moment(toDate,formatWholeBack).subtract(1, 'month')]
		}
	}, function(start,end){
		cb(start,end);
		submitDateTime(start,end);
	});
  </script>