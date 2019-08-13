<?php
//-------------------------------------------------------------
/*
*	AutomationPublicData.php
*
* This page shouldn't be accessible to the web.
* 
* 
*/
//-------------------------------------------------------------
define('PUBLIC_BALANCE_DATA_OUTPUT', '../PublicData.json');
define('PUBLIC_ROUND_DATA_OUTPUT', '../PublicRoundData.json');
define('PUBLIC_ROUND_CHART_DATA_OUTPUT', '../PublicRoundChartData.json');
define('DEFAULT_PUBLIC_ROUND_COUNT', 4); // last x rounds are displayed
define('DEFAULT_PUBLIC_ROUND_CHART_COUNT', 100); // last x rounds are displayed
define('DEFAULT_PUBLIC_ROUND_INCLUDE_IDLE', 0); // 0/1 include round workers with no tx (idle)
define('CFG_PUBLIC_ROUND_COUNT', 'auto_pubdata_round_count');
define('CFG_PUBLIC_ROUND_CHART_COUNT', 'auto_pubdata_chart_count');
define('CFG_PUBLIC_ROUND_CHART_FULL_COUNT', 'auto_pubdata_fchart_count');
define('CFG_PUBLIC_ROUND_INCLUDE_IDLE', 'auto_pubdata_round_inc_idle');
//---------------------------
define('LEGACY_UPDATE_TEAM_DATA_EO', false);
//---------------------------
define('TEST_TEAMSTAT', false);
define('TEST_TEAMSUMMARY', false);
//-------------------------------------------------------------
define('POLL_TEAM_STATS_PAGE_EO', 'http://folding.extremeoverclocking.com/xml/team_summary.php?t=');
define('TEST_TEAM_STATS_PAGE_EO', 'test_team_summary.xml');
define('TEST_TEAM_STATS_PAGE', '../test_team_summary.json');
//---------------------------
define('PUBLIC_TEAM_DATA_OUTPUT', '../PublicTeamData.json');
define('CFG_PUBLIC_LAST_POLL_TEAM', 'last-poll-team-stat');
//---------------------------
require('./include/Init.php');
//---------------------------
class AutomationPublicData
{
	//---------------------------
	function formatDate($strDate)
	{
		//---------------------------
		try
		{
			$dt = new DateTime($strDate, new DateTimeZone('UTC'));
		}
		catch (Exception $e)
		{
			XLogWarn("AutomationPublicData::formatDate parse Date failed input string: ".XVarDump($strDate).", Message: ".$e->getMessage());
			return "";
		}
		//---------------------------
		return $dt->format(DATE_ISO8601);
	}
	//---------------------------
	function Main()
	{
		//---------------------------
		$Automation = new Automation() or die("Create object failed");
		//---------------------------
		$ret = $this->updateData();
		if ($ret === false)
			XLogError("AutomationPublicData::Main updateData failed");
		//---------------------------
		if ($ret === false && !$Automation->IncPublicDataNoAction())
		{
			XLogError("AutomationPublicData::Main Automation IncPublicDataNoAction failed");
			return false;
		}
		//---------------------------
		if ($ret === true && !$Automation->ClearPublicDataNoAction())
		{
			XLogError("AutomationPublicData::Main Automation ClearPublicDataNoAction failed");
			return false;
		}
		//---------------------------
		if ($ret === true && !$Automation->UpdateLastPublicData())
		{
			XLogError("AutomationPublicData::Main Automation UpdateLastPublicData failed");
			return false;
		}
		//---------------------------
		if (LEGACY_UPDATE_TEAM_DATA_EO === true)
		{
			if (!$this->updateTeamDataEO())
			{
				XLogError("AutomationPublicData::Main Automation updateTeamDataEO failed");
				return false;
			}
		}
		else
		{			 
			if (!$this->updateTeamData())
			{
				XLogError("AutomationPublicData::Main Automation updateTeamData failed");
				return false;
			}
		}
		//---------------------------
		return $ret;
	}
	//---------------------------
	function updateData()
	{
		//---------------------------
		$Config = new Config() or die("Create object failed");
		//---------------------------
		$roundMax = $Config->Get(CFG_PUBLIC_ROUND_COUNT);
		if ($roundMax === false || !is_numeric($roundMax))
		{
			$roundMax = DEFAULT_PUBLIC_ROUND_COUNT;
			if (!$Config->Set(CFG_PUBLIC_ROUND_COUNT, $roundMax))
			{
				XLogError("AutomationPublicData::updateData Config failed to Set round count to default");
				return false;
			}
		}
		//---------------------------
		$data = $this->getBalanceData();
		if ($data === false)
		{
			XLogError("AutomationPublicData::updateData getBalanceData failed");
			return false;
		}
		//---------------------------
		if (!$this->saveBalanceData($data))
		{
			XLogError("AutomationPublicData::updateData saveBalanceData failed");
			return false;
		}
		//---------------------------
		$data = $this->getRoundData($roundMax, true/*includeDetails*/);
		if ($data === false)
		{
			XLogError("AutomationPublicData::updateData getRoundData failed");
			return false;
		}
		//---------------------------
		if (!$this->saveRoundData($data))
		{
			XLogError("AutomationPublicData::updateData saveRoundData failed");
			return false;
		}
		//---------------------------
		$roundMax = $Config->Get(CFG_PUBLIC_ROUND_CHART_COUNT);
		if ($roundMax === false || !is_numeric($roundMax))
		{
			$roundMax = DEFAULT_PUBLIC_ROUND_CHART_COUNT;
			if (!$Config->Set(CFG_PUBLIC_ROUND_CHART_COUNT, $roundMax))
			{
				XLogError("AutomationPublicData::updateData Config failed to Set round chat count to default");
				return false;
			}
		}
		//---------------------------
		$data = $this->getRoundData($roundMax, false/*includeDetails*/);
		if ($data === false)
		{
			XLogError("AutomationPublicData::updateData getRoundData failed");
			return false;
		}
		//---------------------------
		if (!$this->saveRoundChartData($data))
		{
			XLogError("AutomationPublicData::updateData saveRoundChartData failed");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function getBalanceData()
	{
		//---------------------------
		$Contributions = new Contributions() or die("Create object failed");
		$Ads = new Ads() or die("Create object failed");
		//---------------------------
		$Wallet = new Wallet() or die("Create object failed");
		if (!$Wallet->Init())
		{
			XLogError("AutomationPublicData::getBalanceData Wallet init failed");
			return false;
		}
		//---------------------------
		$activeAddress = $Wallet->getAccountAddress(); // defaults to active account
		if ($activeAddress === false)
		{
			XLogError("AutomationPublicData::getBalanceData Wallet failed to getAccountAddress");
			return false;
		}
		//---------------------------
		if (is_array($activeAddress) && sizeof($activeAddress)) // this should be a one entry array
			$activeAddress = $activeAddress[0];
		//---------------------------
		$activeBalance = $Wallet->getBalance(); // defaults to active account
		if ($activeBalance === false)
		{
			XLogError("AutomationPublicData::getBalanceData Wallet failed to getBalance");
			return false;
		}
		//---------------------------
		$contList = $Contributions->findRoundContributions(-1 /*roundIdx*/);
		if ($contList === false)
		{
			XLogError("AutomationPublicData::getBalanceData Contributions failed to findRoundContributions (default)");
			return false;
		}
		//---------------------------
		$jsonCont = array();
		foreach ($contList as $cont)
		{
			$ad = false;
			if ($cont->ad !== false && is_numeric($cont->ad))
				$ad = $Ads->getAd($cont->ad);
			if ($ad === false)
			{
				$adMode  = AD_MODE_NONE;
				$adStyle = "";
				$adLink  = "";
				$adText  = "";
				$adImage = "";
			}
			else
			{
				$adMode  = $ad->mode;
				$adStyle = $ad->style;
				$adLink  = $ad->link;
				$adText  = $ad->text;
				$adImage = $ad->image;
			}
			if ($cont->mode == CONT_MODE_ALL && $cont->account !== false && $cont->account !== "")
			{
				$cont->value = $Wallet->getBalance($cont->account);	
				if ($cont->value === false)
				{
					XLogWarn("AutomationPublicData::getBalanceData Wallet getBalance of ALL Contribution from account '".$cont->account."' failed");
					$cont->value = 0;
				}
			}
			$jsonCont[] = array( 'number' => $cont->number, 'name' => $cont->name, 'mode' => $cont->mode, 'value' => $cont->value, 'flags' => $cont->flags, 'adMode' => $adMode, 'adStyle' => $adStyle, 'adLink' => $adLink, 'adText' => $adText, 'adImage' => $adImage);
		}
		//---------------------------
		$activeBalance = round($activeBalance);
		//---------------------------
		return array( 'UTC' => $this->formatDate("now"), 'activeAddress' => $activeAddress, 'activeBalance' => $activeBalance, 'fee' => $Wallet->estfee, 'contributions' => $jsonCont);
	}
	//---------------------------
	function saveBalanceData($data)
	{
		//---------------------------
		$strJson = json_encode($data);
		if ($strJson === false)
		{
			XLogError("AutomationPublicData::saveBalanceData json_encode failed");
			return false;
		}
		//---------------------------
		$retval = file_put_contents(PUBLIC_BALANCE_DATA_OUTPUT, $strJson);
		if ($retval === false)
		{
			XLogError("AutomationPublicData::saveBalanceData file_put_contents failed");
			XLogWarn("AutomationPublicData::saveBalanceData failed to write JSON data file: ".PUBLIC_BALANCE_DATA_OUTPUT." (".realpath(PUBLIC_BALANCE_DATA_OUTPUT)."), is writeable: ".(is_writable(PUBLIC_BALANCE_DATA_OUTPUT) ? "Y" : "N").", file exists: ".(file_exists(PUBLIC_BALANCE_DATA_OUTPUT) ? "Y" : "N")." free disk space: ".disk_free_space(dirname(PUBLIC_BALANCE_DATA_OUTPUT))." bytes.");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function getRoundData($roundMax, $includeDetails)
	{
		//---------------------------
		$Rounds = new Rounds() or die("Create object failed");
		$Payouts = new Payouts() or die("Create object failed");
		$Stats = new Stats() or die("Create object failed");
		$Workers = new Workers() or die("Create object failed");
		$Config = new Config() or die("Create object failed");
		//---------------------------
		$digits = $Config->Get(CFG_ROUND_PAY_DIGITS);
		if ($digits === false)
		{
			XLogError("AutomationPublicData::getRoundData Config Get pay digits failed");
			return false;
		}
		//---------------------------
		$includeIdle = $Config->Get(CFG_PUBLIC_ROUND_INCLUDE_IDLE);
		if ($includeIdle === false || !is_numeric($includeIdle))
		{
			$includeIdle = DEFAULT_PUBLIC_ROUND_INCLUDE_IDLE;
			if (!$Config->Set(CFG_PUBLIC_ROUND_INCLUDE_IDLE, $includeIdle))
			{
				XLogError("AutomationPublicData::getRoundData Config failed to Set round show idle to default");
				return false;
			}
		}
		//---------------------------
		$roundList = $Rounds->getRounds(true/*onlyDone*/, ($roundMax < 5 ? 10 : ($roundMax * 2))/*maxCount*/, DB_ROUND_ID." DESC" /*sort*/);
		if ($roundList === false)
		{
			XLogError("AutomationPublicData::getRoundData Rounds failed to getRounds");
			return false;
		}
		//---------------------------
		$workerList = array();
		$txidList = array();
		//---------------------------
		$roundsData = array();
		$roundCount = 0;
		//---------------------------
		foreach ($roundList as $r)
			if (!XMaskContains($r->payMode, ROUND_PAY_MODE_HIDDEN_TEST) && sizeof($roundsData) < $roundMax)
			{
				//---------------------------
				$payoutList = $Payouts->findRoundPayouts($r->id);
				if ($payoutList === false)
				{
					XLogError("AutomationPublicData::getRoundData Rounds failed to getRounds ridx $r->id");
					return false;
				}
				//---------------------------
				$statList = $Stats->findRoundStats($r->id);
				if ($statList === false)
				{
					XLogError("AutomationPublicData::getRoundData Stats failed to findRoundStats ridx $r->id");
					return false;
				}
				//---------------------------
				$avgPoints = 0;
				$avgPay = 0.0;
				$maxPoints = 0;
				$maxPay = 0.0;
				$minPoints = PHP_INT_MAX;
				$minPay = PHP_INT_MAX;
				$countPoints = 0;
				$countPay = 0;
				//---------------------------
				$curTxid = false;
				//---------------------------
				$wData = array();
				foreach ($statList as $stat)
				{
					//------------------------
					$payout = false;
					foreach ($payoutList as $p)
						if ($p->id == $stat->payoutIdx)
						{
							$payout = $p;
							break;
						}
					//------------------------
					if ($payout === false)
						$widx = $stat->workerIdx;
					else
						$widx = $payout->workerIdx;
					//------------------------
					$worker = $Workers->getWorker($widx);
					if ($worker === false)
						$strWorker = "[missing]";
					else if ($worker->address == "")
						$strWorker = $worker->uname;
					else				
						$strWorker = $worker->address;
					//------------------------
					$work = $stat->work();
					if ($work === false)
						$work = "[error]";
					if ($stat->dtPolled == "")
						$strPoints = "[missing]";
					else
					{
						$strPoints = $work;
						$avgPoints += $work;
						if ($work > $maxPoints)
							$maxPoints = (int)$work;
						if ($work > 0 && $work < $minPoints)
							$minPoints = (int)$work;
						$countPoints++;
					}
					//------------------------
					if ($payout === false)
					{
						$strPayout = "0";
						$strTx = "[none]";
						
					}
					else
					{
						$strPayout = "$payout->pay";
						$strTx = $payout->txid;
						$avgPay += $payout->pay;
						if ($payout->pay > $maxPay)
							$maxPay = (float)$payout->pay;
						if ($payout->pay > 0 && $payout->pay < $minPay)
							$minPay = (float)$payout->pay;
						$countPay++;
					}
					//------------------------
					if ($curTxid === false || $strTx != $txidList[$curTxid])
					{
						$curTxid = false;
						for ($idx = 0; $idx < sizeof($txidList);$idx++)
							if ($strTx == $txidList[$idx])
							{
								$curTxid = $idx;
								break;
							}
						if ($curTxid === false)
						{
							$curTxid = sizeof($txidList);
							$txidList[] = $strTx;
						}
					}
					//------------------------
					$workerIdx = false;
					for ($idx = 0; $idx < sizeof($workerList);$idx++)
						if ($strWorker == $workerList[$idx])
						{
							$workerIdx = $idx;
							break;
						}
					if ($workerIdx === false)
					{
						$workerIdx = sizeof($workerList);
						$workerList[] = $strWorker;
					}
					//------------------------
					//XLogDebug("Round ".$r->id." widx ".$widx." includeIdle ".XVarDump($includeIdle)." has payout ".($payout !== false ? "y" : "f")." tx ".$strTx);
					if ($payout !== false || ($roundCount < $includeIdle && is_numeric($work) && $work > 0)) // has a payout or within include idle range
						$wData[] = array('address' => $workerIdx, 'points' => $strPoints, 'pay' => $strPayout, 'txid' => $curTxid);
					//------------------------
				} // foreach ($statList as $stat)
				//------------------------
				if ($countPoints != 0)
					$avgPoints /= $countPoints;
				$avgPoints =  floor($avgPoints);
				if ($countPay != 0)
					$avgPay /= $countPay;
				$avgPay = round($avgPay, $digits);
				//------------------------
				if ($minPoints === PHP_INT_MAX)
					$minPoints = 0;
				if ($minPay === PHP_INT_MAX)
					$minPay = 0.0;
				//------------------------
				$comment = $r->getComment();
				if ($comment === false)
					$comment = '[error]';
				//------------------------
				switch ( ($r->payMode & ROUND_PAY_MODE_MASK) )
				{
					case ROUND_PAY_MODE_RATE: $strMode = "Flat Rate"; break;
					case ROUND_PAY_MODE_PERCENT: $strMode = "Percent"; break;
					case ROUND_PAY_MODE_NONE: // fall through to default
					default: $strMode = "Error"; break;
				}
				//------------------------
				if (XMaskContains($r->payMode, ROUND_PAY_MODE_FLAG_NAMED_WORKERS_ONLY)) $strMode .= " named-only";
				if (XMaskContains($r->payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS)) $strMode .= " include-unkown-address";
				if (XMaskContains($r->payMode, ROUND_PAY_MODE_FLAG_DRYRUN)) $strMode .= "dry-run/test";
				//------------------------
				if ($includeDetails)
				{
					$roundsData[] = array('id' => $r->id, 'mode' => $strMode, 'comment' => $comment, 
										  'utcStats' => $this->formatDate($r->dtStatsDone), 'utcPaid' => $this->formatDate($r->dtPaid), 
										  'totalPoints' => $r->totalWork, 'avgPoints' => $avgPoints, 'maxPoints' => $maxPoints, 'minPoints' => $minPoints,
										  'totalPay' => $r->totalPay, 'countPay' => $countPay, 'avgPay' => $avgPay, 'maxPay' => $maxPay, 'minPay' => $minPay,
										  'workers' => $wData);
				}
				else // if ($includeDetails)
				{
					$roundsData[] = array('id' => $r->id, 'utcPaid' => $this->formatDate($r->dtPaid), 
										  'totalPoints' => $r->totalWork, 'avgPoints' => $avgPoints, 'maxPoints' => $maxPoints, 'minPoints' => $minPoints,
										  'totalPay' => $r->totalPay, 'countPay' => $countPay, 'avgPay' => $avgPay, 'maxPay' => $maxPay, 'minPay' => $minPay);
				}
				//---------------------------
				$roundCount++;
				//---------------------------
			} // foreach ($roundList as $r) if mode....
		//---------------------------
		if ($includeDetails)
			return array( 'UTC' => $this->formatDate("now"), 'txids' => $txidList, 'workers' => $workerList, 'rounds' => $roundsData);
		else
			return array( 'UTC' => $this->formatDate("now"), 'rounds' => $roundsData);
	}
	//---------------------------
	function saveRoundData($data)
	{
		//---------------------------
		$strJson = json_encode($data);
		if ($strJson === false)
		{
			XLogError("AutomationPublicData::saveRoundData json_encode failed");
			return false;
		}
		//---------------------------
		$retval = file_put_contents(PUBLIC_ROUND_DATA_OUTPUT, $strJson);
		if ($retval === false)
		{
			XLogError("AutomationPublicData::saveRoundData file_put_contents failed");
			XLogWarn("AutomationPublicData::saveRoundData failed to write JSON data file: ".PUBLIC_ROUND_DATA_OUTPUT." (".realpath(PUBLIC_ROUND_DATA_OUTPUT)."), is writeable: ".(is_writable(PUBLIC_ROUND_DATA_OUTPUT) ? "Y" : "N").", file exists: ".(file_exists(PUBLIC_ROUND_DATA_OUTPUT) ? "Y" : "N")." free disk space: ".disk_free_space(dirname(PUBLIC_ROUND_DATA_OUTPUT))." bytes.");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function saveRoundChartData($data)
	{
		//---------------------------
		$strJson = json_encode($data);
		if ($strJson === false)
		{
			XLogError("AutomationPublicData::saveRoundChartData json_encode failed");
			return false;
		}
		//---------------------------
		$retval = file_put_contents(PUBLIC_ROUND_CHART_DATA_OUTPUT, $strJson);
		if ($retval === false)
		{
			XLogError("AutomationPublicData::saveRoundChartData file_put_contents failed");
			XLogWarn("AutomationPublicData::saveRoundChartData failed to write JSON data file: ".PUBLIC_ROUND_CHART_DATA_OUTPUT." (".realpath(PUBLIC_ROUND_CHART_DATA_OUTPUT)."), is writeable: ".(is_writable(PUBLIC_ROUND_CHART_DATA_OUTPUT) ? "Y" : "N").", file exists: ".(file_exists(PUBLIC_ROUND_CHART_DATA_OUTPUT) ? "Y" : "N")." free disk space: ".disk_free_space(dirname(PUBLIC_ROUND_CHART_DATA_OUTPUT))." bytes.");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function updateTeamDataEO()
	{
		$Config = new Config() or die("Create object failed");
		//------------------
		$teamNum = $Config ->Get(CFG_ROUND_TEAM_ID);
		if ($teamNum === false)
		{
			XLogError("AutomationPublicData::updateTeamDataEO get team ID config failed");
			return false;
		}
		//------------------
		if (!is_numeric($teamNum))
		{
			XLogError("AutomationPublicData::updateTeamDataEO validate team ID is numeric failed: ".XVarDump($teamNum));
			return false;
		}
		//------------------
		if (TEST_TEAMSTAT === true)
			$url = TEST_TEAM_STATS_PAGE_EO;
		else
		{
			$url = POLL_TEAM_STATS_PAGE_EO.$teamNum;
		}
		//---------------------------------
		$stats = @simplexml_load_file($url);
		if ($stats === false)
		{
			$error = libxml_get_last_error();
			if ($error !== false && strpos($error->message, "failed to load external entity") === 0)
			{
				//---------------------------------
				XLogWarn("AutomationPublicData::updateTeamDataEO - team stats not found team: $teamNum");
				//---------------------------------
				return "Not Found";
			}
			XLogError("AutomationPublicData::updateTeamDataEO - simplexml_load_file failed. Error: ".($error === false ? "<no error info>" : "[$error->code] $error->message").", Url: $url");
			return false;
		}
		//---------------------------------
		if (!$this->updateTeamRateLimit())
		{
			XLogError("AutomationPublicData::updateTeamDataEO updateTeamRateLimit failed");
			return false;
		}
		//------------------
		if (!isset($stats->team))
		{
			XLogError("AutomationPublicData::updateTeamDataEO - verify data structure failed");
			return false;
		}
		//------------------
		$stats = $stats->team;
		//------------------
		if (!isset($stats->TeamID) || $stats->TeamID != $teamNum)
		{
			XLogError("AutomationPublicData::updateTeamDataEO - verify team number failed. Expected: $teamNum, got: ".XVarDump($stats->TeamID));
			return false;
		}
		//---------------------------------
		if (!isset($stats->Team_Name) || !isset($stats->Rank) || !isset($stats->Users_Active) || !isset($stats->Users) || !isset($stats->Change_Rank_24hr) || !isset($stats->Change_Rank_7days) || !isset($stats->Points_24hr_Avg) || !isset($stats->Points) || !isset($stats->WUs))
		{
			XLogError("AutomationPublicData::updateTeamDataEO - not all expected fields were found");
			return false;
		}
		//---------------------------------
		$data = array( 'UTC' => $this->formatDate("now"), 'name' => (string)$stats->Team_Name, 'id' => (int)$stats->TeamID, 'actusers' => (int)$stats->Users_Active, 'users' => (int)$stats->Users, 'rank' => (int)$stats->Rank, 'rank24' => (int)$stats->Change_Rank_24hr, 'rank7' => (int)$stats->Change_Rank_7days, 'points' => (int)$stats->Points, 'points24' => (int)$stats->Points_24hr_Avg, 'wu' => (int)$stats->WUs);
		//---------------------------------
		$strJson = json_encode($data);
		if ($strJson === false)
		{
			XLogError("AutomationPublicData::updateTeamDataEO json_encode failed");
			return false;
		}
		//---------------------------
		$retval = file_put_contents(PUBLIC_TEAM_DATA_OUTPUT, $strJson);
		if ($retval === false)
		{
			XLogError("AutomationPublicData::updateTeamDataEO file_put_contents failed");
			XLogWarn("AutomationPublicData::updateTeamDataEO failed to write JSON data file: ".PUBLIC_TEAM_DATA_OUTPUT." (".realpath(PUBLIC_TEAM_DATA_OUTPUT)."), is writeable: ".(is_writable(PUBLIC_TEAM_DATA_OUTPUT) ? "Y" : "N").", file exists: ".(file_exists(PUBLIC_TEAM_DATA_OUTPUT) ? "Y" : "N")." free disk space: ".disk_free_space(dirname(PUBLIC_TEAM_DATA_OUTPUT))." bytes.");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function updateTeamData()
	{
		$Team = new Team() or die("Create object failed");
		//------------------
		if ($this->checkTeamRateLimit())
			if (!$this->pollTeamOverview())
			{
				XLogError("AutomationPublicData::updateTeamData pollTeamOverview failed");
				return false;
			}
		//------------------
		$stats = $Team->calcTeamStats();
		//------------------
		if ($stats === false)
		{
			XLogError("AutomationPublicData::updateTeamData - Team calcTeamStats failed");
			return false;
		}
		//---------------------------------
		$strJson = json_encode($stats);
		if ($strJson === false)
		{
			XLogError("AutomationPublicData::updateTeamData json_encode failed");
			return false;
		}
		//---------------------------
		$retval = file_put_contents(PUBLIC_TEAM_DATA_OUTPUT, $strJson);
		if ($retval === false)
		{
			XLogError("AutomationPublicData::updateTeamData file_put_contents failed");
			XLogWarn("AutomationPublicData::updateTeamData failed to write JSON data file: ".PUBLIC_TEAM_DATA_OUTPUT." (".realpath(PUBLIC_TEAM_DATA_OUTPUT)."), is writeable: ".(is_writable(PUBLIC_TEAM_DATA_OUTPUT) ? "Y" : "N").", file exists: ".(file_exists(PUBLIC_TEAM_DATA_OUTPUT) ? "Y" : "N")." free disk space: ".disk_free_space(dirname(PUBLIC_TEAM_DATA_OUTPUT))." bytes.");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function pollTeamOverview()
	{
		$FahClient = new FahClient() or die("Create object failed");
		//------------------
		XLogDebug("AutomationPublicData::pollTeamOverview");
		//------------------
		if (TEST_TEAMSUMMARY === true)
		{
			if (!$FahClient->reparseTeamOverview(TEST_TEAM_STATS_PAGE))
			{
				XLogError("AutomationPublicData::pollTeamOverview FahClient pollTeamOverview failed");
				return false;
			}
		}
		else
		{
			if (!$FahClient->pollTeamOverview())
			{
				XLogError("AutomationPublicData::pollTeamOverview FahClient pollTeamOverview failed");
				return false;
			}
		}
		//------------------
		if (!$this->updateTeamRateLimit())
		{
			XLogError("AutomationPublicData::pollTeamOverview updateTeamRateLimit failed");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function checkTeamRateLimit()
	{
		$Config = new Config() or die("Create object failed");
		//------------------
		$lastPoll = $Config->Get(CFG_PUBLIC_LAST_POLL_TEAM);
		if ($lastPoll === false || $lastPoll === "")
		{
			 XLogWarn("AutomationPublicData::checkTeamRateLimit No Rate limit info");
			 return true;
		}
		//------------------
		$d = XDateTimeDiff($lastPoll, false/*now*/, false/*UTC*/, 'n'/*minutes*/);
		//------------------
		if ($d === false)
		{
			 XLogError("AutomationPublicData::checkTeamRateLimit XDateTimeDiff failed. lastPoll: '$lastPoll'");
			 return false;
		}
		//------------------
		if ($d < 360) // maximum rate, once every 360 mins = 6 hours
		{
			//XLogDebug("AutomationPublicData::checkTeamRateLimit rate limitted. minutes since last team stats: $d");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function updateTeamRateLimit()
	{
		$Config = new Config() or die("Create object failed");
		//------------------
		//XLogNotify("AutomationTeamStats::updateTeamRateLimit");
		$dtNow = new DateTime('now', new DateTimeZone('UTC'));
		if (!$Config->Set(CFG_PUBLIC_LAST_POLL_TEAM, $dtNow->format(MYSQL_DATETIME_FORMAT)))
		{
			 XLogError("AutomationPublicData::updateTeamRateLimit Config Set Last Team Stat failed");
			 return true;
		}
		//------------------
		return true;
	}
	//---------------------------
} // class AutomationPublicData
//---------------------------
$apd = new AutomationPublicData() or die("Create object failed");
if (!$apd->Main())
{
	XLogNotify("AutomationPublicData Main failed");
	echo "failed";
}
else
{
	//XLogNotify("AutomationPublicData done successfully");
	echo "ok";
}
//---------------------------
?>
