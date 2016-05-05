<?php 

  $userData = GetLoggedUser();
  if($userData == array())
  {
    exit; //hacking attempt
  }
  
  $active = intval(GetGet("active", "1"));
  
  $isActiveFilter = true;
?>

<div class="picker-active col-xs-4 col-md-3" id="active-filter" >
	<div class="form-group">
	  <label for="activeSelector" class="col-xs-9 control-label">Active searchs:</label>
        <div class="col-xs-3">
		  <select id="activeSelector" name="activeSelector" style="width:100%;" onchange="submitActive()">
			<option value="1" <?php if ($active === 1) echo "selected"; ?> >Active</option>
			<option value="0" <?php if ($active === 0) echo "selected"; ?> >Inactive</option>
		  </select>
		</div>
	</div>
</div>

<script language="javascript" type="text/javascript">
  var activeVars = "&active=" + <?php echo '"'.$active.'"'; ?>;
  function submitActive() {
    activeVars = "&active=" + $( "#activeSelector" ).val();
    submitTab(currentTab);
  }
</script>