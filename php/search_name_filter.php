<?php

	$searchs = $sql_tools->GetSearchsActive($pid);
	$sid = GetGet("sid", "");
	if ($sid == "")
	{
		if (count($searchs) > 0)
			$sid = $searchs[0]["id"];
	}
	$searchidStr = "&sid=".$sid."";

?>

<div class="picker-search col-md-4 col-lg-2" id="search-name-filter">
	<div class="form-group">
		<label for="searchSelector" class="col-xs-7 control-label">Search Name:</label>
        <div class="col-xs-5">
			<select id="searchSelector" name="searchSelector" style="width:90px" onchange="submitSearchId()" >
				<?php foreach ($searchs as $search) { ?>
				  <option value="<?php echo $search["id"]; ?>" <?php if ($search["id"] == $sid) echo "selected" ?> ><?php echo $search["name"]; ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>

<script type="text/javascript">

	function submitSearchId(){
    	searchIdVars = "&sid=" + $( "#searchSelector" ).val();
    	submitTab(currentTab);
    }

</script>