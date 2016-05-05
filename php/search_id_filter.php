<?php 
	
	$userData = GetLoggedUser();
	if($userData == array())
	{
		exit; //hacking attempt
	}
	
	$pid = GetGet("pid", current($sql_tools->GetProjects($userData["id"]))['id']);
  	$active = GetGet("active", 1);
	
	$searchs = $sql_tools->GetSearchsActive($pid, $active);
	$sid = GetGet("sid", (count($searchs) > 0)? current($searchs)['id'] : '');
	
	$isSearchIdFilter = true;
	
?>

<div class="picker-category col-xs-4 col-md-3 col-lg-2" id="search-id-filter" >
	<div class="form-group">
		<label for="searchSelector" class="col-xs-8 control-label">Search Name:</label>
		<div class="col-xs-4">
			<select id="searchSelector" name="searchSelector" onchange="submitISearch()">
				<?php foreach ($searchs as $search) { ?>
				  <option value="<?php echo $search["id"]; ?>" <?php if ($search["id"] == $sid) echo "selected" ?> ><?php echo $search["name"]; ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>

<script language="javascript" type="text/javascript">
  var searchVars = "&sid=" + <?php echo '"'.$sid.'"'; ?>;
  function removeOptions(selectbox)
  {
      var i;
      for(i=selectbox.options.length-1;i>=0;i--)
      {
          selectbox.remove(i);
      }
  }
  /*$(function() {
    $( "#searchSelector" ).change(function(){
    	searchVars = "&sid=" + $( "#searchSelector" ).val();
    	submitTab();
    });
  });*/
  function submitISearch(){
    	searchVars = "&sid=" + $( "#searchSelector" ).val();
    	submitTab(currentTab);
  }
</script>
