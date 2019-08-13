<?php
/*
 *	www/adming/include/Team.php
 * 
 * 
* 
*/
//---------------
class Team
{
	//------------------
	function Install()
	{
		global $db, $dbTeamFields, $dbTeamUsersFields;
		//------------------------------------
		$sql = $dbTeamFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Team::Install db Execute create team table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		$sql = $dbTeamUsersFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Team::Install db Execute create users table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	} // Install
	//------------------
	function Uninstall()
	{
		global $db, $dbTeamFields, $dbTeamUsersFields;
		//------------------------------------
		$sql = $dbTeamFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Team::Uninstall db Execute drop team table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		$sql = $dbTeamUsersFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Team::Uninstall db Execute drop users table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	} // Uninstall
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbTeamFields;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case $dbTeamFields->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbTeamFields->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Team::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Team::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function AddFromOverview($overview)
	{
		global $db, $dbTeamFields;
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$dbTeamFields->ClearValues();
		$dbTeamFields->SetValue(DB_TEAM_DATE,   		$nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbTeamFields->SetValue(DB_TEAM_RANK,   		$overview["rank"]);
		$dbTeamFields->SetValue(DB_TEAM_TEAMS,   		$overview["total_teams"]);
		$dbTeamFields->SetValue(DB_TEAM_CREDIT,   		$overview["credit"]);
		$dbTeamFields->SetValue(DB_TEAM_WUS,   			$overview["wus"]);
		$dbTeamFields->SetValue(DB_TEAM_ACTIVE50,   	$overview["active_50"]);
		$dbTeamFields->setValue(DB_TEAM_ACTIVEDONORS, 	$overview["active_donors"]);
		//------------------
		$sql = $dbTeamFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Team::AddFromOverview - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//------------------
		return true;
	} // AddFromOverview
	//------------------
	function calcTeamStats()
	{
		global $db, $dbTeamFields, $dbTeamUsersFields;
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$teamId = $Config ->Get(CFG_ROUND_TEAM_ID);
		if ($teamId === false || !is_numeric($teamId))
		{
			XLogError("Team::calcTeamStats get team ID config failed");
			return false;
		}
		//------------------
		$nowDate = new DateTime('now',  new DateTimeZone('UTC'));
		$dayDate = new DateTime('now',  new DateTimeZone('UTC'));
		$weekDate = new DateTime('now',  new DateTimeZone('UTC'));
		$dayDate->modify("-1 day");
		$weekDate->modify("-7 day");
		//------------------
		//XLogDebug("Team::calcTeamStats - team data (now/day/week) ".$nowDate->format(MYSQL_DATETIME_FORMAT)." / ".$dayDate->format(MYSQL_DATETIME_FORMAT)." / ".$weekDate->format(MYSQL_DATETIME_FORMAT));
		//------------------
		// Query Total Worker Count
		$sql = "SELECT COUNT(DISTINCT(".DB_TEAM_USERS_USER_ID.")) FROM ".$dbTeamUsersFields->tableName;
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Team::calcTeamStats - db Query lastWeek failed.\nsql: $sql");
			return false;
		}
		//------------------
		$r = $qr->GetRow(); // returns one row, numerically indexed field array
			if (!$r || !isset($r[0]))
			{
				XLogDebug("Team::calcTeamStats validate worker count row failed");
				return false;
			}
		//------------------
		$TotalWorkerCount = $r[0];
		//------------------
		// Query Last Week of Team Data
		$dbTeamFields->SetValues();
		//------------------
		$where = DB_TEAM_DATE.">'".$weekDate->format(MYSQL_DATETIME_FORMAT)."'";
		$orderby = DB_TEAM_DATE." DESC"; // want DESC, newest to oldest
		//------------------
		$sql = $dbTeamFields->scriptSelect($where, $orderby);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Team::calcTeamStats - db Query lastWeek failed.\nsql: $sql");
			return false;
		}
		//------------------
		$dayCounter = new DateTime('now',  new DateTimeZone('UTC')); 
		$dayCounter->modify("-1 day");
		$avgDay = array();
		$avgLast = false;
		$avgCount = 0;
		$weekValues = false;
		$nowValues = false;
		$dayValues = false;
		//------------------
		while ($row = $qr->GetRowArray())
		{
			$date = new DateTime($row[DB_TEAM_DATE],  new DateTimeZone('UTC'));
			if ($nowValues === false)
				$nowValues = $row; // first record is now
			$weekValues = $row;
			if ($date < $dayCounter)
			{
				if ($dayValues === false)
					$dayValues = $row;
				if ($avgLast === false)
				{
					$avgLast = array();
					foreach ($row as $field => $value)
						$avgLast[$field] = $value;
				}
				else
				{
					foreach ($row as $field => $value)
						$avgDay[$field] = $avgLast[$field] - $value; // we are itterating from the oldest to newest
					$avgCount++;
				}
				$avgLast = $row;
				$dayCounter->modify("-1 day");
			}
			//else XLogDebug("Team::calcTeamStats date ".$date->format(MYSQL_DATETIME_FORMAT)." (".$row[DB_TEAM_DATE].") !< ".$dayCounter->format(MYSQL_DATETIME_FORMAT));
			$weekValues = $row; // very last record will be about one week ago
		}
		//------------------
		if ($avgCount != 0)
		{
			foreach ($avgDay as $field=>$value)
				$avgDay[$field] /= $avgCount;
		}
		//------------------
		if ($nowValues === false || $dayValues === false || $weekValues === false)
		{
			XLogError("Team::calcTeamStats - not all required period values were found (now, day, week)");
			return false;
		}
		//------------------
		$userStats = $this->getTeamUserStats();
		if ($userStats === false)
		{
			XLogError("Team::calcTeamStats - getTeamUserStats failed");
			return false;
		}
		//------------------
		$actAll = $userStats[0];
		$actDay = $userStats[1];
		$actWeek = $userStats[2];
		//------------------
		// members active/ x-inactive
		// rank / total teams (day / week change)
		// wus total
		// points / day (val/7)
		$rankDay = $nowValues[DB_TEAM_RANK] - $dayValues[DB_TEAM_RANK];
		$rankWeek = $nowValues[DB_TEAM_RANK] - $weekValues[DB_TEAM_RANK];
		$pointsDay = $nowValues[DB_TEAM_CREDIT] - $dayValues[DB_TEAM_CREDIT];
		$pointsWeek = $nowValues[DB_TEAM_CREDIT] - $weekValues[DB_TEAM_CREDIT];
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$json = array( 'UTC' => $nowUtc->format(DATE_ISO8601), 'name' => (string)"[team name]", 'id' => (int)$teamId, 'actusers' => (int)$actWeek, 'users' => (int)$TotalWorkerCount, 
					   'rank' => (int)$nowValues[DB_TEAM_RANK], 'rank24' => (int)$rankDay, 'rank7' => (int)$rankWeek, 
					   'points' => (int)$nowValues[DB_TEAM_CREDIT], 'points24' => (int)$avgDay[DB_TEAM_CREDIT], 'wu' => (int)$nowValues[DB_TEAM_WUS]);
		//------------------
		return $json;
	}
	//------------------
	function addUserDonor($donor, $date = false)
	{
		global $db, $dbTeamUsersFields;
		//------------------
		if (!isset($donor["wus"]) || !isset($donor["credit"]) || !isset($donor["id"]) || !is_numeric($donor["wus"]) || !is_numeric($donor["credit"]) || !is_numeric($donor["id"]))
		{
			XLogError("Team::addUserDonor - validate donor fields failed".XVarDump($donor));
			return false;
		}
		//------------------
		if ($date === false)
			$date = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$dbTeamUsersFields->ClearValues();
		$dbTeamUsersFields->SetValue(DB_TEAM_USERS_DATE,   		$date->format(MYSQL_DATETIME_FORMAT));
		$dbTeamUsersFields->SetValue(DB_TEAM_USERS_USER_ID,   	$donor["id"]);
		$dbTeamUsersFields->SetValue(DB_TEAM_USERS_POINTS,   	$donor["credit"]);
		$dbTeamUsersFields->SetValue(DB_TEAM_USERS_WUS,   		$donor["wus"]);
		//------------------
		$sql = $dbTeamUsersFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Team::addUserDonor - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function getTeamUserStats()
	{
		//------------------
		
		$dtNow = new DateTime('now',  new DateTimeZone('UTC'));
		$dtDay = new DateTime('now',  new DateTimeZone('UTC')); // needs to be seperate object to modify correctly (don't set to $dtNow)
		$dtWeek = new DateTime('now',  new DateTimeZone('UTC')); // needs to be seperate object to modify correctly (don't set to $dtNow)
		$dtDay->modify("-1 day");
		$dtWeek->modify("-7 day");
		//------------------
		$strNow = $this->getTeamUserMaxDate($dtNow);
		if ($strNow === false)
		{
			XLogError("Team::getTeamUserStats - now getTeamUserMaxDate failed.");
			return false;
		}
		//------------------
		$strDay = $this->getTeamUserMaxDate($dtDay);
		if ($strDay === false)
		{
			XLogError("Team::getTeamUserStats - day getTeamUserMaxDate failed.");
			return false;
		}
		//------------------
		$strWeek = $this->getTeamUserMaxDate($dtWeek);
		if ($strWeek === false)
		{
			XLogError("Team::getTeamUserStats - week getTeamUserMaxDate failed.");
			return false;
		}
		//------------------
		$valuesNow = $this->getTeamUserValuesByDate($strNow);
		if ($valuesNow === false)
		{
			XLogError("Team::getTeamUserStats - now getTeamUserValuesByDate failed.");
			return false;
		}
		//------------------
		$valuesDay = $this->getTeamUserValuesByDate($strDay);
		if ($valuesDay === false)
		{
			XLogError("Team::getTeamUserStats - day getTeamUserValuesByDate failed.");
			return false;
		}
		//------------------
		$valuesWeek = $this->getTeamUserValuesByDate($strWeek);
		if ($valuesWeek === false)
		{
			XLogError("Team::getTeamUserStats - week getTeamUserValuesByDate failed.");
			return false;
		}
		//------------------
		$actDay = 0;
		$actWeek = 0;
		foreach ($valuesNow as $vNow)
		{
			//------------------
			$id = $vNow[0];
			if (isset($valuesDay[$id]))
			{
				if (($vNow[1] - $valuesDay[$id][1]) > 0)
					$actDay++;
				else if ($vNow[1] !== $valuesDay[$id][1])
					XLogDebug("id $id weird no match (now/day) ".XVarDump($vNow[1])."/".XVarDump($valuesDay[$id][1]));

				if (isset($valuesWeek[$id]))
				{
					if (($vNow[1] - $valuesWeek[$id][1]) > 0)
						$actWeek++;
					else if ($vNow[1] !== $valuesWeek[$id][1])
						XLogDebug("id $id weird no match (now/week) ".XVarDump($vNow[1])."/".XVarDump($valuesWeek[$id][1]));
				}
			}
			//------------------
		} // foreach valuesNow
		//------------------
		return array(sizeof($valuesNow), $actDay, $actWeek);
	}
	//------------------
	function getTeamUserMaxDate($maxDate, $default = false)
	{
		global $db, $dbTeamUsersFields;
		//------------------
		$sql = "SELECT MAX(".DB_TEAM_USERS_DATE.") FROM ".$dbTeamUsersFields->tableName." WHERE ".DB_TEAM_USERS_DATE."<'".$maxDate->format(MYSQL_DATETIME_FORMAT)."'";
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Team::getTeamUserMaxDate - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$r = $qr->GetRow(); // returns one row, numerically indexed field array
		if (!$r || !isset($r[0]))
			return $default;
		//------------------
		return $r[0];
	}
	//------------------
	function getTeamUserValuesByDate($strDate)
	{
		global $db, $dbTeamUsersFields;
		//------------------
		$dbTeamUsersFields->ClearValues();
		//------------------
		$dbTeamUsersFields->setValue(DB_TEAM_USERS_USER_ID);
		$dbTeamUsersFields->setValue(DB_TEAM_USERS_POINTS);
		$dbTeamUsersFields->setValue(DB_TEAM_USERS_WUS);
		//------------------
		$where = DB_TEAM_USERS_DATE."='".$strDate."'";
		//------------------
		$sql = $dbTeamUsersFields->scriptSelect($where);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Team::getTeamUserValuesByDate - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$values = array();
		while ($u = $qr->GetRowArray())
			if (isset($u[DB_TEAM_USERS_USER_ID]) && isset($u[DB_TEAM_USERS_POINTS]) && isset($u[DB_TEAM_USERS_WUS]))
				$values[$u[DB_TEAM_USERS_USER_ID]] = array($u[DB_TEAM_USERS_USER_ID], $u[DB_TEAM_USERS_POINTS], $u[DB_TEAM_USERS_WUS]);
			else
				XLogDebug("Team::getTeamUserValuesByDate - not all fields set: ".XVarDump($u));
		//------------------
		return $values;
	}
	//------------------
}// class Team
//---------------
?>
