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
	$concepts = array();
	foreach($values as $value)
	{
		$toDecode = "{".str_replace(']','',str_replace('[','', str_replace('",', '":', $value["concepts"])))."}";
		$decoded =  json_decode($toDecode, true);
		if ($concepts == array())
		{
			$concepts = $decoded;
		}
		else{
			$concepts = ArrayAdd($concepts,$decoded);
		}
	}
	if (empty($concepts)){
		echo('<h3 style="margin-top:0;padding-left:1em;">No matching results found...</h3>');
		return;
	}
	
	arsort($concepts);
	$concepts = array_slice($concepts, 0, 20);
	
	// Because it was ordered, I know directly the min and max values
	$maxVal = array_values($concepts)[0];
	$minVal =  array_values($concepts)[count($concepts)-1];
	
	$m2 = 60;
	$m1 = 16;
	if ($maxVal != $minVal)
		$slope = ($m2 -$m1)/($maxVal - $minVal);
	else
		$slope = ($m2 -$m1);
	
	$conceptsFont = array();
	foreach ($concepts as $key=> $value) {
		$conceptsFont[$key] = ($value-$minVal)*$slope + $m1;
	}
	
	loadDependencies(array(
	  "scripts"     =>  array(
		array(
		  "library" =>  "d3",
		  "version" =>  "3.5.12",
		  "files"   =>  array("d3.min.js","layouts/d3.layout.cloud.js")
		)
	  )
	));
	
?>

<div style="width: 100%;">
	<center>
		<div id="chart"></div>
	</center>
</div>

<script language="javascript" type="text/javascript">
  
	var fill = d3.scale.category20();
	var width = 800;
	var height = 600;
	
  d3.layout.cloud().size([height, width])
      //.words([
      //  ".NET", "Silverlight", "jQuery", "CSS3", "HTML5", "JavaScript", "SQL","C#"].map(function(d) {
      //  return {text: d, size: 10 + Math.random() * 50};
      //}))
      .words([
      <?php 
		  $words = array();
		  foreach ($conceptsFont as $key => $value)
			$words[] = "{text:\"".str_replace("'","\\'",$key)."\",size:{$value}}";
		  echo implode(",\n",$words);
	  ?>
        ])
      .rotate(function() { return 0; })
      .font("Impact")
      .fontSize(function(d) { return d.size; })
      .on("end", draw)
      .start();

  function draw(words) {
    d3.select("#chart").append("svg:svg")
        .attr("width", width)
        .attr("height", height)
      .append("g")
        .attr("transform", "translate("+width/2+","+height/2+")")
      .selectAll("text")
        .data(words)
      .enter().append("text")
        .style("font-size", function(d) { return d.size + "px"; })
        .style("font-family", "Impact")
        .style("fill", function(d, i) { return fill(i); })
        .attr("text-anchor", "middle")
        .attr("transform", function(d) {
          return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
        })
        .text(function(d) { return d.text; });
  }

</script>
