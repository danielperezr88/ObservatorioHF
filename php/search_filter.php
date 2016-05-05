<?php 
	
	$pid = GetGet("pid", "");
	if (!$pid)
	{
		$projectRows = $sql_tools->GetProjects($userData["id"]);
		$first_proj = array_values($projectRows)[0]; 
		$pid = $first_proj["id"];
	}
	
	$searchs = array();
	$returned = $sql_tools->GetSearchsActive($pid);
	foreach($returned as $row)
	{
	 	$searchs[] = $row['id'];
	}
	
	//$leaders = $sql_tools->GetLeaders($searchs, "lang = 'es'"); // TODO: Change for most common words
	
	$values = $sql_tools->GetFrecuents($searchs);
	$concepts = array();
	foreach($values as $value)
	{
		$toDecode = "{".str_replace(']','',str_replace('[','', str_replace('",', '":', $value["concepts"])))."}";
		$toDecode =  str_replace("bytearrayb'","",$toDecode);
		//$decoded = "{".str_replace(']','',str_replace('[','', str_replace('",', '":', $value["concepts"])))."}";
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
	
	$searchVars = explode(",",GetGet("searchVars", ""));
	if($searchVars) foreach($searchVars as $k => $v) $searchVars[$k] = ".tagit('createTag','" . $v . "')";
	
	$isSearchFilter = true;
  
?>
  
<div class="picker-wsearcher well ui-widget col-xs-8 col-sm-6 col-md-5 col-lg-4" id="search-filter" >
	<div class="form-group">
		<label for="wordsToSearch" class="col-sm-5 col-xs-6 control-label">Words to search:</label>
		<div class="col-sm-7 col-xs-6">
			<div class="col-xs-8">
				<ul name="wordsToSearch" id="wordsToSearch"></ul>
			</div>
			<div style="display:none;">
				<input name="myWordsToSearch" id="myWordsToSearch" value="" type="hidden">
			</div>
			<div class="col-xs-4" style="padding-right: 7px;padding-left: 7px;">
				<input name="submitSearch" id="submitSearch" type="submit" value="Search" class="btn" onclick="submitWSearch()" />
			</div>
		</div>
	</div>
</div>


 
<script type="text/javascript">
  var searchVars = "";
  	var sampleTags = [
    <?php
    	$counter = 0;
    	foreach ($concepts as $key => $value) {
    	echo "\"".str_replace("\"","",$key)."\"";
    	if ($counter < count($concepts)-1) echo ",";
    	$counter++;
    } ?>
    ];
    
    $(window).load(function(){
		$('#wordsToSearch').tagit({
			availableTags: sampleTags,
			// This will make the user has to press the backspace key twice to remove the last tag
			removeConfirmation: true,
			// This will make Tag-it submit a single form value, as a comma-delimited field.
			singleField: true, 
			singleFieldNode: $('#myWordsToSearch')
		})<?php echo implode($searchVars);?>;
	});
	function submitWSearch(){
    	var wordsToSearch = $("#myWordsToSearch").val().trim();
    	if (wordsToSearch != "")
				searchVars = "&searchVars=" + wordsToSearch;
			else
				searchVars = "";
    	submitTab(currentTab);
	}
  
</script>
