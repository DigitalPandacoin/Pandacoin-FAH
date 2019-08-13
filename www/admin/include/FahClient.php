<?php
/*
 *	www/include/Wallet.php
 * 
 * 
* 
*/
//---------------
define('FAH_RPC_URL', "https://folding.stanford.edu/stats/api/team/");
define('FAH_ARCHIVE_FOLDER', './log/archive/');
//---------------
define('FAH_RPC_DEBUG_FAIL', true);
define('FAH_RPC_DEBUG_SUCCESS', true);
//------------------------
define('CFG_FAH_VALIDATION_MODE', 'fah_valid_mode');
define('DEF_CFG_FAH_VALIDATION_MODE', 0x3B); // current all (63) 0x3F, all but fail on not set (59) 0x3B, all without fail 58 (0x3A)
//------------------------
define('FAH_VALMODE_NONE', 					0x0);
define('FAH_VALMODE_FAILON_ADDRMISMATCH', 	0x1);
define('FAH_VALMODE_WARNON_ADDRMISMATCH', 	0x2);
define('FAH_VALMODE_FAILON_VALIDNOTSET', 	0x4);
define('FAH_VALMODE_WARNON_VALIDNOTSET', 	0x8);
define('FAH_VALMODE_WARNON_VALIDATION', 	0x10);
define('FAH_VALMODE_WARNON_NOVALIDATION', 	0x20);
//------------------------
require_once('jsonRPCClient.php');
//------------------
class FahClient
{
	var $clientSession = false;
	//------------------
	function pollTeam($rIdx, $teamId)
	{
		//------------------
		XLogDebug("FahClient::pollTeam beginning poll for team $teamId, round $rIdx");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$reply = $this->request($teamId);
		//------------------
		if ($reply === false)
		{
			XLogError("FahClient::pollTeam request failed");
			return false;
		}
		//------------------
		XLogDebug(XVardump($reply));
		list($rawData, $jsonData) = $reply;
		//------------------
		$fileName = FAH_ARCHIVE_FOLDER."FahClient_$rIdx.json";
		if (@file_put_contents($fileName, $rawData) === false)
		{
			XLogError("FahClient::pollTeam file_put_contents failed: $fileName");
			return false;
		}
		//------------------
		if (!file_exists($fileName))
		{
			XLogError("FahClient::pollTeam verifiy file_exists failed: $fileName");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::pollTeam request time: $secDiff");
		//------------------
		if (!$this->processData($rIdx, $jsonData))
		{
			XLogError("FahClient::pollTeam processData failed");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::pollTeam processData time: $secDiff");
		//------------------
		return true;
	}
	//------------------
	function request($teamId)
	{
		//------------------
		$client = new jsonRPCClient(FAH_RPC_URL.$teamId);
		//------------------
		try
		{
			//------------------
			if (FAH_RPC_DEBUG_SUCCESS || FAH_RPC_DEBUG_FAIL)
				$client->debug = true;
			//------------------
			$result = $client->__request(true/*wantRaw*/);
			//------------------
		}
		catch (exception $e)
		{
			//------------------
			XLogNotify("FahClient::request  debug: '$client->debug', failed with exception: $e");
			//------------------
			return false;
		}
		//------------------
		
		//------------------
		if (FAH_RPC_DEBUG_SUCCESS)
			XLogNotify("FahClient::pollTeam success debug: $client->debug");
		//------------------
		return $result;		
	}
	//------------------
	function reparseProcessData($rIdx)
	{
		//------------------
		$fileName = FAH_ARCHIVE_FOLDER."FahClient_$rIdx.json";
		$data = @file_get_contents($fileName);
		if ($data === false)
		{
			XLogError("FahClient::reparseProcessData file_get_contents failed: $fileName");
			return false;
		}
		//------------------
		$data = json_decode($data, true);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			XLogError("FahClient::reparseData json_decode data failed");
			return false;
		}
		if (!$this->processData($rIdx, $data, true/*reparse*/))
		{
			XLogError("FahClient::reparseData processData failed");
			return false;
		}
		//------------------
		return true;	
	}
	//------------------
	function processData($rIdx, $data, $reparse = false)
	{
		global $db; // just using the sanitize function
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$Rounds = new Rounds() or die("Create object failed");
		$Stats = new Stats() or die("Create object failed");
		$Workers = new Workers() or die ("Create object failed");
		$Wallet = new Wallet() or die ("Create object failed");
		$Config = new Config() or die("Create object failed");
		//------------------
		$validationMode = $Config->Get(CFG_FAH_VALIDATION_MODE);
		if ($validationMode === false)
		{
			$validationMode = DEF_CFG_FAH_VALIDATION_MODE;
			if (!$Config->Set(CFG_FAH_VALIDATION_MODE, $validationMode))
			{
				XLogError("FahClient::processData Config Set default validation mode failed");
				return false;
			}
		}
		//------------------
		if (!isset($data["donors"]))
		{
			XLogWarn("FahClient::processData donors not found, invalid reply");
			if (FAH_RPC_DEBUG_FAIL)
				XLogNotify("FahClient::processData data: ".XVarDump($data));
			return false;
		}
		//------------------
		$round = $Rounds->getRound($rIdx);
		if ($round === false)
		{
			XLogError("FahClient::processData - roundIdx not found: ".$rIdx);
			return false;
		}
		//---------------------------------
		if ($round->teamId === false || !is_numeric($round->teamId))
		{
			XLogError("FahClient::processData - validate round teamId failed: ".XVarDump($round->teamId));
			return false;
		}
		//---------------------------------
		$workerList = $Workers->loadWorkers();
		if ($workerList === false)
		{
			XLogError("FahClient::processData Workers loadWorkers failed");
			return false;
		}
		//------------------
		$newWorkers = array();
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::processData : prep/init $secDiff");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$workersByName = array();
		foreach ($data["donors"] as $donor) // first pass, validate and find new workers in donor list
		{
			//------------------
			if (!isset($donor["name"]) || !isset($donor["team"]) || !isset($donor["credit"]) || !isset($donor["wus"]) || !isset($donor["id"]) || 
				!is_numeric($donor["team"]) || !is_numeric($donor["credit"]) || !is_numeric($donor["wus"]) || !is_numeric($donor["id"]) || 
				(isset($donor["rank"]) && !is_numeric($donor["rank"])) )
			{
				XLogWarn("FahClient::processData required donor field not found, not valid, or invalid donor entry");
				if (FAH_RPC_DEBUG_FAIL)
					XLogNotify("FahClient::processData donor: ".XVarDump($donor));
				return false;
			}
			//------------------
			$wname 	= $donor["name"];
			//------------------
			if (MYSQL_REAL_ESCAPE)
			{
				//------------------
				try
				{
					$wname = $db->sanitize($wname);
					$donor["name"] = $wname;
				}
				catch (exception $e)
				{
					XLogError("FahClient::processData (new workers check) sanitize exception: ".XVarDump($e));
					return false;
				}
				//------------------
			} // if (MYSQL_REAL_ESCAPE)
			//------------------
			$worker = false;
			foreach ($workerList as $w)
				if ($w->uname == $wname)
				{
					$worker = $w;
					break;
				}
			//------------------
			if ($worker === false)
				$newWorkers[] = $wname;
			else
				$workersByName[$wname] = $worker; // save looked up object
			//------------------
		} // foreach ($data["donors"] as $donor)
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::processData : first pass $secDiff, doner count ".sizeof($data["donors"]));
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		XLogWarn("FahClient::processData found ".sizeof($newWorkers)." new workers.");
		if (sizeof($newWorkers) > 0) // add new workers
		{
			//------------------
			foreach ($newWorkers as $wname)
			{
				if (strlen($wname) > C_MAX_WALLET_ADDRESS_LENGTH)
					$waddress = false;
				else
					$waddrress = $wname;
				if (!$Workers->addWorker($wname, false/*address*/, "<FahClient Automation>"))
				{
					XLogError("FahClient::processData Workers addWorkerfailed for: $wname");
					return false;					
				}
			}
			//------------------
			$workerList = $Workers->loadWorkers();
			if ($workerList === false)
			{
				XLogError("FahClient::processData Workers loadWorkers after new workers failed");
				return false;
			}
			//------------------
			
		} // if (sizeof($newWorkers) > 0)
		//------------------
		$rmode = $round->statsMode; // $ridx from earlier and $rmode will be the same for each
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::processData add new workers time: $secDiff");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$updatedWorkers = array();
		foreach ($data["donors"] as $donor) // second pass, add stat for each donor
		{
			//------------------
			$wname 		= $donor["name"];
			$wteam 		= $donor["team"];
			$wpoints 	= $donor["credit"];
			$wwu 		= $donor["wus"];
			$wid		= $donor["id"];
			//------------------
			$wrank		= (isset($donor["rank"]) ? $donor["rank"] : false);
			//------------------
			if (isset($workersByName[$wname]))
				$worker = $workersByName[$wname];
			else
			{
				//------------------
				XLogDebug("FahClient::processData second pass looking up worker $wname");
				$worker = false;
				foreach ($workerList as $w)
					if ($w->uname == $wname)
					{
						$worker = $w;
						break;
					}
				//------------------
			}
			//------------------
			if ($worker === false)
			{
				XLogError("FahClient::processData failed to find worker for: $wname after already added new workers");
				return false;					
			}
			//------------------
			$skip = false;
			//------------------
			if ($worker->disabled)
				$skip = 10; 
			//------------------
			if ($worker->address == "" || $worker->validAddressKnown === false)
			{
				//------------------
				if ($worker->validAddressKnown === false)
				{
					if (XMaskContains($validationMode, FAH_VALMODE_FAILON_VALIDNOTSET))
					{
						XLogError("Fail on #address valid unknown# ($worker->id) wuname '$worker->uname', wAddress '$worker->address'");
						return false;
					}
					if (XMaskContains($validationMode, FAH_VALMODE_WARNON_VALIDNOTSET))
						XLogWarn("#address valid unknown# ($worker->id) wuname '$worker->uname', wAddress '$worker->address'");
				}
				//------------------
				$wasAddress = $worker->address;
				$wasValid = $worker->validAddress;
				//------------------
				$address = ($worker->address != "" && $worker->address != "x" ? $worker->address : $worker->uname);
				$isValid = $Wallet->isValidAddress($address);
				if ($isValid === false)
				{
					XLogError("FahClient::processData wallet isValidAddress failed");
					return false;
				}
				//------------------
				$worker->validAddress = ($isValid == $address ? true : false);
				if (XMaskContains($validationMode, FAH_VALMODE_WARNON_VALIDATION))
					XLogWarn("#Validate# ($worker->id) valid ".BoolYN($worker->validAddress)." isValid '$isValid' address '$address' wuname '$worker->uname', wAddress '$worker->address', was valid ".BoolYN($wasValid).($worker->validAddressKnown ? "" : " (not set)").", was address '$wasAddress'");
				//------------------
				if ($worker->validAddress)
					$worker->address = $address;
				else
					$worker->address = "x";
				//------------------
				if (!$worker->UpdateValidAddress())
				{
					XLogError("FahClient::processData worker failed to UpdateValidAddress");
					return false;
				}
				//------------------
			} // if ($worker->address == "")
			else
			{
				if (XMaskContains($validationMode, FAH_VALMODE_WARNON_NOVALIDATION))
				{
					$msg = " - ($worker->id) valid ".BoolYN($worker->validAddress).($worker->validAddressKnown ? "" : " (not set)")." - $worker->uname";
					if ($worker->uname != $worker->address)
						$msg .= "($worker->address)";
					 XLogWarn($msg);
				}
				if ($worker->validAddress && $worker->uname != $worker->address)
				{
					if (XMaskContains($validationMode, FAH_VALMODE_FAILON_ADDRMISMATCH))
					{
						XLogError("Fail on #address missmatch# ($worker->id) (addr valid) wuname '$worker->uname', wAddress '$worker->address'");
						return false;
					}
					if (XMaskContains($validationMode, FAH_VALMODE_WARNON_ADDRMISMATCH))
						XLogWarn("#address missmatch# ($worker->id) (addr valid) wuname '$worker->uname', wAddress '$worker->address'");
				}
			}
			//------------------
			if (!XMaskContains($round->payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS) && (!$worker->validAddress || $worker->address == ""))
				$skip = 1;
			else if (XMaskContains($round->payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS) && $worker->validAddress && $worker->address != "")
				$skip = 2;
			else if (XMaskContains($round->payMode, ROUND_PAY_MODE_FLAG_NAMED_WORKERS_ONLY) && $worker->address == $worker->uname)
				$skip = 3;
			else if ($reparse && $Stats->findStat($rIdx, $worker->id) === false)
				$skip = 4; 
			//------------------
			if ($skip === false)
			{
				//------------------
				//XLogDebug("FahClient::processData adding: ridx: $rIdx, rmode: $rmode, workeridx: $worker->id, team: $wteam, wid: $wid, points: $wpoints, wu: $wwu, rank: ".XVardump($wrank));
				if (!$Stats->addFahClientStat($rIdx, $rmode, $worker->id, $wteam, $wid, $wpoints, $wwu, $wrank, false /*reload*/))
				{
					XLogError("FahClient::processData - addFahClientStat failed");
					return false;
				}
				//------------------
			}
			//---------------------------------
			$updatedWorkers[] = $worker;
			//---------------------------------
		} // foreach ($data["donors"] as $donor) 
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::processData second pass, add stats time: $secDiff");
		//------------------
		return true;
	}
	//------------------
	function checkStats($rIdx, $reparse = false)
	{
		//------------------
		$Rounds = new Rounds() or die("Create object failed");
		$Stats = new Stats() or die("Create object failed");
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$round = $Rounds->getRound($rIdx);
		if ($round === false)
		{
			XLogError("FahClient::checkStats - roundIdx not found: ".$rIdx);
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::checkStats : looked up rounds $secDiff");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$newStats  = $Stats->findRoundStats($rIdx, false/*orderBy fefault*/, ($reparse ? false : true)/*includeWithPayout*/);
		if ($newStats === false)
		{
			XLogError("FahClient::checkStats - findRoundStats failed for new rIdx: ".$rIdx);
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::checkStats : looked up ".sizeof($newStats)." new status for new round $rIdx in $secDiff secs");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$rIdxLast = $Rounds->getRoundBefore($rIdx); // returns false on fail, -1 for none, or round index
		if ($rIdxLast === false)
		{
			XLogError("FahClient::checkStats - findRoundStats failed for last rIdx: ".$rIdxLast);
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::checkStats : looked up round index $rIdxLast before new round $rIdx in $secDiff secs");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		if ($rIdxLast === -1) // if there was no previous round
		{
			$roundLast = false;
			$lastStats = false;
		}
		else // if ($rIdxLast === -1)
		{
			//------------------
			$roundLast = $Rounds->getRound($rIdxLast);
			if ($roundLast === false)
			{
				XLogError("FahClient::checkStats - roundIdx not found: ".$rIdxLast);
				return false;
			}
			//------------------
			$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
			XLogDebug("FahClient::checkStats : looked up last round $rIdxLast in $secDiff secs");
			$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
			//------------------
			$lastStats = $Stats->findRoundStats($rIdxLast);
			if ($lastStats === false)
			{
				XLogError("FahClient::checkStats - findRoundStats failed for last rIdx: ".$rIdxLast);
				return false;
			}
			//------------------
			$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
			XLogDebug("FahClient::checkStats : looked up ".sizeof($lastStats)." old statuses for last round $rIdxLast in $secDiff secs");
			$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
			//------------------
		} // else // if ($rIdxLast === -1)
		//------------------
		$secsDeepSearching = 0;
		$deepCount = 0;
		//------------------
		XLogDebug("FahClient::checkStats - comparing rounds $rIdx and $rIdxLast");
		//------------------
		$debugVerbose  = "FahClient::checkStats:\n";
		$notFoundLastStat = array();
		foreach ($newStats as $nStat)
		{
			//------------------
			$lastStat = false;
			if ($lastStats !== false)
				foreach ($lastStats as $lStat)
					if ($nStat->workerIdx == $lStat->workerIdx)
					{
						$lastStat = $lStat;
						break;
					}
			//------------------
			if ($lastStat === false)
			{
				//------------------
				$startDeepUtc = new DateTime('now',  new DateTimeZone('UTC'));
				XLogWarn("FahClient::checkStats - (first pass) last stat not found for worker idx: ".$nStat->workerIdx.", deep searching.");
				//------------------
				$lastTotalPoints = $this->getLastTotalPoints($rIdx, $nStat->workerIdx);
				if ($lastTotalPoints === false)
				{
					//------------------
					XLogError("FahClient::checkStats - validate total points for stat and last stat failed. Last stat (".$lastStat->id.", ".XVarDump($lastStat->totPoints)."), new stat (".$nStat->id.", ".XVarDump($nStat->totPoints).")");
					return false;
					//------------------
				}
				//------------------
				$deepCount++;
				$secsDeepSearching += XDateTimeDiff($startDeepUtc); // default against now in UTC returning seconds
				//------------------
			}
			else // if ($lastStat === false)
			{
				//------------------
				if (!is_numeric($nStat->totPoints) || !is_numeric($lastStat->totPoints))
				{
					//------------------
					XLogError("FahClient::checkStats - validate total points for stat and last stat failed. Last stat (".$lastStat->id.", ".XVarDump($lastStat->totPoints)."), new stat (".$nStat->id.", ".XVarDump($nStat->totPoints).")");
					return false;
					//------------------
				}
				//------------------
				$lastTotalPoints = (int)$lastStat->totPoints;
				//------------------
			} // else // if ($lastStat === false)
			//------------------
			if ($lastStat === false)
				$strLast = "(deep $lastTotalPoints)";
			else
				$strLast = "($lastStat->id) $lastStat->totPoints";
			//------------------
			$strCur = "($nStat->id) $nStat->totPoints";
			//------------------
			$debugVerbose .= "cur: $strCur, last: $strLast\n";
			//------------------
			$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
			$nStat->dtPolled = $nowUtc->format(MYSQL_DATETIME_FORMAT); 
			$nStat->weekPoints = $nStat->totPoints - $lastTotalPoints;
			if ($nStat->weekPoints < 0)
			{
				XLogDebug($debugVerbose);
				XLogError("FahClient::checkStats points anomaly for stat id $nStat->id, $nStat->weekPoints = $nStat->totPoints - $lastTotalPoints");
				return false;
			}
			else if (!$nStat->update())
			{
				XLogDebug($debugVerbose);
				XLogError("FahClient::checkStats - update stat failed, stat idx: ".$nStat->id);
				return false;
			}
			//------------------
		} // foreach newStats
		//------------------
		XLogDebug($debugVerbose);
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		XLogDebug("FahClient::checkStats - checked ".sizeof($newStats)." workers, with $deepCount deep searches, in $secDiff secs, with $secsDeepSearching secs deep searching.");
		//------------------
		return true;
	}
	//------------------
	function getLastTotalPoints($ridx, $widx)
	{
		global $db;
		//------------------
		$sql = "SELECT ".DB_STATS_TOTAL_POINTS." FROM ".DB_STATS." WHERE ".DB_STATS_WORKER."=$widx AND ".DB_STATS_ROUND."=(SELECT MAX(".DB_STATS_ROUND.") FROM ".DB_STATS." WHERE ".DB_STATS_WORKER."=$widx AND ".DB_STATS_ROUND."<$ridx)";
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("FahClient::getLastTotalPoints - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogNotify("FahClient::getLastTotalPoints - not found, using zero.");
			return 0;
		}
		//------------------
		if (!isset($s[DB_STATS_TOTAL_POINTS]) || !is_numeric($s[DB_STATS_TOTAL_POINTS]))
		{
			XLogNotify("FahClient::getLastTotalPoints - validate total points is set failed. Result: ".XVarDump($s));
			return false;
		}
		//------------------
		return (int)$s[DB_STATS_TOTAL_POINTS];
	}
	//------------------
	function pollTeamOverview($reply = false)
	{
		global $db, $dbTeamFields;
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$teamId = $Config ->Get(CFG_ROUND_TEAM_ID);
		if ($teamId === false || !is_numeric($teamId))
		{
			XLogError("FahClient::pollTeamOverview get team ID config failed");
			return false;
		}
		//------------------
		$Team = new Team() or die("Create object failed");
		//------------------
		XLogDebug("FahClient::pollTeamOverview beginning poll for team $teamId");
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		if ($reply === false)
			$reply = $this->request($teamId);
		//------------------
		if ($reply === false)
		{
			XLogError("FahClient::pollTeamOverview request failed");
			return false;
		}
		//------------------
		$overview = $this->processTeamOverviewData($reply);
		//------------------
		if ($overview === false)
		{
			XLogError("FahClient::pollTeamOverview processTeamOverviewData failed");
			return false;
		}
		//------------------
		if (!isset($overview["team"]) || $overview["team"] != $teamId)
		{
			XLogWarn("FahClient::pollTeamOverview verify team id of reply failed. Requested ".XVarDump($teamId).", replied ".XVarDump($overview["team"]));
			XLogDebug(XVardump($reply));
			if (FAH_RPC_DEBUG_FAIL)
				XLogNotify("FahClient::pollTeamOverview reply: ".XVarDump($reply));
			return false;
		}
		//------------------
		if (!$Team->AddFromOverview($overview))
		{
			XLogError("FahClient::processTeamOverviewData Team AddFromOverview failed");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
	function reparseTeamOverview($fileName)
	{
		//------------------
		$data = @file_get_contents($fileName);
		if ($data === false)
		{
			XLogError("FahClient::reparseTeamOverview file_get_contents failed: $fileName");
			return false;
		}
		//------------------
		$jsonData = json_decode($data, true);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			XLogError("FahClient::reparseTeamOverview json_decode data failed");
			return false;
		}
		//------------------
		if (!$this->pollTeamOverview(array($data, $jsonData) ))
		{
			XLogError("FahClient::reparseTeamOverview pollTeamOverview failed");
			return false;
		}
		//------------------
		return true;	
	}
	//------------------
	function processTeamOverviewData($data)
	{
		//------------------
		$Rounds = new Rounds() or die("Create object failed");
		$Stats = new Stats() or die("Create object failed");
		$Team = new Team() or die("Create object failed");
		//------------------
		if ($data === false)
		{
			XLogError("FahClient::processTeamOverviewData data not set");
			return false;
		}
		//------------------
		//XLogDebug(XVardump($reply));
		list($rawData, $jsonData) = $data;
		//------------------
		$fieldNames = array();
		$fieldNames[] = "name";
		$fieldNames[] = "last";
		$fieldNames[] = "team";
		$fieldNames[] = "rank";
		$fieldNames[] = "total_teams";
		$fieldNames[] = "credit";
		$fieldNames[] = "wus";
		$fieldNames[] = "active_50";
		//------------------
		$overview = array();
		foreach ($fieldNames as $fn)
			if (!isset($jsonData[$fn]) || ($fn != "name" && $fn != "last" && !is_numeric($jsonData[$fn])))
			{
				XLogWarn("FahClient::processTeamOverviewData required field '$fn' not found or not valid");
				//XLogDebug(XVardump($jsonData);
				XLogDebug(XVardump($data));
				if (FAH_RPC_DEBUG_FAIL)
					XLogNotify("FahClient::processTeamOverviewData reply: ".XVarDump($data));
				return false;
			}
			else $overview[$fn] = $jsonData[$fn];
		//------------------
		if (!isset($jsonData["donors"]) || !is_array($jsonData["donors"]))
		{
			XLogWarn("FahClient::processTeamOverviewData required field 'donors' not found or not valid");
			return false;
		}
		//------------------
		$teamUserDate = new DateTime('now',  new DateTimeZone('UTC'));
		$activeDonors = 0;
		//"wus": 19088, "name": "(user-name)", "rank": 1301, "credit": 386275168, "team": 226715, "id": 1}
		foreach ($jsonData["donors"] as $donor)
		{
			if (isset($donor["wus"]) && isset($donor["credit"]) && isset($donor["id"]) && is_numeric($donor["wus"]) && is_numeric($donor["credit"]) && is_numeric($donor["id"]))
				if ($donor["wus"] > 0 && $donor["credit"] > 0)
				{
					if (!$Team->addUserDonor($donor, $teamUserDate))
					{
						XLogError("FahClient::processTeamOverviewData Team addUserDonor failed");
						return false;
					}
					//XLogDebug(" donor id ".$donor['id'].", wus ".$donor['wus'].", credit ".$donor['credit'].", lastPoints ".XVarDump($lastPoints)." activeDonors $activeDonors");
				}
		}
		$overview["active_donors"] = $activeDonors;
		//------------------
		//XLogDebug("Overview: ".XVarDump($overview));
		//------------------
		return $overview;
	}
	//------------------
}// class FahClient
?>
