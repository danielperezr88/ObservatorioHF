<?php

if(!class_exists("sql_tools"))
{
  
  date_default_timezone_set('Europe/Madrid');
  
  class sql_tools
  {
    var $conn;
    var $table;
    
    function sql_tools($conn)
    {
      $this->conn = $conn;
    }
    
    function GetTweeterAccounts($dbUserId)
    {
      return self::GetValuesFromQuery("SELECT * FROM config Where user_id =".$dbUserId);
    }
    
    function GetTweeterAccount($dbUserId, $tweeterId)
    {
      return self::GetValuesFromQuery("SELECT * FROM config Where user_id =".$dbUserId." AND id=".$tweeterId." limit 1");
    }
    
    function GetTweeterAccountWhere($dbUserId, $where)
    {
      return self::GetValuesFromQuery("SELECT * FROM config Where user_id =".$dbUserId." AND ".$where);
    }
    
    function InsertTweeterAccount($dbUserId, $consumer_key, $consumer_secret, $access_token_key, $access_token_secret)
    {
      return self::ExecuteQuery("INSERT INTO config (user_id, ckey, consumer_secret, access_token_key, access_token_secret) VALUES('".$dbUserId."', '".$consumer_key."', '".$consumer_secret."', '".$access_token_key."', '".$access_token_secret."')");
    }

    function InsertUser($mail, $hashed_info, $spice, $name, $user_id)
    {
      return self::ExecuteQuery("INSERT INTO users (hashed_info, spyce, user_email, name, user_id) VALUES('".$hashed_info."','".$spice."','".$mail."','".$name."','".$user_id."')");
    }
    
    function UpdateTweeterAccount($dbUserId, $tweeterId, $consumer_key, $consumer_secret, $access_token_key, $access_token_secret)
    {
      return self::ExecuteQuery("UPDATE config set ckey='".$consumer_key."', consumer_secret='".$consumer_secret."', access_token_key='".$access_token_key."', access_token_secret='".$access_token_secret."' where user_id=".$dbUserId." AND id=".$tweeterId.";");
    }
    
    function DeleteTweeterAccount($dbUserId, $tweeterId)
    {
      return self::ExecuteQuery("DELETE FROM `config` WHERE `user_id`=".$dbUserId." AND `id`=".$tweeterId);
    }

    function GetUserData($mail)
    {
      return self::GetValuesFromQuery("SELECT * FROM users WHERE user_email = '".$mail."'");
    }    
    
    function GetUserIds()
    {
      return self::GetValuesFromQuery("SELECT user_id FROM users");
    }    
    
    function GetProjects($dbUserId)
    {
      return self::GetValuesFromQuery("SELECT * FROM projects Where user_id =".$dbUserId);
    }
    
    function GetProject($dbUserId, $projId)
    {
      $returned = self::GetValuesFromQuery("SELECT * FROM projects Where user_id =".$dbUserId." AND id=".$projId);
      if (is_array($returned))
      {
        return $returned[0];
      }
      return $returned;
    }
    
    function SetNameProject($dbUserId, $pid, $newname)
    {
      return self::ExecuteQuery("UPDATE projects set name='".$newname."' where id=".$pid." AND user_id=".$dbUserId.";");
    }
    
    function SetNameSearch($dbUserId, $sid, $newname)
    {
      
      return self::ExecuteQuery("UPDATE searchs set name='".$newname."' where id=".$sid." AND project_id IN ( SELECT ID FROM projects Where user_id =".$dbUserId.");");
    }
    
    function CreateProject($dbUserId, $projectName)
    {
      return self::ExecuteQuery("INSERT INTO projects (user_id,name) VALUES('".$dbUserId."', '".$projectName."')");
    }
    
    function GetSearchStats($dbUserId, $pid)
    {
      $toReturn = array();
      $query = "SELECT count(id) as maxSearchs FROM `config` where `user_id`=".$dbUserId;
      $toReturn["maxActiveSearchs"] = self::GetValuesFromQuery($query)[0]["maxSearchs"];
      
      $query = "SELECT count(t1.id) as activeSearchs FROM `projects` as t1 INNER JOIN searchs as t2 ON t2.project_id = t1.id and t2.active = 1 WHERE t1.`user_id`=".$dbUserId;
      $toReturn["currentActiveSearchs"] = self::GetValuesFromQuery($query)[0]["activeSearchs"];
      
      $query = "SELECT count(id) as activeSearchs FROM `searchs` WHERE `active`=1 and `project_id`=".$pid;
      $toReturn["projectActiveSearchs"] = self::GetValuesFromQuery($query)[0]["activeSearchs"];
      
      return $toReturn;
    }
    
    function GetSearchs($dbProjectId)
    {
      return self::GetValuesFromQuery("SELECT t1.id as id, t1.name as name, t1.active as active, t2.string as search FROM `searchs` as t1 JOIN search_strings as t2 ON t1.search_string_id=t2.search_string_id where t1.project_id=".$dbProjectId);
    }
    
    function GetProjectSearchsInfo($dbProjectId)
    {
    	$returned =  self::GetValuesFromQuery("SELECT count(id) as searchs, COALESCE(SUM(active), 0) as active FROM `searchs` Where project_id =".$dbProjectId);
    	if (is_array($returned))
      {
        return $returned[0];
      }
      return $returned;
    }
    
    function GetSearch($userId, $dbSearchId)
    {
      $returned = self::GetValuesFromQuery("SELECT t1.project_id as project_id, t1.name as name, t3.string as search FROM searchs as t1 RIGHT JOIN projects as t2 on t1.project_id=t2.id JOIN search_strings as t3 on t1.search_string_id=t3.search_string_id Where t1.id = ".$dbSearchId." AND t2.user_id=".$userId." limit 1");
      if (is_array($returned))
      {
        return $returned[0];
      }
      return $returned;
    }
    
    function GetFirstFreeConfig($userId)
    {
      $query = "SELECT * FROM `config` WHERE `user_id`=".$userId." AND id NOT IN (SELECT t2.config_id FROM `projects` as t1 INNER JOIN searchs as t2 ON t2.project_id = t1.id AND t2.active =1 WHERE t1.`user_id`=".$userId.") LIMIT 1";
      return self::GetValuesFromQuery($query);
    }
    
    function CreateWhere($w_array)
    {
      foreach($w_array as $key => $element) if(empty($element)) unset($w_array[$key]);
      return implode(' AND ',$w_array);
    }
    
    function CreateSearch($userId, $pid, $searchName, $searchStr, $active)
    {
      $configId = 0;
      if ($active)
      {
        $firstConfig = self::GetFirstFreeConfig($userId);
        if ($firstConfig)
        {
          $configId =$firstConfig[0]["id"];
        }
      }
      $active = $active? 1 : 0;
      $searchStr = mysql_real_escape_string ($searchStr) ;
	  $addSearchStrQuery = "INSERT INTO search_strings (string) VALUES ('".$searchStr."')";
	  $findLastSearchStrIdQuery = "SELECT search_string_id FROM search_strings ORDER BY search_string_id DESC LIMIT 1";
	  self::ExecuteQuery($addSearchStrQuery);
	  $id = self::GetValuesFromQuery($findLastSearchStrIdQuery);
	  $id = (is_array($id)) ? $id[0] : $id;
      $query = "INSERT INTO searchs (project_id,name, search_string_id, config_id, active) VALUES('".$pid."', '".$searchName."', '".$id."', ".$configId.", ".$active.");";
      //print($query );
      return self::ExecuteQuery($query);
    }
    
    function SetActiveValueSearch($userId, $searchId, $activeValue)
    {
      $activeValue = $activeValue == 1? 1 : 0; // Be sure no hacking attempt was done
      if ($activeValue == 1)
      {
        $configId = self::GetFirstFreeConfig($userId);
        if ($configId)
        {
          //print_r($configId[0]["id"]);
          // TODO: Create an instant query to obtain the first available config and insert it into the row
          return self::ExecuteQuery("UPDATE searchs set active=".$activeValue.", config_id=".$configId[0]["id"]." where id=".$searchId." LIMIT 1;");
        }
        //else hacking attempt?
      }
      else
      {
        return self::ExecuteQuery("UPDATE searchs set active=".$activeValue.", config_id=0 where id=".$searchId." LIMIT 1;");
      }
    }
    
    function GetSearchsActive($dbProjectId, $active = 1, $id = "")
    {
      $query = "SELECT id, name FROM searchs Where project_id =".$dbProjectId." and active = ".$active;
      if ($id != "")
        $query .= " and id = ".$id." LIMIT 1";
      //print($query);
	  //error_log($query);
      return self::GetValuesFromQuery($query, true);
    }
    
    function GetValuesLastSeconds($search_ids, $seconds_window, $delay_minutes)
    {
      if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
	
	  $month_from = date('Y_m', strtotime("-".$delay_minutes." minutes -".$seconds_window." seconds"));
      $month_to = date('Y_m', strtotime("-".$delay_minutes." minutes"));
      
	  if (strcmp($month_to,$month_from) == 0) {
        $query = "SELECT t2.search_id, SUM(CAST(t1.polarity>0 AS SIGNED INTEGER)*2-1) AS value, COUNT(t1.polarity) AS volume "
	      ."FROM cnt_extra_".$month_from." AS t1 JOIN cnt_info_".$month_from." AS t2 ON t1.cnt_id = t2.cnt_id WHERE t2.search_id IN (".$search_ids .") "
          ."AND t1.created_at > (now() - INTERVAL ".$seconds_window." SECOND - INTERVAL ".$delay_minutes." MINUTE) "
          ."AND t1.created_at < (now() - INTERVAL ".$delay_minutes." MINUTE) group by t2.search_id";
        return self::GetValuesFromQuery($query);
	  } else {
	    $query1 = "SELECT t2.search_id, SUM(CAST(t1.polarity>0 AS SIGNED INTEGER)*2-1) AS value, COUNT(t1.polarity) AS volume "
	      ."FROM cnt_extra_".$month_from." AS t1 JOIN cnt_info_".$month_from." AS t2 ON t1.cnt_id = t2.cnt_id WHERE t2.search_id IN (".$search_ids .") "
          ."AND t1.created_at > (now() - INTERVAL ".$seconds_window." SECOND - INTERVAL ".$delay_minutes." MINUTE) group by t2.search_id";
		$query2 = "SELECT t2.search_id, SUM(CAST(t1.polarity>0 AS SIGNED INTEGER)*2-1) AS value, COUNT(t1.polarity) AS volume "
	      ."FROM cnt_extra_".$month_to." AS t1 JOIN cnt_info_".$month_to." AS t2 ON t1.cnt_id = t2.cnt_id WHERE t2.search_id IN (".$search_ids .") "
          ."AND t1.created_at < (now() - INTERVAL ".$delay_minutes." MINUTE) group by t2.search_id";
		return array_merge(self::GetValuesFromQuery($query1), self::GetValuesFromQuery($query2));
	  }
    }
    
    /*function GetInstantValues($search_ids,$where = "")
    {
	  if (!is_array($search_ids)) $search_ids = array($search_ids);
      //$query = "SELECT `search_id`,`created_at`, `sentiment`, `confidence` FROM `tweets` ";
      $query = "SELECT * FROM `tweets` ";
  
      $finalQuery = " order by 'created_at'";
      
      $toReturn = array();
      foreach($search_ids as $search_id)
	  {
		  $result = self::GetValuesFromQueryStr($search_id,$query, $where, $finalQuery);
          if(!empty($result)) $toReturn[$search_id] = $result;
      }
      return $toReturn;
    }*/
    
    function GetAverageValues($search_ids, $intervalType, $fromDate = "", $toDate = "", $where_arr = array(), $catType= "subtract")
    {
	  if (!is_array($search_ids))
		$search_ids = array($search_ids);
	  $search_ids = implode(',', $search_ids);
	  
	  $fromStr = " FROM cnt_extra_%s as t1 JOIN cnt_info_%s as t2 on t1.cnt_id = t2.cnt_id";
	  
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = [];
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
	  
	  if ($catType == "add")
        $query = "SELECT t2.search_id AS search_id, date(t1.created_at) AS created_at, COUNT(t1.polarity) as count";
      else
        $query = "SELECT t2.search_id AS search_id, date(t1.created_at) AS created_at, SUM(CAST(t1.polarity>0 AS SIGNED INTEGER)*2-1) as count";
  
      $finalQuery = "";
      if ($intervalType == "hour")
      {
        $finalQuery .= " GROUP BY DATE(t1.created_at), HOUR(t1.created_at), t2.search_id";
      }
      else if ($intervalType == "day")
      {
        $finalQuery .= " GROUP BY DATE(t1.created_at), t2.search_id";
      }
      else if ($intervalType == "week")
      {
        $finalQuery .= " GROUP BY CONCAT(YEAR(t1.created_at), '/', WEEK(t1.created_at)), t2.search_id";
      }
      else if ($intervalType == "month")
      {
        $finalQuery .= " GROUP BY CONCAT(YEAR(t1.created_at), '/', MONTH(t1.created_at), t2.search_id";
      }
      $finalQuery .= " ORDER BY t1.created_at, t2.search_id";
	  
	  $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t2.search_id IN (".$search_ids.")"));
      
      $toReturn = array();
      foreach($dates as $date){
		$res = self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . $whereQuery . $finalQuery);
		foreach($r in $res){
		  if(!array_key_exists($r['search_id'])) $toReturn[$r['search_id']] = array();
	      $toReturn[$r['search_id']] = array_merge($res,$toReturn[$r['search_id']]);
		}
	  }
      
      return $toReturn;
    }
    
    function GetResearch($search_ids, $fromDate, $toDate, $searchVars, $where_arr = array())
    {
	  if (!is_array($search_ids))
		$search_ids = array($search_ids);
	  $search_ids = implode(',', $search_ids);
	  
	  $toSearch = implode("%' AND t3.content LIKE '%",explode(',',$searchVars));
	  $toSearch = !empty($toSearch) ? " t3.content LIKE '%".$toSearch."%'" : $toSearch;
	  
	  $fromStr = " FROM cnt_extra_%s AS t1 JOIN cnt_info_%s AS t2 ON t1.cnt_id = t2.cnt_id";
	  $extraFrom = " JOIN cnt_scraped_%s AS t3 ON t1.cnt_id = t3.cnt_id";
	  
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
      
      $query = "SELECT t2.search_id AS search_id, DATE_FORMAT(t1.created_at, '%Y-%m-%d %H:%i:00') as created_norm, SUM(CAST(t1.polarity>0 AS SIGNED INTEGER)*2-1) as count";
	  
	  $whereQuery = " WHERE " . self::CreateWhere(!empty($toSearch) ? $where_arr + array($toSearch,"t2.search_id IN (".$search_ids.")") : $where_arr + array("t2.search_id IN (".$search_ids.")"));
	  
      $finalQuery = " GROUP BY t2.search_id, DATE(t1.created_at ) ORDER BY t1.created_at ASC"; // ,HOUR(`created_at` ), MINUTE(`created_at` )
      
	  $toReturn = array();
      foreach($dates as $date)
		$toReturn = array_merge($toReturn,self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . (!empty($toSearch) ? sprintf($extraFrom,$date) : "") . $whereQuery . $finalQuery));
      return $toReturn;
    }
    
    function GetTotal($search_ids,$fromDate, $toDate, $where_arr = array())
    {
	  if (!is_array($search_ids))
		$search_ids = array($search_ids);
	  $search_ids = implode(',', $search_ids);
	  
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
	  
      $query = "SELECT t2.search_id AS search_id, IF(t1.polarity>38,'pos',IF(t1.polarity<-38,'neg','unk')) AS sentiment, COUNT(t1.polarity) AS count";
	  $fromStr = " FROM cnt_extra_%s AS t1 JOIN cnt_info_%s AS t2 ON t1.cnt_id = t2.cnt_id";
	  $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t2.search_id IN (".$search_ids.")"));
      $finalQuery = " GROUP BY t2.search_id`, `sentiment`";
      
      $toReturn = array();
      foreach($dates as $date){
		$res = self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . $whereQuery . $finalQuery);
		foreach($r in $res){
		  if(!array_key_exists($r['search_id'])) $toReturn[$r['search_id']] = array();
	      $toReturn[$r['search_id']] = array_merge($toReturn[$r['search_id']],$res);
		}
	  }
	  
	  return $toReturn;
	  
    }
    
    function GetOriginalLatLon($leaders, $fromDate, $toDate, $where_arr = array())
    {
      if (!is_array($leaders))
		$leaders = array($leaders);
      $leaders = implode(',', $leaders);
	  
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
      
	  $query = "SELECT COUNT(sentiment) as total, t1.original_lat AS original_lat, t1.original_lon AS original_lon, IF(t2.polarity>38,'pos',IF(t2.polarity<-38,'neg','unk')) as sentiment";
	  $fromStr = " FROM cnt_interactions_%s AS t1 JOIN cnt_extra_%s AS t2 ON t1.cnt_id = t2.cnt_id";
      $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t1.original_from IN (".$leaders.")","t1.original_lat <> 0","t1.original_lon <> 0"));
      $finalQuery = " GROUP BY t1.original_lat, t1.original_lon";
      
      $toReturn = array();
      foreach($dates as $date)
		$toReturn = array_merge($toReturn,self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . $whereQuery . $finalQuery));
	  
	  return $toReturn;
	  
    }
    
    function GetLatLon($fromDate, $toDate, $where_arr = array())
    {
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
	  
	  $query = "SELECT COUNT(t1.geolat) as total, t1.geolat, t1.geolon, IF(t1.polarity>38,'pos',IF(t1.polarity<-38,'neg','unk')) as sentiment";
	  $fromStr = " FROM cnt_extra_%s AS t1 JOIN cnt_info_%s AS t2 ON t1.cnt_id = t2.cnt_id";
	  $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t1.geolat <> 0"));
      $finalQuery = " GROUP BY t1.geolat, t1.geolon";
      
      $toReturn = array();
      foreach($dates as $date)
		$toReturn = array_merge($toReturn,self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . $whereQuery . $finalQuery));
	  
	  return $toReturn;      
	  
    }
    
    function CountContents($names, $fromDate, $toDate, $where_arr = array())
    {
      if (!is_array($names))
		$names = array($names);
      $names = implode(',', $names);
	  
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
	
	  $query = "SELECT t1.user_name as name, count(t1.user_name) as ct, IF(t2.polarity>38,'pos',IF(t2.polarity<-38,'neg','unk')) as sentiment";
	  $fromStr = " FROM cnt_info_%s AS t1 JOIN cnt_extra_%s AS t2 ON t1.cnt_id = t2.cnt_id";
	  $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t1.user_name IN (".$names.")"));
      $finalQuery = " GROUP BY t1.user_name";
      
      $toReturn = array();
      foreach($dates as $date)
		$toReturn = array_merge($toReturn,self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . $whereQuery . $finalQuery));
      
      return $toReturn;
	  
    }
    
    function GetLeaders($fromDate, $toDate, $where_arr = array())
    {
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
	  
      $query = "SELECT t1.original_from, count(t1.original_from) as ctF, IF(t3.polarity>38,'pos',IF(t3.polarity<-38,'neg','unk')) as sentiment";
	  $fromStr =  " FROM cnt_interactions_%s AS t1 JOIN cnt_info_%s AS t2 ON t1.cnt_id = t2.cnt_id JOIN cnt_extra_%s AS t3 ON t1.cnt_id = t3.cnt_id";
	  $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t1.original_from <> ''"));
      $finalQuery = " GROUP BY t1.original_from ORDER BY ctF DESC limit 25";
      
      $toReturn = array();
      foreach($dates as $date)
		$toReturn = array_merge($toReturn,self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date,$date) . $whereQuery . $finalQuery));
	  
      return $toReturn;
	  
    }
    
    function GetCategories($search_ids, $fromDate = "", $toDate = "", $where_arr = array())
    {
	  if (!is_array($search_ids))
		$search_ids = array($search_ids);
	  $search_ids = implode(',', $search_ids);
	  
	  $month_from = intval((new DateTime($fromDate))->format('m'));
      $month_to = intval((new DateTime($toDate))->format('m'));
	  
	  $year_from = intval((new DateTime($fromDate))->format('Y'));
      $year_to = intval((new DateTime($toDate))->format('Y'));
	  
	  $dates = array();
	  foreach(range($year_from,$year_to) as $y){
	    foreach(range(($y == $year_from) ? $month_from : 1,($y == $year_to) ? $month_to : 12) as $m){
	      $dates[] = sprintf("%04d_%02d",$y,$m);
	    }
	  }
	  
      $query = "SELECT distinct(IF(t1.polarity>30,'pos',IF(t1.polarity<-30,'neg','unk')))";
	  $fromStr = " FROM cnt_extra_%s AS t1 JOIN cnt_info_%s AS t2 ON t1.cnt_id = t2.cnt_id";
      $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("t2.search_id IN (".$search_ids .")"));
	  $finalQuery = "";
	  
	  $toReturn = array();
      foreach($dates as $date)
		$toReturn = array_merge($toReturn,self::GetValuesFromQuery($query . sprintf($fromStr,$date,$date) . $whereQuery . $finalQuery));
	  
      return $toReturn;
	  
	}
    
    function GetFrecuents($search_ids,$where_arr = array())
    {
	  if (!is_array($search_ids))
		$search_ids = array($search_ids);
	  $search_ids = implode(',', $search_ids);
	  
      $query = "SELECT concepts, urls, hashtags";
	  $fromStr = " FROM frecuents";
	  $whereQuery = " WHERE " . self::CreateWhere($where_arr + array("search_id IN (".$search_ids .")"));
      $finalQuery = "";
	  
      return self::GetValuesFromQuery($query . $fromStr . $whereQuery . $finalQuery);
	  
    }
    
    /*function GetValuesFromQueryStr($search_id,$query, $where, $finalQuery)
    {
      $whereQuery = " where search_id = ".$search_id;
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery.$finalQuery;
      //print_r($totalQuery);
      return self::GetValuesFromQuery($totalQuery);
    }*/
    
    function GetValuesFromQuery($query,$idd = false)
    {
      $toReturn = array();
      $result = mysql_query($query,$this->conn);
      if (!$result) {
        return $toReturn;
      }
      
      while ($row = mysql_fetch_array($result))
      {
		if($idd) $toReturn[$row['id']] = $row;
        else $toReturn[] = $row;
      }
      mysql_free_result($result);
      return $toReturn;
    }
    
    function ExecuteQuery($query)
    {
      $result = mysql_query($query,$this->conn);
      return $result;
    }
    
    function GetTemporalStr($fromdate, $todate, $fromhour, $tohour)
    {
		$result = array();
		if(!empty($fromdate)) $result[] = "created_at >='" . (new DateTime($fromdate))->format('Y-m-d') ."'";
		if(!empty($todate)) $result[] = "created_at <='" . (new DateTime($todate))->format('Y-m-d') . "' + interval 1 day";
		if(!empty($fromhour)) $result[] = "HOUR(created_at) >='" . $fromhour . "'";
		if(!empty($tohour)) $result[] = "HOUR(created_at) <='" . $tohour . "'";
		
		return implode(" AND ",$result);
    }
    
    function GetSentimentStr($sentiment)
    {
		return (empty($sentiment) || $sentiment == '-all-')? "" : "sentiment='" . $sentiment . "'";
    }
    
    /*function GetTemporalGetStr($fromdate, $todate, $fromhour, $tohour)
    {
      return 'fromdate='.$fromdate.'&todate='.$todate.'&fromhour='.$fromhour.'&tohour='.$tohour.'';
    }*/
    
    function ArchiveProject($projectId, $archiverId)
    {
    	// TODO: Check privileges? (others than being the owner that was checked using GetSearch() )
      
      // Check projectId has not any active search (hacking attempt?)
      $projInfo = self::GetProjectSearchsInfo($archiverId, $projectId);
      if ($projInfo["active"] > 0)
        return "Any search is still active in the project, so the project cannot be archived.";
      
	  $searchs = self::GetSearchs($projectId);
	
	  $search_ids = array();
	  foreach($searchs as $search)
	  {
	    $search_ids[] = $search["id"];
	  }
	
	  // Delete first the search, to avoid it gets started again. TODO: think in other way to lock (while the process is running)
      $returned = self::ArchiveProjects(array($projectId), $archiverId);
      if ($returned != 1)
      	return $returned;
      	
       if ($search_ids != array())
       {
         $returned = self::ArchiveSearchs($search_ids, $archiverId);
       }
       return 1;
    }
    
    function ArchiveSearch($searchId, $archiverId)
    {
      // TODO: Check privileges? (others than being the owner that was checked using GetSearch() )
      
      // Check SearchId is not active (hacking attempt?)
      $searchInfo = self::GetSearch($archiverId, $searchId);
      if ($searchInfo["active"])
        return "Search '".$searchInfo["name"]."' is still active, so it cannot be archived.";
      
      // Delete first the search, to avoid it gets started again. TODO: think in other way to lock (while the process is running)
      $returned = self::ArchiveSearchs(array($searchId), $archiverId);
      
    }
    
    function ArchiveProjects($project_ids, $archiverId)
    {
    	if (is_array($project_ids))
        $project_ids = implode(',', $project_ids);
        
      $query = "INSERT INTO `arch_projects` (`archiver_id`, `id`, `name`, `user_id`)
        SELECT ".$archiverId.",`id`, `name`, `user_id`
        FROM `projects` where `id` in (".$project_ids.")";
      
      $returned = self::ExecuteQuery($query);
      if ($returned == 1)
      	self::RemoveProjects($project_ids);
      
      return 1;
    }
    
    function ArchiveSearchs($search_ids, $archiverId)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
        
      $query = "INSERT INTO `arch_searchs` (`archiver_id`, `id`, `project_id`, `config_id`, `name`, `search_string_id`, `active`)
        SELECT ".$archiverId.",`id`, `project_id`, `config_id`, `name`, `search_string_id`, `active`
        FROM `searchs` where `id` in (".$search_ids.")";
      
      $returned = self::ExecuteQuery($query);
      if ($returned == 1)
      {
      	self::RemoveSearchs($search_ids);
      	
      	self::ArchiveFrecuents($search_ids, $archiverId);
      	//self::ArchiveTweets($search_ids, $archiverId);
      	return 1;
      }
      else
      	return $returned;
    }
    
    function ArchiveFrecuents($search_ids, $archiverId)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
        
      $query = "INSERT INTO `arch_frecuents`(`archiver_id`,`search_id`, `sentiment`, `created_at`, `concepts`, `urls`, `leaders`, `hashtags`)
        SELECT ".$archiverId.",`search_id`, `sentiment`, `created_at`, `concepts`, `urls`, `leaders`, `hashtags`
        FROM `frecuents` where `search_id` in (".$search_ids.")";
      
      $returned = self::ExecuteQuery($query);
      if ($returned == 1)
      	self::RemoveFrecuents($search_ids);
      
      return 1;
    }
    
    /*function ArchiveTweets($search_ids, $archiverId)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
        
      $query = "INSERT INTO `arch_tweets` (`archiver_id`,`search_id`, `id`, `created_at`, `name`, `id_str`, `location`, `lang`, `geo`, `text`, `sentiment`, `confidence`, `sentimentVal`, `geoLat`, `geoLon`, `retweetedFrom`, `retweetedLat`, `retweetedLon`, `words`, `saved`)
        SELECT ".$archiverId.",`search_id`, `id`, `created_at`, `name`, `id_str`, `location`, `lang`, `geo`, `text`, `sentiment`, `confidence`, `sentimentVal`, `geoLat`, `geoLon`, `retweetedFrom`, `retweetedLat`, `retweetedLon`, `words`, `saved`
        FROM `tweets` where `search_id` in (".$search_ids.")";
      
      $returned = self::ExecuteQuery($query);
      if ($returned == 1)
      	self::RemoveTweets($search_ids);
      
      return 1;
    }*/
    
    function RemoveProjects($project_ids)
    {
    	if (is_array($project_ids))
        $project_ids = implode(',', $project_ids);

    	$query = "DELETE FROM `projects` where `id` in (".$project_ids.")";
    	
    	return self::ExecuteQuery($query);
    }
    
    function RemoveSearchs($search_ids)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);

    	$query = "DELETE FROM `searchs` where `id` in (".$search_ids.")";
    	
    	return self::ExecuteQuery($query);
    }
    
    function RemoveFrecuents($search_ids)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);

    	$query = "DELETE FROM `frecuents` where `search_id` in (".$search_ids.")";
    	
    	return self::ExecuteQuery($query);
    }
    
    /*function RemoveTweets($search_ids)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);

    	$query = "DELETE FROM `tweets` where `search_id` in (".$search_ids.")";
    	
    	return self::ExecuteQuery($query);
    }*/
  }
}
?>
