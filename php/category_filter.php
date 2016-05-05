<?php 

	include "php/dbTools.php";
	include "php/dbConnection.php";
	
	$userData = GetLoggedUser();
	if($userData == array())
	{
		exit; //hacking attempt
	}
	
	$pid = GetGet("pid", "");
	if (!$pid)
	{
		$projectRows = $sql_tools->GetProjects($userData["id"]);
		$first_proj = array_values($projectRows)[0]; 
		$pid = $first_proj["id"];
	}
	
	$searchs = array();
	$returned = $sql_tools->GetSearchsActive($pid);//print_r($returned);
	foreach($returned as $row)
	{
	 	$searchs[] = $row['id'];
	}
	
	$categories = array_merge(array(array("sentiment" => "-all-")), $sql_tools->GetCategories($searchs));
	
	$sentiment = GetGet("sentiment", "");
	if ($sentiment == "")
	{
		$sentiment = $categories[0]["sentiment"];
	}
	//print_r($categories);
	//$onChangeLink = GetBaseLinkWithoutParam("sentiment")."&sentiment=";
	
	$isCategoryFilter = true;
?>

<div class="picker-category col-xs-3 col-sm-2 " id="category-filter" >
	<div class="form-group">
		<label for="categorySelector" class="col-xs-7 control-label">Category:</label>
		<div class="col-xs-5">
			<select id="categorySelector" name="categorySelector" onchange="submitCateg()" >
				<?php foreach ($categories as $category) { ?>
				  <option value="<?php echo $category["sentiment"]; ?>" <?php if ($category["sentiment"] == $sentiment) echo "selected" ?> ><?php echo $category["sentiment"]; ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>

<script language="javascript" type="text/javascript">
  var categVars = "&sentiment=" + <?php echo '"'.$sentiment.'"'; ?>;
  function submitCateg(){
    	categVars = "&sentiment=" + $( "#categorySelector" ).val();
    	submitTab(currentTab);
    }
</script>