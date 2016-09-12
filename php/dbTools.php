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
      return self::GetValuesFromQuery("SELECT * FROM config Where user_id =".$dbUserId." AND id=".$tweeterId);
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
      return self::GetValuesFromQuery("SELECT * FROM searchs Where project_id =".$dbProjectId);
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
      $returned = self::GetValuesFromQuery("SELECT * FROM searchs Where id = ".$dbSearchId." AND `project_id` in (SELECT id from projects WHERE user_id=".$userId.")");
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
      // TODO: Create an instant query to obtain the first available config and insert it into the row
      $query = "INSERT INTO searchs (project_id,name, search, config_id, active) VALUES('".$pid."', '".$searchName."', '".$searchStr."', ".$configId.", ".$active.");";
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
          return self::ExecuteQuery("UPDATE searchs set active=".$activeValue.", config_id=".$configId[0]["id"]." where id=".$searchId.";");
        }
        //else hacking attempt?
      }
      else
      {
        return self::ExecuteQuery("UPDATE searchs set active=".$activeValue.", config_id=0 where id=".$searchId.";");
      }
    }
    
    function GetSearchsActive($dbProjectId, $active = 1, $id = "")
    {
      $query = "SELECT * FROM searchs Where project_id =".$dbProjectId." and active = ".$active;
      if ($id != "")
        $query .= " and id = ".$id;
      //print($query);
	  //error_log($query);
      return self::GetValuesFromQuery($query, true);
    }
    
    function GetValuesLastSeconds($search_ids, $seconds_window, $delay_minutes)
    {
      if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
      
      $query = "SELECT search_id,SUM(sentimentVal) as value, COUNT(sentimentVal) FROM `tweets` where search_id IN (".$search_ids .") "
        ."AND saved > (now() - INTERVAL ".$seconds_window." SECOND - INTERVAL ".$delay_minutes." MINUTE) "
        ."AND saved < (now() - INTERVAL ".$delay_minutes." MINUTE) group by search_id";
      //print($query);
      return self::GetValuesFromQuery($query);
    }
    
    function GetInstantValues($search_ids,$where = "")
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
    }
    
    function GetAverageValues($search_ids, $intervalType, $where = "", $catType= "subsctract")
    {
	  if (!is_array($search_ids)) $search_ids = array($search_ids);
		
      if ($catType == "add")
        $query = "SELECT `search_id`,date(`created_at`) as `created_at`, `confidence`, COUNT(*) as count FROM `tweets` ";
      else
        $query = "SELECT `search_id`,date(`created_at`) as `created_at`, SUM(`confidence`)/COUNT(`confidence`) as `confidence`, SUM(`sentimentVal`) as count FROM `tweets` ";
  
      $finalQuery = "";
      if ($intervalType == "hour")
      {
        $finalQuery .= " GROUP BY date( created_at ), hour( created_at ), `search_id`";
      }
      else if ($intervalType == "day")
      {
        $finalQuery .= " GROUP BY date( created_at ),`search_id`";
      }
      else if ($intervalType == "week")
      {
        $finalQuery .= " GROUP BY CONCAT(YEAR(created_at), '/', WEEK(created_at)), `search_id`";
      }
      else if ($intervalType == "month")
      {
        $finalQuery .= " GROUP BY CONCAT(YEAR(created_at), '/', MONTH(created_at), `search_id`";
      }
      $finalQuery .= " order by `search_id`,`created_at`";
      
      $toReturn = array();
      foreach($search_ids as $search_id)
      {
		  $result = self::GetValuesFromQueryStr($search_id,$query, $where, $finalQuery);
          if(!empty($result)) $toReturn[$search_id] = $result;
      }
      
      return $toReturn;
    }
    
    function GetResearch($search_ids, $where = "")
    {
      $toReturn = array();
      
      $query = "SELECT `search_id`, DATE_FORMAT(`created_at`, '%Y-%m-%d %H:%i:00') as `created_norm`,`created_at`, `sentimentVal`, sum(`sentimentVal`) as count FROM `tweets` ";
      
      $finalQuery = " GROUP BY `search_id`, DATE(`created_at` ) ORDER BY `created_at` ASC"; // ,HOUR(`created_at` ), MINUTE(`created_at` )
      
      if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
        
      $whereQuery = " where search_id IN (".$search_ids .")";
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery.$finalQuery;
	  //error_log($totalQuery);
      $toReturn = self::GetValuesFromQuery($totalQuery);
      return $toReturn;
    }
    
    function GetTotal($search_ids,$where = "")
    {
      $query = "SELECT `search_id`, `sentiment`, count(`sentiment`) as count FROM `tweets` ";
      
      $finalQuery = " GROUP BY `search_id`, `sentiment`";
      
      $toReturn = array();
      if (is_array($search_ids))
      {
        foreach($search_ids as $search_id)
        {
          $toReturn[$search_id] = self::GetValuesFromQueryStr($search_id,$query, $where, $finalQuery);
        }
      }
      else
      {
        $toReturn[$search_ids] = self::GetValuesFromQueryStr($search_ids,$query, $where, $finalQuery);
      }
      return $toReturn;
    }
    
    function GetRetweetedLatLon($leaders, $whereStr = "")
    {
      $query = "SELECT COUNT( `sentiment`) as total,`retweetedLat`,`retweetedLon` FROM `tweets` ";
      
      $whereSearch = "";
      $isFirst = true;
      $toReturn = array();
      if (is_array($leaders))
        $leaders = implode(',', $leaders);

      $finalQuery = " group by `retweetedLat`,`retweetedLon`";
      
      $whereSearch = " retweetedFrom IN (".$leaders .")";
      $whereQuery = " where (".$whereSearch.") AND `retweetedLat` <> 0 AND `retweetedLon` <> 0 ";
      if ($whereStr != "") $whereQuery .= " and ".$whereStr;
      $totalQuery = $query.$whereQuery.$finalQuery;
      //print($totalQuery );
      return self::GetValuesFromQuery($totalQuery);
    }
    
    function GetLatLon($search_ids, $whereStr = "")
    {
	  if(!is_array($search_ids)) $search_ids = array($search_ids);
		
      $query = "SELECT COUNT( `sentiment`) as total,`geoLat`,`geoLon` FROM `tweets` ";
      $finalQuery = " group by `geoLat`,`geoLon`";
      
	  $filters = array("`geoLat` <> 0","search_id IN (". implode(',', $search_ids) .")");
	  if ($whereStr != "") $filters[] = $whereStr;
      
      return self::GetValuesFromQuery($query . ' WHERE ' . implode(' AND ',$filters) . $finalQuery);
      
      //return $toReturn;
    }
    
    function CountTweets($search_ids, $names, $where = "")
    {
      $toReturn = array();
      
      $query = "SELECT `name`, count(`name`) as ct FROM `tweets` ";
      $finalQuery = " GROUP BY `name`";
      
      
      if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
        
      if (is_array($names))
        $names = implode(',', $names);
      
      $whereQuery = " where `name` IN (".$names.") AND search_id IN (".$search_ids .")";
      
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery.$finalQuery;
      //print($totalQuery);
      $toReturn = self::GetValuesFromQuery($totalQuery);
      return $toReturn;
    }
    
    function GetLeaders($search_ids, $where = "")
    {
      $toReturn = array();
      
      //$query = "SELECT `name`, count(`name`) as ct, `retweetedFrom`, count(`retweetedFrom`) as ctF FROM `tweets` ";
      $query = "SELECT `retweetedFrom`, count(`retweetedFrom`) as ctF FROM `tweets` ";
  
      //$finalQuery = " GROUP BY `name` ORDER BY ct DESC limit 25";
      $finalQuery = " GROUP BY `retweetedFrom` ORDER BY ctF DESC limit 25";
      
      if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
      
      $whereQuery = " where search_id IN (".$search_ids .") and `retweetedFrom` <> '' ";
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery.$finalQuery;
      //print($totalQuery);
      $toReturn = self::GetValuesFromQuery($totalQuery);
      return $toReturn;
    }
    
    function GetCategories($search_ids,$where = "")
    {
      $query = "SELECT distinct(sentiment) FROM `tweets` ";
      
      if (is_array($search_ids)){
        $s_ids = implode(',', $search_ids);
        unset($search_ids);
        $search_ids = $s_ids;
      }
      $whereQuery = " where search_id IN (".$search_ids .")";
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery;
      return self::GetValuesFromQuery($totalQuery);
    }
    
    function GetFrecuents($search_ids,$where = "")
    {
      $query = "SELECT * FROM `frecuents` ";
      
      if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);
        
      $whereQuery = " where search_id IN (".$search_ids .")";
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery;//error_log(serialize($totalQuery));
      return self::GetValuesFromQuery($totalQuery);
    }
    
    function GetValuesFromQueryStr($search_id,$query, $where, $finalQuery)
    {
      $whereQuery = " where search_id = ".$search_id;
      if ($where != "") $whereQuery .= " and ".$where;
      $totalQuery = $query.$whereQuery.$finalQuery;
      //print_r($totalQuery);
      return self::GetValuesFromQuery($totalQuery);
    }
    
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
    
    function GetTemporalGetStr($fromdate, $todate, $fromhour, $tohour)
    {
      return 'fromdate='.$fromdate.'&todate='.$todate.'&fromhour='.$fromhour.'&tohour='.$tohour.'';
    }
    
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
        
      $query = "INSERT INTO `arch_searchs` (`archiver_id`, `id`, `project_id`, `config_id`, `name`, `search`, `stored_id`, `active`)
        SELECT ".$archiverId.",`id`, `project_id`, `config_id`, `name`, `search`, `stored_id`, `active`
        FROM `searchs` where `id` in (".$search_ids.")";
      
      $returned = self::ExecuteQuery($query);
      if ($returned == 1)
      {
      	self::RemoveSearchs($search_ids);
      	
      	self::ArchiveFrecuents($search_ids, $archiverId);
      	self::ArchiveTweets($search_ids, $archiverId);
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
    
    function ArchiveTweets($search_ids, $archiverId)
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
    }
    
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
    
    function RemoveTweets($search_ids)
    {
    	if (is_array($search_ids))
        $search_ids = implode(',', $search_ids);

    	$query = "DELETE FROM `tweets` where `search_id` in (".$search_ids.")";
    	
    	return self::ExecuteQuery($query);
    }
  }
}
?>
