<?php 
  //include "php/tools.php";
  include "php/dbConnection.php";

$searchs = GetGet("searchs", "");
$refreshInterval = GetGet("refresh", "");
$delayMinutes = GetGet("delay", 5);
$returned  = $sql_tools->GetValuesLastSeconds($searchs, $refreshInterval, $delayMinutes);
$returnedSearch = array();
foreach($returned as $val)
{
  $returnedSearch[$val["search_id"]] = $val["value"];
}
$searchIds = explode (',', $searchs);

$label  = date("H:i:s");

$values = array();
foreach($searchIds as $id )
{
  if (array_key_exists($id, $returnedSearch))
    $values[] = $returnedSearch[$id] ;
  else
    $values[] = ""; // To print empty to avoid to print a 0 
}
$value = implode("|", $values);
//$value1 = 0 + round(mt_rand() / mt_getrandmax(), 2);
//$value2 = 0 + round(mt_rand() / mt_getrandmax(), 2);
//$value= $value1."|".$value2;
//if (endsWith($label, "0") || endsWith($label, "1"))
//{
//  $value="|35.1";
//}
?>
&label=<?php echo $label; ?>&value=<?php echo $value; ?>
