<?php 

	if (!function_exists('StartsWith'))
	{
		function recurse_copy($src,$dst) { 
	    $dir = opendir($src); 
	    @mkdir($dst);	 
	    while(false !== ( $file = readdir($dir)) ) { 
	      if (( $file != '.' ) && ( $file != '..' )) { 
	        if ( is_dir($src . '/' . $file) ) { 
	          recurse_copy($src . '/' . $file,$dst . '/' . $file); 
	        } 
	        else { 
	          copy($src . '/' . $file,$dst . '/' . $file); 
	        } 
	      } 
	    } 
	    closedir($dir); 
		} 
		function StartsWith($haystack, $needle) {
	    // search backwards starting from haystack length characters from the end
	    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
		function EndsWith($haystack, $needle) {
	    // search forward starting from end minus needle length characters
	    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
		}
		function Contains($haystack, $needle)
		{
			return (strpos($haystack,$needle) !== false);
		}
		
		function getPost($key, $default) {
	    if (isset($_POST[$key]))
	        return $_POST[$key];
	    return $default;
		}
		
		function GetGet($key, $default) {
	    if (isset($_GET[$key]))
	        return $_GET[$key];
	    return $default;
		}
		
		function Normalize($arrayData, $fieldName) {
			// Get max value
			$firstVal = true;
			$minVal = 0;
			foreach($arrayData as $value)
			{
				if ($firstVal)
				{
					$minVal = (int)$value[$fieldName];
					$firstVal = false;
				}
				else 
				{
					$minVal = min($minVal,$value[$fieldName]);
				}
			}

			foreach($arrayData as $key => $value)
			{
				$arrayData[$key][$fieldName] = (int) ($value[$fieldName] / $minVal);
			}
			//print_r($arrayData);
	    return $arrayData;
		}
		
		function GetLoggedUser()
		{
			$cookie_name = "userData";
		  if(isset($_COOKIE[$cookie_name]))
		  {
		    return unserialize($_COOKIE[$cookie_name]);
		  }
		  else
		    return array();
		}
		
		function SetLoggedUser($userData)
		{
			$cookie_name = "userData";
			setcookie($cookie_name, serialize($userData), time() + (86400 * 30), "/");
		}
		
		function UnsetLoggedUser()
		{
			$cookie_name = "userData";
			setcookie($cookie_name, '', time() - 3600, "/");
		}
		
		function GetBaseLinkWithoutParam($paramNames)
		{              
			$parts = explode('?',$_SERVER["REQUEST_URI"],2);
			$newGets = GetParamsWithout($paramNames);
			return  $parts[0]."?".$newGets;
		}
		
		function GetParamsWithout($paramNames)
		{
			$getsArray =  array();
			if (!is_array($paramNames))
				$paramNames = array($paramNames);
			foreach($_GET as $key => $getVal)           
			{
				if (!in_array($key, $paramNames))
					$getsArray[] = $key."=".$getVal;
			}
			return  implode("&",$getsArray);
		}
		
		function ArrayAdd($array1, $array2)
		{
			foreach ($array2 as $key => $value )
			{
				if (array_key_exists($key , $array1))
					$array1[$key] += $value;
				else
					$array1[$key] = $value;
			}
			return $array1;
		}
		
		function GetColour ($value, $maxValue)
		{
			$colors = array("#FF0F00", "#FF6600", "#FF9E01", "#FCD202", "#F8FF01", "#B0DE09", "#04D215", "#0D8ECF", "#0D52D1", "#2A0CD0", "#8A0CCF", "#CD0D74", "#754DEB", "#DDDDDD", "#999999", "#333333", "#000000");
			$ratio = (float)$value / $maxValue;
			$colorVals = count($colors);
			$index = $colorVals - round($ratio * ($colorVals-1)) - 1;
			return $colors[$index];
		}
	
		function retrieveVersion($versions,$position = "last"){
			
			if(empty($versions)) return null;
			
			usort($versions,'version_compare');
			switch($position){
				case "latest":
				case "last":
					return end($versions);
				case "first":
				case "earliest":
					return current($versions);
				default:
					if(intval($position)>=count($versions)) return null;
					return $versions[intval($position)];
			}
		}
		
		function maybeLoadDeps($deps = array(),$kinda = "scripts"){
			
			foreach($deps as $dep){
				
				$version = $dep['version'];
				$library = $dep['library'];
				
				switch($version){
					case "latest":
					case "last":
					case "first":
					case "earliest":
						$version = retrieveVersion(preg_filter("/{$library}_v(.*)/","$1",scandir("{$kinda}/")),$version);
					default:
						if(!$version) continue;
						$library = "{$kinda}/{$library}_v{$version}";
					case "external":
						if(empty($dep['files'])) continue;
						foreach($dep['files'] as $file){
							if($kinda == "scripts")
								echo "<script src=\"{$library}/{$file}\"></script>\n";
							else{
								if($dep['isAsync']){
									echo '<style type="text/css">';
									require $library.'/'.$file;
									echo '</style>';
								}
								else
									echo "<link href=\"{$library}/{$file}\" />\n";
							}
						}
				}
			}
		}
		
		function loadDependencies($dependencies = array()){
			if(empty($dependencies)) return false;
			foreach($dependencies as $kinda => $deps) maybeLoadDeps($deps,$kinda);
			return true;
		}


		function renderHTMLTabs($pageContents){

			if( !is_array($pageContents) ) return false;
			if( empty($pageContents) ) return false;
			if( !is_array($pageContents['tabs']) ) return false;
			if( empty($pageContents['tabs']) ) return false;
			if( !is_array($pageContents['paramsWithout']) ) return false;

			$dateToday = date('Y-m-d');

			$noCatGets = GetParamsWithout($pageContents['paramsWithout']);
			$noCatGets = !empty($noCatGets) ? '&' . $noCatGets : '';
			$noCatGets = $noCatGets . '&preventCache=' . $dateToday;
			$result = "";

			foreach($pageContents['tabs'] as $tab){
				
				if( !is_array($tab) ) continue;
				if( empty($tab['id']) || empty($tab['name']) || empty($tab['rendererUri'])) continue;
		        
		        $result .= "\n\t" . '<li><a id="' . $tab['id'] . '" href="#tabcontent" onclick="submitTab(\'' . $tab['rendererUri'] . '\');">' . $tab['name'] . '</a></li>';

		    }
			
			if( empty($result) ) return false; ?>

		  	<div id="content">
			  <div id="preloader">
			    	<center><img src="img/Loading.gif" align="absmiddle"></center>
			  </div>
			  <div id="tabs" style="display:none;">

			    <ul><?php echo $result; ?></ul>
				
				<div id="filter-container" class="container-fluid"><?php
					foreach($pageContents['tabs'] as $tab)
						foreach($tab['filters'] as $filter)
							include_once "php/{$filter}_filter.php";
				?></div>
			    
			    <div id="tabcontent">
			    <?php
			    	include $pageContents['tabs'][0]['rendererUri'];
			    ?>
				</div>

			  </div>
			</div>
		  
			<script language="javascript" type="text/javascript">

			  var extraVars = '<?php echo $noCatGets;?>';

			  $(function() {
			    $("#tabs").tabs().show();
			    $("#preloader").hide();
			  });
			  
			  function submitTab(selectedTab)
			  {
			  	var allVars = "";
			  	if (typeof tempVars != 'undefined') {
					allVars = allVars + tempVars;
				}
			  	if (typeof categVars != 'undefined') {
					allVars = allVars + categVars;
				}
				if (typeof catTypeVars != 'undefined') {
					allVars = allVars + catTypeVars;
				}
			  	if (typeof searchVars != 'undefined') {
					allVars = allVars + searchVars;
				}
			  	if (typeof searchIdVars != 'undefined') {
					allVars = allVars + searchIdVars;
				}
			  	if (typeof activeVars != 'undefined') {
					allVars = allVars + activeVars;
				}
			  	if (typeof extraVars != 'undefined') {
					allVars = allVars + extraVars;
				}
			  	var uri = selectedTab + '?' + allVars.substring(1);
			  	//alert(selectedTab);

			  	$('div[class|=picker]').hide();
<?php		  	foreach($pageContents['tabs'] as $tab)
			  		echo "\t\t\t\tif(selectedTab == '{$tab['rendererUri']}') $('" . '#'.str_replace('_','-',implode('-filter,#',$tab['filters'])).'-filter' . "').show();\n";
			  	?>

			  	loadTabContent(uri);
			  }
			</script>

			<?php

			return true;

		}
	}
?>