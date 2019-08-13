<?php
/*
 *	www/include/Stats.php
 * 
 * 
* 
*/
//---------------
define('STAT_POLL_RATELIMIT', 17); // seconds
define('STAT_DEBUG_RATELIMIT_ALLOWED', false); // verbose
//---------------
define('TEST_XML', false);
//define('POLL_XML_STATS_PAGE', 'http://folding.extremeoverclocking.com/xml/user_summary.php?u=');
define('POLL_XML_STATS_PAGE', 'http://folding.extremeoverclocking.com/xml/user_summary.php?un=');
define('TEST_POLL_XML_STATS_PAGE', 'http://localhost/dogecoinfah/www/teststat');
define('CFG_LAST_POLL_STAT', 'last-poll-stat');
//---------------
libxml_use_internal_errors(true); // errors are handled nicely
//---------------
class Stat
{
	var $id = -1;
	var $mode = 0; // 0 = ROUND_STATS_MODE_NONE
	var $dtCreated = "";
	var $dtPolled = "";
	var $didPoll = false;
	var $requestTeamID = false;
	var $roundIdx = false;
	var $payoutIdx = false;
	var $workerIdx = false;
	var $userID = false;
	var $team = false;
	var $teamRank = false;
	var $rank = false;
	var $weekPoints = false;
	var $avgPoints = false;
	var $totPoints = false;
	var $wus = false; // work units
	//------------------
	function __construct($row = false)
	{
		if ($row !== false)
			$this->set($row);
	}
	//------------------
	function set($row)
	{
		//------------------
		$this->id			= $row[DB_STATS_ID];
		$this->mode 		= (isset($row[DB_STATS_MODE]) ? $row[DB_STATS_MODE] : ROUND_STATS_MODE_NONE);
		$this->dtCreated 	= (isset($row[DB_STATS_DATE_CREATED]) ? $row[DB_STATS_DATE_CREATED] : "");
		$this->dtPolled 	= (isset($row[DB_STATS_DATE_POLLED]) ? $row[DB_STATS_DATE_POLLED] : "");
		$this->roundIdx 	= $row[DB_STATS_ROUND];
		$this->payoutIdx 	= (isset($row[DB_STATS_PAYOUT]) ? $row[DB_STATS_PAYOUT] : false);
		$this->workerIdx 	= $row[DB_STATS_WORKER];
		$this->userID 		= (isset($row[DB_STATS_WORKER_USER_ID]) ? $row[DB_STATS_WORKER_USER_ID] : false);  
		$this->team			= (isset($row[DB_STATS_TEAM]) ? $row[DB_STATS_TEAM] : false);  
		$this->teamRank		= (isset($row[DB_STATS_TEAM_RANK]) ? $row[DB_STATS_TEAM_RANK] : false);  
		$this->rank			= (isset($row[DB_STATS_RANK]) ? $row[DB_STATS_RANK] : false);  
		$this->weekPoints	= (isset($row[DB_STATS_WEEK_POINTS]) ? $row[DB_STATS_WEEK_POINTS] : false);  
		$this->avgPoints	= (isset($row[DB_STATS_AVG_POINTS]) ? $row[DB_STATS_AVG_POINTS] : false);  
		$this->totPoints	= (isset($row[DB_STATS_TOTAL_POINTS]) ? $row[DB_STATS_TOTAL_POINTS] : false);  
		$this->wus			= (isset($row[DB_STATS_WUS]) ? $row[DB_STATS_WUS] : false);  
		//------------------
		if ($this->dtCreated != "")
			if (strtotime($this->dtCreated) === false)
				$this->dtCreated = "";
		if ($this->dtPolled != "")
			if (strtotime($this->dtPolled) === false)
				$this->dtPolled = "";
		//------------------
	}
	//------------------
	function setMaxSizes()
	{
		global $dbStatsFields;
		//------------------
		$this->id 	 = -1;
		$this->mode			= $dbStatsFields->GetMaxSize(DB_STATS_MODE);
		$this->dtCreated	= $dbStatsFields->GetMaxSize(DB_STATS_DATE_CREATED);
		$this->dtPolled	 	= $dbStatsFields->GetMaxSize(DB_STATS_DATE_POLLED);
		$this->roundIdx	 	= $dbStatsFields->GetMaxSize(DB_STATS_ROUND);
		$this->payoutIdx	= $dbStatsFields->GetMaxSize(DB_STATS_PAYOUT);
		$this->workerIdx	= $dbStatsFields->GetMaxSize(DB_STATS_WORKER);
		$this->userID 		= $dbStatsFields->GetMaxSize(DB_STATS_WORKER_USER_ID);
		$this->team 		= $dbStatsFields->GetMaxSize(DB_STATS_TEAM);
		$this->teamRank 	= $dbStatsFields->GetMaxSize(DB_STATS_TEAM_RANK);
		$this->rank 		= $dbStatsFields->GetMaxSize(DB_STATS_RANK);
		$this->weekPoints 	= $dbStatsFields->GetMaxSize(DB_STATS_WEEK_POINTS);
		$this->avgPoints 	= $dbStatsFields->GetMaxSize(DB_STATS_AVG_POINTS);
		$this->totPoints 	= $dbStatsFields->GetMaxSize(DB_STATS_TOTAL_POINTS);
		$this->wus 			= $dbStatsFields->GetMaxSize(DB_STATS_WUS);
		//------------------
	}
	//------------------
	function Update()
	{
		global $db, $dbStatsFields;
		//---------------------------------
		$dbStatsFields->ClearValues();
		$dbStatsFields->SetValue(DB_STATS_MODE,   $this->mode);
		$dbStatsFields->SetValue(DB_STATS_DATE_CREATED,   $this->dtCreated);
		$dbStatsFields->SetValue(DB_STATS_DATE_POLLED, $this->dtPolled);
		$dbStatsFields->SetValue(DB_STATS_ROUND, $this->roundIdx);
		$dbStatsFields->SetValue(DB_STATS_WORKER, $this->workerIdx);
		//---------------------------------
		if ($this->payoutIdx !== false)
			$dbStatsFields->SetValue(DB_STATS_PAYOUT, $this->payoutIdx);
		if ($this->userID !== false)
			$dbStatsFields->SetValue(DB_STATS_WORKER_USER_ID, $this->userID);
		if ($this->team !== false)
			$dbStatsFields->SetValue(DB_STATS_TEAM, $this->team);
		if ($this->teamRank !== false)
			$dbStatsFields->SetValue(DB_STATS_TEAM_RANK, $this->teamRank);
		if ($this->rank !== false)
			$dbStatsFields->SetValue(DB_STATS_RANK, $this->rank);
		if ($this->weekPoints !== false)
			$dbStatsFields->SetValue(DB_STATS_WEEK_POINTS, $this->weekPoints);
		if ($this->avgPoints !== false)
			$dbStatsFields->SetValue(DB_STATS_AVG_POINTS, $this->avgPoints);
		if ($this->totPoints !== false)
			$dbStatsFields->SetValue(DB_STATS_TOTAL_POINTS, $this->totPoints);
		if ($this->wus !== false)
			$dbStatsFields->SetValue(DB_STATS_WUS, $this->wus);
		//---------------------------------
		$sql = $dbStatsFields->scriptUpdate(DB_STATS_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Stat::Update - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
	function work()
	{
		//---------------------------------
		if (!$this->mode)
		{
			XLogError("Stat::work - mode not set");
			return false;
		}
		//---------------------------------
		if (XMaskContains($this->mode, ROUND_STATS_MODE_EO_WEEK_SCORE))
		{
			if (XMaskContains($this->mode, ROUND_STATS_MODE_EO_TOTAL_SCORE) || XMaskContains($this->mode, ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE))
			{
				XLogError("Stat::work - invalid mode mask combination, week and total, mode: ".XVarDump($this->mode));
				return false;
			}
			return $this->weekPoints;
		}
		//---------------------------------
		if (XMaskContains($this->mode, ROUND_STATS_MODE_EO_TOTAL_SCORE))
		{
			if (XMaskContains($this->mode, ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE))
			{
				XLogError("Stat::work - invalid mode mask combination, week and total, mode: ".XVarDump($this->mode));
				return false;
			}
			return $this->totPoints;
		}
		//---------------------------------
		if (XMaskContains($this->mode, ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE))
			return ($this->totPoints - $this->weekPoints);
			
		//---------------------------------
		if (XMaskContains($this->mode, ROUND_STATS_MODE_FAH_SCORE))
			return $this->weekPoints;
		//---------------------------------
		XLogError("Stat::work - no supported mode mask combination set, mode: ".XVarDump($this->mode));
		//---------------------------------
		return false;
	}
	//------------------
	function pollStats()
	{
		//---------------------------------
		$this->didPoll = false; // reset state
		//---------------------------------
		$Config = new Config() or die("Create object failed");
		$workers = new Workers() or die("Create object failed");
		$rounds = new Rounds() or die("Create object failed");
		//---------------------------------
		$lastPoll = $Config->Get(CFG_LAST_POLL_STAT);
		if ($lastPoll !== false)
		{
			$secDiff = XDateTimeDiff($lastPoll); // default against now in UTC returning seconds
			if ($secDiff === false)
			{
				XLogError("Stat::pollStats XDateTimeDiff failed. Cannot verify rate limit. lastPoll: '$lastPoll'");
				return false;
			}
			if ($secDiff < STAT_POLL_RATELIMIT)
			{
				XLogWarn("Stat::pollStats Rate limitted. Secs wait till $secDiff");
				return false;
			}
			else if (STAT_DEBUG_RATELIMIT_ALLOWED) XLogDebug("Stat::pollStats Allowed. Secs wait till $secDiff");
		}
		else if (STAT_DEBUG_RATELIMIT_ALLOWED) XLogWarn("Stat::pollStats No Rate limit info");
		//---------------------------------
		XLogDebug("Stat::pollStats...");
		if ($this->roundIdx === false)
		{
			XLogError("Stat::pollStats - roundIdx not set");
			return false;
		}
		//---------------------------------
		if ($this->workerIdx === false)
		{
			XLogError("Stat::pollStats - workerIdx not set");
			return false;
		}
		//---------------------------------
		$round = $rounds->getRound($this->roundIdx);
		if ($round === false)
		{
			XLogError("Stat::pollStats - roundIdx not found: ".$this->roundIdx);
			return false;
		}
		//---------------------------------
		if ($round->teamId === false || !is_numeric($round->teamId))
		{
			XLogError("Stat::pollStats - validate round teamId failed: ".XVarDump($round->teamId));
			return false;
		}
		//---------------------------------
		$worker = $workers->getWorker($this->workerIdx);
		if ($worker === false)
		{
			XLogError("Stat::pollStats - workerIdx not found: ".$this->workerIdx);
			return false;
		}
		//---------------------------------
		if (TEST_XML === true)
			$url = TEST_POLL_XML_STATS_PAGE."$worker->uname.xml";
		else
			$url = POLL_XML_STATS_PAGE."$worker->uname&t=$round->teamId.xml";
		//---------------------------------
		$stats = @simplexml_load_file($url);
		//---------------------------------
		// update last poll time and didPoll even if something failed or does fail 
		$this->didPoll = true; // set state
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		if (!$Config->Set(CFG_LAST_POLL_STAT, $nowUtc->format(MYSQL_DATETIME_FORMAT)))
		{
			XLogError("Stat::pollStats - Config set last poll stat failed");
			return false;
		}
		//---------------------------------
		if ($stats === false)
		{
			$error = libxml_get_last_error();
			if ($error !== false && strpos($error->message, "failed to load external entity") === 0)
			{
				//---------------------------------
				XLogWarn("Stat::pollStats - stats not found for user: $worker->uname, team: $round->teamId");
				//---------------------------------
				$this->dtPolled = $nowUtc->format(MYSQL_DATETIME_FORMAT);
				$this->userID	= 0;
				$this->team		= 0;
				$this->teamRank	= 0;
				$this->rank		= 0;
				$this->weekPoints = 0;
				$this->avgPoints = 0;
				$this->totPoints = 0;
				$this->wus		= 0;
				//---------------------------------
				if (!$this->Update())
				{
					XLogError("Stat::pollStats - Update failed");
					return false;
				}
				//---------------------------------
				return "Not Found";
			}
			XLogError("Stat::pollStats - simplexml_load_file failed. Error: ".($error === false ? "<no error info>" : "[$error->code] $error->message").", Url: $url");
			return false;
		}
		//---------------------------------
		if (!isset($stats->team) || !isset($stats->user))
		{
			XLogError("Stat::pollStats - reply doesn't have correct base structure");
			return false;
		}
		//---------------------------------
		if (!isset($stats->user->UserID) || !isset($stats->user->Points_Week))
		{
			XLogError("Stat::pollStats - reply is missing one or more expected fields");
			return false;
		}
		//---------------------------------
		if (!isset($stats->user->Team_Rank) || !isset($stats->user->Overall_Rank))
		{
			XLogError("Stat::pollStats - reply is missing one or more expected fields");
			return false;
		}
		//---------------------------------
		if (!isset($stats->user->Points_24hr_Avg) || !isset($stats->user->Points))
		{
			XLogError("Stat::pollStats - reply is missing one or more expected fields");
			return false;
		}
		//---------------------------------
		if (!isset($stats->team->TeamID) || !isset($stats->user->WUs))
		{
			XLogError("Stat::pollStats - reply is missing one or more expected fields");
			return false;
		}
		//---------------------------------
		$this->dtPolled = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		$this->userID	= $stats->user->UserID;
		$this->team		= $stats->team->TeamID;
		$this->teamRank	= $stats->user->Team_Rank;
		$this->rank		= $stats->user->Overall_Rank;
		$this->weekPoints	= $stats->user->Points_Week;
		$this->avgPoints = $stats->user->Points_24hr_Avg;
		$this->totPoints = $stats->user->Points;
		$this->wus		= $stats->user->WUs;
		//---------------------------------
		if (!$this->Update())
		{
			XLogError("Stat::pollStats - Update failed");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
} // class Stat
//---------------
class Stats
{
	//------------------
	var $stats = array();
	var $isLoaded = false;
	//------------------
	function Install()
	{
		global $db, $dbStatsFields;
		//------------------------------------
		$sql = $dbStatsFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Stats::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbStatsFields;
		//------------------------------------
		$sql = $dbStatsFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Stats::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbStatsFields;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case 1: // old version changed work field
				//---------------
				$dbStatsFields->SetValues();
				//---------------
				$newFields = $dbStatsFields->GetNameListString();
				$fieldsArray = $dbStatsFields->GetNameArray();
				//---------------
				for ($i=0;$i < sizeof($fieldsArray);$i++)
					if ($fieldsArray[$i] == DB_STATS_WEEK_POINTS)
						$fieldsArray[$i] = 'work';
					else if ($fieldsArray[$i] == DB_STATS_MODE)
						$fieldsArray[$i] = (ROUND_STATS_MODE_FAH_WORKER | ROUND_STATS_MODE_EO_WEEK_SCORE);
				//---------------
				$oldFields = implode(",", $fieldsArray);
				//---------------
				$sql = "INSERT INTO $dbStatsFields->tableName ($newFields) SELECT $oldFields FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Stats::Import db Execute table import failed (convert ver < 2) .\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			case $dbStatsFields->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbStatsFields->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Stats::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Stats::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function GetMaxSizes()
	{
		//------------------------------------
		$msizeStat = new Stats();
		$msizeStat->setMaxSizes();
		//------------------------------------
		return $msizeStat;		
	}
	//------------------
	function deleteStat($idx)
	{
		global $db, $dbStatsFields;
		//---------------------------------
		$sql = $dbStatsFields->scriptDelete(DB_STATS_ID."=".$idx);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Stats::deleteStat - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadStats() === false)
		{
			XLogError("Stats::deleteStat - loadStats failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear()
	{
		global $db, $dbStatsFields;
		//---------------------------------
		$sql = $dbStatsFields->scriptDelete();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Stats::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->stats = array();
		//---------------------------------
		$this->isLoaded = true;
		//------------------
		return true;
	}
	//---------------------------------	
	function loadStatRaw($idx)
	{
		global $db, $dbStatsFields;
		//------------------
		$dbStatsFields->SetValues();
		//------------------
		$sql = $dbStatsFields->scriptSelect(DB_STATS_ID."=$idx", false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Stats::loadStatRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadStat($idx)
	{
		//------------------
		$qr = $this->loadStatRaw($idx);
		//------------------
		if ($qr === false)
		{
			XLogError("Stats::loadStat - loadStatRaw failed");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogWarn("Stats::loadStat - index $idx not found.");
			return false;
		}
		//------------------
		return new Stat($s);
	}
	//------------------
	function getStat($idx)
	{
		//---------------------------------
		if ($this->isLoaded)
			foreach ($this->stats as $s)
				if ($s->id == $idx)
					return $s;
		//---------------------------------
		return $this->loadStat($idx);
	}
	//------------------
	function findStat($roundIdx, $workerIdx)
	{
		//---------------------------------
		global $db, $dbStatsFields;
		//------------------
		$dbStatsFields->SetValues();
		//------------------
		$sql = $dbStatsFields->scriptSelect(DB_STATS_ROUND."=$roundIdx AND ".DB_STATS_WORKER."=$workerIdx", false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Stats::findStat - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogNotify("Stats::findStat - not found.");
			return false;
		}
		//------------------
		return new Stat($s);
	}
	//------------------
	function findRoundStats($roundIdx, $orderBy = false, $includeWithPayout = true)
	{
		//---------------------------------
		global $db, $dbStatsFields;
		//------------------
		if ($orderBy === false)
			$orderBy = DB_STATS_ID;
		//------------------
		$where = DB_STATS_ROUND."=$roundIdx";
		if (!$includeWithPayout)
			$where .= " AND ".DB_STATS_PAYOUT." IS NULL";
		//------------------
		$dbStatsFields->SetValues();
		//------------------
		$sql = $dbStatsFields->scriptSelect($where, $orderBy);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Stats::findRoundStats - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$stats = array();
		while ($s = $qr->GetRowArray())
			$stats[] = new Stat($s);
		//------------------
		return $stats;
	}
	//------------------
	function findIncompleteStats()
	{
		//---------------------------------
		global $db, $dbStatsFields;
		//------------------
		$dbStatsFields->SetValues();
		//------------------
		$where = DB_STATS_DATE_POLLED." is NULL OR ".DB_STATS_DATE_POLLED."=''";
		//------------------
		$sql = $dbStatsFields->scriptSelect($where , DB_STATS_ID /*orderby*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Stats::findIncompleteStats - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$stats = array();
		while ($s = $qr->GetRowArray())
			$stats[] = new Stat($s);
		//------------------
		return $stats;
	}
	//------------------
	function loadStatsRaw($limit = false)
	{
		global $db, $dbStatsFields;
		//------------------
		$dbStatsFields->SetValues();
		$sql = $dbStatsFields->scriptSelect(false /*where*/, DB_STATS_ID." DESC" /*orderby*/, $limit /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Stats::loadStatsRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadStats($limit = false)
	{
		$this->stats = array();
		//------------------
		$qr = $this->loadStatsRaw($limit);
		//------------------
		if ($qr === false)
		{
			XLogError("Stats::loadStats - loadStatsRaw failed");
			return false;
		}
		//------------------
		while ($s = $qr->GetRowArray())
			$this->stats[] = new Stat($s);
		//------------------
		$this->isLoaded = true;
		//------------------
		return $this->stats;
	}
	//------------------
	function addStat($roundIdx, $mode, $workerIdx, $reload = true)
	{
		global $db, $dbStatsFields;
		//------------------
		if (!is_numeric($roundIdx) || !is_numeric($mode) || !is_numeric($workerIdx))
		{
			XLogError("Stats::addStat - validate parameters are numeric failed round idx: ".XVarDump($roundIdx).", mode:".XVarDump($mode).", worker idx:".XVarDump($workerIdx));
			return false;
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//---------------------------------
		$dbStatsFields->ClearValues();
		$dbStatsFields->SetValue(DB_STATS_DATE_CREATED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbStatsFields->SetValue(DB_STATS_ROUND, $roundIdx);
		$dbStatsFields->SetValue(DB_STATS_MODE, $mode);
		$dbStatsFields->SetValue(DB_STATS_WORKER, $workerIdx);
		//------------------
		$sql = $dbStatsFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Stats::addStat - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($reload && $this->loadStats() === false)
		{
			XLogError("Stats::addStat - loadStats failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
	function addFahClientStat($roundIdx, $mode, $workerIdx, $team, $userID, $points, $wus, $rank = false, $reload = true)
	{
		global $db, $dbStatsFields;
		//------------------
		if (!is_numeric($roundIdx) || !is_numeric($mode) || !is_numeric($workerIdx) || !is_numeric($team) || !is_numeric($userID) || !is_numeric($points) || !is_numeric($wus) ||
			($rank !== false && !is_numeric($rank)) )
		{
			XLogError("Stats::addFahClientStat - validate parameters are numeric failed round idx: ".XVarDump($roundIdx).", mode:".XVarDump($mode).", worker idx:".XVarDump($workerIdx).", team:".XVarDump($team).", userID:".XVarDump($userID).", points:".XVarDump($points).", wus:".XVarDump($wus));
			return false;
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//---------------------------------
		$dbStatsFields->ClearValues();
		$dbStatsFields->SetValue(DB_STATS_DATE_CREATED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbStatsFields->SetValue(DB_STATS_ROUND, $roundIdx);
		$dbStatsFields->SetValue(DB_STATS_MODE, $mode);
		$dbStatsFields->SetValue(DB_STATS_WORKER, $workerIdx);
		$dbStatsFields->SetValue(DB_STATS_TEAM, $team);
		$dbStatsFields->SetValue(DB_STATS_WORKER_USER_ID, $userID);
		$dbStatsFields->SetValue(DB_STATS_TOTAL_POINTS, $points);
		$dbStatsFields->SetValue(DB_STATS_WUS, $wus);
		//------------------
		if ($rank !== false)
			$dbStatsFields->SetValue(DB_STATS_RANK, $rank);
		//------------------
		$sql = $dbStatsFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Stats::addFahClientStat - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($reload && $this->loadStats() === false)
		{
			XLogError("Stats::addFahClientStat - loadStats failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
	function getStats($limit = false)
	{
		//---------------------------------
		if ($this->isLoaded)
			return $this->stats;
		//---------------------------------
		return $this->loadStats($limit);
	}
	//------------------
	function deleteAllRound($ridx)
	{
		global $db, $dbStatsFields;
		//------------------
		$sql = $dbStatsFields->scriptDelete(DB_STATS_ROUND."=$ridx");
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Stats::deleteAllRound - db Execute scriptDelete failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		//---------------------------------
		return true;
	}
	//------------------
} // class Stats
//---------------
?>
