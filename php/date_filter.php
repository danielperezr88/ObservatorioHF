<?php 
	
  $defaultFromDate = date('d-m-Y', strtotime(date('d-m-Y')." -30 days"));
  $defaultToDate = date('d-m-Y', strtotime(date('d-m-Y')." 0 days"));//$week_end;
      
  $fromDate = GetGet("fromdate",date('d-m-Y', strtotime(date('d-m-Y')." -30 days")));
  $toDate = GetGet("todate",date('d-m-Y', strtotime(date('d-m-Y')." 0 days")));//$week_end;
  
  $isTemporalFilter = true;
  
?>

  <div class="picker-date col-sm-12 col-lg-6" id="date-filter">
	<div class="form-group">
		<label for="fromdate" class="col-xs-1 control-label">Dates:</label>
        <div class="col-xs-3" >
			<div class="col-xs-5" >
				<input type="text" id="fromdate" name="fromdate" value="<?php echo $fromDate; ?>" class="validate-numeric datepick">
			</div>
			<label for="todate" class="col-xs-2 control-label" >-</label>
			<div class="col-xs-5" >
				<input type="text" id="todate" name="todate" value="<?php echo $toDate; ?>" class="validate-numeric datepick">
			</div>
		</div>
        <!--<div class="clearfix visible-xs"></div>-->
		<div class="col-xs-2 temp-btn" >
			<input type="button" id="minusMonth" alt="- Month" value="- Month" onclick="decMonth()" class="btn" />
		</div>
		<div class="col-xs-2 temp-btn" >
			<input type="button" id="plusMonth" alt="+ Month" value="+ Month" onclick="incMonth()" class="btn" />
		</div>
        <!--<div class="clearfix visible-sm invisible-xs"></div>-->
		<div class="col-xs-2 temp-btn" >
			<input type="button" id="setDefaults" value="Set defaults" onclick="setDefaults()" class="btn" />
		</div>
        <div class="col-xs-2 temp-btn" >
			<input name="submitTemp" id="submitTemp" type="submit" value="Filter" class="btn" onclick="submitTemps()" />
		</div>
	</div>
  </div>
  <div class="clearfix visible-sm visible-xs"></div>
  
  <script language="javascript" type="text/javascript">
	  
	  var tempVars = 	"&fromdate=" 	+ <?php echo '"'.$fromDate.'"'; 	?> +
						"&todate=" 		+ <?php echo '"'.$toDate.'"'; 		?>;
	  
	  $(function() {
		SetDatepicks("es-ES");
		
		/*$( "#submitTemp" ).click(function(){
			tempVars = "&fromdate=" + $( "#fromdate" ).val();
			tempVars = tempVars + "&todate=" + $( "#todate" ).val();
			tempVars = tempVars + "&fromhour=" + $( "#fromhour" ).val();
			tempVars = tempVars + "&tohour=" + $( "#tohour" ).val();
			submitTab();
		});*/
	  });
	  
	  function submitTemps(){
		tempVars = "&fromdate=" + $( "#fromdate" ).val();
		tempVars = tempVars + "&todate=" + $( "#todate" ).val();
		submitTab(currentTab);
	  }
	  
	  function incMonth()
	  {
		addToMonth("#fromdate", +1);
		addToMonth("#todate", +1);
	  }
	  
	  function decMonth()
	  {
		addToMonth("#fromdate", -1);
		addToMonth("#todate", -1);
	  }
	  
	  function addToMonth(inputName, toAdd)
	  {
		//alert($( inputName ).datepicker( "getDate" ));
		var previousValDate = $( inputName ).datepicker( "getDate" );
		var newDate = new Date(previousValDate.setMonth(previousValDate.getMonth()+toAdd))
		$( inputName ).val( ("0" + newDate.getDate()).slice(-2) + "-" + ("0" + (newDate.getMonth() + 1)).slice(-2) + "-" +  newDate.getFullYear());
	  }
	  
	  function setDefaults()
	  {
		$( "#fromdate" ).val("<?php echo $defaultFromDate; ?>");
		$( "#todate" ).val("<?php echo $defaultToDate; ?>");
	  }
	  
  </script>