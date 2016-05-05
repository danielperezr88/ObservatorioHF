<?php

	$catType = GetGet("catType", "");

?>

<div class="picker-type col-xs-4 col-lg-2" id="category-type-filter">
	<div class="form-group">
		<label for="catType" class="col-sm-5 col-xs-7 control-label">Type:</label>
        <div class="col-sm-7 col-xs-5">
			<select id="catType" name="catType" style="width:100%;" onchange="submitCatType()" >
				<option value="add" <?php if ($catType == "add")  echo "selected"; ?>>Participation</option>
				<option value="substract" <?php if ($catType != "add")  echo "selected"; ?>>Opinion</option>
			</select>
		</div>
	</div>
</div>

<script type="text/javascript">

	function submitCatType(){
    	catTypeVars = "&catType=" + $( "#catType" ).val();
    	submitTab(currentTab);
    }

</script>