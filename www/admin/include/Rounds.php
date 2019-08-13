<?php
/*
 *	www/include/Rounds.php
 * 
 * 
* 
*/
//---------------
define('ROUND_DEBUG_STATES', true); // verbose state output
//---------------
define('ROUND_STATS_MODE_NONE',						0); // bitmasks
define('ROUND_STATS_MODE_EO_WEEK_SCORE',			0x1);
define('ROUND_STATS_MODE_FAH_WORKER',				0x2);
define('ROUND_STATS_MODE_FAH_SCORE',				0x4);
define('ROUND_STATS_MODE_EO_TOTAL_SCORE',			0x8);
define('ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE',	0x10);
//---------------
define('ROUND_PAY_MODE_NONE', 						0);
define('ROUND_PAY_MODE_RATE', 						1);
define('ROUND_PAY_MODE_PERCENT', 					2);
define('ROUND_PAY_MODE_MASK', 						0xF); //(first 15 bits) this bitmasked gets just the mode, not flags
define('ROUND_PAY_MODE_FLAG_NAMED_WORKERS_ONLY', 	0x20); //(32) this is bitmasked
define('ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS', 	0x40); //(64) this is bitmasked
define('ROUND_PAY_MODE_FLAG_DRYRUN', 				0x80); //(128) this is bitmasked
define('ROUND_PAY_MODE_HIDDEN_TEST', 				0x100); //(256) this is bitmasked
//---------------
define('DEFAULT_ROUND_TEAM_ID', BRAND_FOLDING_TEAM_ID);
define('DEFAULT_ROUND_STATS_MODE', (ROUND_STATS_MODE_FAH_WORKER | ROUND_STATS_MODE_EO_WEEK_SCORE));
define('DEFAULT_ROUND_PAY_MODE', ROUND_PAY_MODE_PERCENT);
define('DEFAULT_ROUND_PAY_RATE', 0.001);
define('DEFAULT_ROUND_PAY_DIGITS', 4);
define('DEFAULT_ROUND_TRANS_WAIT', 8); // minutes
define('DEFAULT_ROUND_PAY_MINIMUM', 1); 
//---------------
define('CFG_ROUND_TEAM_ID', 'def_round_team_id');
define('CFG_ROUND_STATS_MODE', 'def_round_stats_mode');
define('CFG_ROUND_PAY_MODE', 'def_round_pay_mode');
define('CFG_ROUND_PAY_RATE', 'def_round_pay_rate');
define('CFG_ROUND_PAY_DIGITS', 'def_round_pay_digits');
//-----------
define('CFG_ROUND_PAY_MINIMUM', 'round_pay_minimum'); 
define('CFG_ROUND_TRANS_WAIT', 'round_trans_wait'); // minutes
define('CFG_ROUND_LAST_TRANSACTION', 'round_last_trans');
//-----------
define('CFG_ROUND_OLDEST_ACTIVE', 'round_oldest_active');
//---------------
define('ROUND_STATE_NONE', 			0);
define('ROUND_STATE_ERROR', 		1);
define('ROUND_STATE_FAHSTATSREQ', 	2);
define('ROUND_STATE_FAHSTATS', 		3);
define('ROUND_STATE_STATSREQ', 		4);
define('ROUND_STATE_STATS', 		5);
define('ROUND_STATE_CONT_REQ',		6);
define('ROUND_STATE_CONT',			7);
define('ROUND_STATE_PAYREQ', 		8);
define('ROUND_STATE_WAIT_FUND', 	9);
define('ROUND_STATE_WAIT_APPR', 	10);
define('ROUND_STATE_PAYING', 		11);
define('ROUND_STATE_DONE', 			12);
define('ROUND_STATE_FAHCLIENTREQ',	20);
define('ROUND_STATE_FAHCLIENT',	21);
//define('ROUND_STATE_',			22);
//---------------
class Round
{
	var $id = -1;
	var $comment = false;
	var $dtStarted = "";
	var $dtFahStatsRequested = "";
	var $dtFahStatsDone = "";
	var $dtStatsRequested = "";
	var $dtStatsDone = "";
	var $dtContRequested = "";
	var $dtContDone = "";
	var $dtPayRequested = "";
	var $dtPaid = "";
	var $statsMode = ROUND_STATS_MODE_NONE;
	var $teamId = false;
	var $payMode = ROUND_PAY_MODE_NONE;
	var $payRate = 0.0;
	var $totalWork = 0;
	var $totalPay = 0.0;
	var $approved = false;
	var $funded = false;
	var $state = ROUND_STATE_NONE;
	//------------------
	function __construct($row = false)
	{
		if ($row !== false)
			$this->set($row);
	}
	//------------------
	function forceState($state)
	{
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$dt = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		$this->state = $state;
		//------------------
		$bFahStatsRequested = false;
		$bFahStatsDone 		= false;
		$bStatsRequested 	= false;
		$bStatsDone 		= false;
		$bContRequested		= false;
		$bContDone			= false;
		$bPayRequested 		= false;
		$bPaid 				= false;
		$bFunded			= false;
		$bApproved			= false;
		//------------------
		if ($state == ROUND_STATE_ERROR)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
			$bContDone			= true;
			$bPayRequested 		= true;
			$bPaid 				= true;
			$bFunded			= true;
			$bApproved			= true;
		}
		else if ($state == ROUND_STATE_NONE)
		{
		}
		else if ($state == ROUND_STATE_FAHSTATSREQ)
		{
		}
		else if ($state == ROUND_STATE_FAHSTATS)
		{
			$bFahStatsRequested = true;
		}
		else if ($state == ROUND_STATE_STATSREQ)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
		}
		else if ($state == ROUND_STATE_STATS)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
		}
		else if ($state == ROUND_STATE_CONT_REQ)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
		}
		else if ($state == ROUND_STATE_CONT)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
		}
		else if ($state == ROUND_STATE_PAYREQ)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
			$bContDone			= true;
			$bApproved			= true;
		}
		else if ($state == ROUND_STATE_WAIT_FUND)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
			$bContDone			= true;
			$bPayRequested 		= true;
		}
		else if ($state == ROUND_STATE_WAIT_APPR)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
			$bContDone			= true;
			$bPayRequested 		= true;
			$bPayRequested 		= true;
			$bFunded			= true;
		}
		else if ($state == ROUND_STATE_PAYING)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
			$bContDone			= true;
			$bPayRequested 		= true;
			$bFunded			= true;
			$bApproved			= true;
		}
		else if ($state == ROUND_STATE_DONE)
		{
			$bFahStatsRequested = true;
			$bFahStatsDone 		= true;
			$bStatsRequested 	= true;
			$bStatsDone 		= true;
			$bContRequested		= true;
			$bContDone			= true;
			$bPayRequested 		= true;
			$bFunded			= true;
			$bApproved			= true;
			$bPaid 				= true;
		}
		//------------------
		if (!$bFahStatsRequested)
			$this->dtFahStatsRequested = "";
		else if ($this->dtFahStatsRequested == "")
			$this->dtFahStatsRequested = $dt;
		if (!$bFahStatsDone)
			$this->dtFahStatsDone = "";
		else if ($this->dtFahStatsDone == "")
			$this->dtFahStatsDone = $dt;
		if (!$bStatsRequested)
			$this->dtStatsRequested = "";
		else if ($this->dtStatsRequested == "")
			$this->dtStatsRequested = $dt;
		if (!$bStatsDone)
			$this->dtStatsDone = "";
		else if ($this->dtStatsDone == "")
			$this->dtStatsDone = $dt;
		if (!$bContRequested)
			$this->dtContRequested = "";
		else if ($this->dtContRequested == "")
			$this->dtContRequested = $dt;
		if (!$bContDone)
			$this->dtContDone = "";
		else if ($this->dtContDone == "")
			$this->dtContDone = $dt;
		if (!$bPayRequested)
			$this->dtPayRequested = "";
		else if ($this->dtPayRequested == "")
			$this->dtPayRequested = $dt;
		if (!$bPaid)
			$this->dtPaid = "";
		else if ($this->dtPaid == "")
			$this->dtPaid = $dt;
		$this->funded = $bFunded;
		$this->approved = $bApproved;
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::forceState update failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function updateState()
	{
		//------------------
		if ($this->dtStarted == "")
		{
			$this->state = ROUND_STATE_ERROR;
			XLogWarn("Round::updateState state error: dtStarted empty");
		}
		else if (XMaskContains($this->statsMode, (ROUND_STATS_MODE_FAH_WORKER | ROUND_STATS_MODE_FAH_SCORE)) && $this->dtFahStatsRequested == "")
		{
			if ($this->dtFahStatsDone == "" && $this->dtStatsRequested == "" && $this->dtStatsDone == "" && $this->dtPaid == "" && $this->dtPayRequested == "")
			{
				if ($this->teamId !== false && is_numeric($this->teamId))
				{
					if (XMaskContains($this->statsMode, ROUND_STATS_MODE_FAH_WORKER))
						$this->state = ROUND_STATE_FAHSTATSREQ;
					else if (XMaskContains($this->statsMode, ROUND_STATS_MODE_FAH_SCORE))
						$this->state = ROUND_STATE_FAHCLIENTREQ;
					else
					{
						$this->state = ROUND_STATE_ERROR; // mode error, this was check for above
						XLogWarn("Round::updateState state error, mode error (fah req) statsMode: ".XVarDump($this->statsMode));
					}
				}
				else
				{
					$this->state = ROUND_STATE_ERROR; // no valid teamId
					XLogWarn("Round::updateState state error: validate team ID failed: ".XVarDump($this->teamId));
				}
			}
			else
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid/stats done yet
				XLogWarn("Round::updateState state error: Fah stats not requested but later step(s) completed (FahStatsDone: $this->dtFahStatsDone, StatsRequested: $this->dtStatsRequested, StatsDone: $this->dtStatsDone, Paid: $this->dtPaid, PayRequested: $this->dtPayRequested)");
			}
		}
		else if (XMaskContains($this->statsMode, (ROUND_STATS_MODE_FAH_WORKER | ROUND_STATS_MODE_FAH_SCORE)) && $this->dtFahStatsDone == "" && $this->dtStatsDone == "")
		{
			if ($this->dtStatsDone == "" && $this->dtPaid == "" && $this->dtPayRequested == "")
			{
				if (XMaskContains($this->statsMode, ROUND_STATS_MODE_FAH_WORKER))
					$this->state = ROUND_STATE_FAHSTATS;
				else if (XMaskContains($this->statsMode, ROUND_STATS_MODE_FAH_SCORE))
					$this->state = ROUND_STATE_FAHCLIENT;
				else
				{
					$this->state = ROUND_STATE_ERROR; // mode error, this was check for above
					XLogWarn("Round::updateState state error, mode error (fah) statsMode: ".XVarDump($this->statsMode));
				}
			}
			else
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid yet
				XLogWarn("Round::updateState state error: Fah stats not done but later step(s) completed (StatsRequested: $this->dtStatsRequested, StatsDone: $this->dtStatsDone, Fah StatsRequested(expected): $this->dtFahStatsRequested, Fah StatsDone: $this->dtFahStatsDone, Paid: $this->dtPaid, PayRequested: $this->dtPayRequested)");
			}
		}
		else if ( (XMaskContains($this->statsMode, ROUND_STATS_MODE_EO_WEEK_SCORE) || XMaskContains($this->statsMode, ROUND_STATS_MODE_EO_TOTAL_SCORE) || XMaskContains($this->statsMode, ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE)) && $this->dtStatsRequested == "")
		{
			if ($this->dtStatsDone == "" && $this->dtPaid == "" && $this->dtPayRequested == "")
			{
				if ($this->teamId !== false && is_numeric($this->teamId))
					$this->state = ROUND_STATE_STATSREQ;
				else
				{
					$this->state = ROUND_STATE_ERROR; // no valid teamId
					XLogWarn("Round::updateState state error: validate team ID failed: ".XVarDump($this->teamId));
				}
			}
			else
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid/stats done yet
				XLogWarn("Round::updateState state error: stats not requested but later step(s) completed (StatsDone: $this->dtStatsDone, Paid: $this->dtPaid, PayRequested: $this->dtPayRequested)");
			}
		}
		else if ((XMaskContains($this->statsMode, ROUND_STATS_MODE_EO_WEEK_SCORE) || XMaskContains($this->statsMode, ROUND_STATS_MODE_EO_TOTAL_SCORE) || XMaskContains($this->statsMode, ROUND_STATS_MODE_EO_TOTAL_LESS_WEEK_SCORE)) && $this->dtStatsDone == "")
		{
			if ($this->dtPaid == "" && $this->dtPayRequested == "")
				$this->state = ROUND_STATE_STATS;
			else
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid yet
				XLogWarn("Round::updateState state error: Stats not done but later step(s) completed (Paid: $this->dtPaid, PayRequested: $this->dtPayRequested)");
			}
		}
		else if ($this->dtContRequested == "")
		{
			if ($this->dtPaid == "" && $this->dtPayRequested == "" && $this->dtContDone == "")
				$this->state = ROUND_STATE_CONT_REQ;
			else
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid yet
				XLogWarn("Round::updateState state error: Account Automation not done but later step(s) completed (Paid: $this->dtPaid, PayRequested: $this->dtPayRequested)");
			}
		}
		else if ($this->dtContDone == "")
		{
			if ($this->dtPaid == "" && $this->dtPayRequested == "")
				$this->state = ROUND_STATE_CONT;
			else
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid yet
				XLogWarn("Round::updateState state error: Account Automation not done but later step(s) completed (Paid: $this->dtPaid, PayRequested: $this->dtPayRequested)");
			}
		}
		else if ($this->dtPaid == "")
		{
			if ($this->dtPayRequested == "")
				$this->state = ROUND_STATE_PAYREQ;
			else
			{
				if ($this->funded != true)
					$this->state = ROUND_STATE_WAIT_FUND;
				else if ($this->approved != true)
					$this->state = ROUND_STATE_WAIT_APPR;
				else
					$this->state = ROUND_STATE_PAYING;
			}
		}
		else
		{
			if ($this->dtPayRequested == "" || $this->dtStatsDone == "" || ($this->dtStatsRequested == "" && $this->dtFahStatsRequested == ""))
			{
				$this->state = ROUND_STATE_ERROR; // out of order
				XLogWarn("Round::updateState state error: paid but previous step(s) not completed (PayRequested: $this->dtPayRequested, StatsDone: $this->dtStatsDone, StatsRequested: $this->dtStatsRequested, FahStatsRequested: $this->dtFahStatsRequested) ");
			}
			else if ($this->approved != true)
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid before approved
				XLogWarn("Round::updateState state error: paid but not approved (Approved: $this->approved, Funded: $this->funded)");
			}
			else if ($this->funded != true)
			{
				$this->state = ROUND_STATE_ERROR; // shouldn't be paid before funded
				XLogWarn("Round::updateState state error: paid but not funded (Approved: $this->approved, Funded: $this->funded)");
			}
			else
				$this->state = ROUND_STATE_DONE;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		$oldestActive = $Config->Get(CFG_ROUND_OLDEST_ACTIVE);
		if ($oldestActive !== false && !is_numeric($oldestActive))
			$oldestActive = 0;
		//------------------
		if (ROUND_DEBUG_STATES && ($oldestActive != 0 && $oldestActive <= $this->id))
			XLogDebug("Round::updateState Round: $this->id, State: $this->state");
		//------------------
	}
	//------------------
	function isActive()
	{
		//------------------
		return ($this->state != ROUND_STATE_NONE && $this->state != ROUND_STATE_ERROR && $this->state != ROUND_STATE_DONE ? true : false);
	}
	//------------------
	function stateText()
	{
		//------------------
		$names = array(	ROUND_STATE_NONE => 'None',
						ROUND_STATE_ERROR => 'State Error',
						ROUND_STATE_FAHSTATSREQ => 'Request Stats',
						ROUND_STATE_FAHSTATS => 'Check Stats',
						ROUND_STATE_FAHCLIENTREQ => 'FahClient Request',
						ROUND_STATE_FAHCLIENT => 'FahClient Check',
						ROUND_STATE_STATSREQ => 'Request User Stats',
						ROUND_STATE_STATS => 'Check User Stats',
						ROUND_STATE_CONT_REQ => 'Request Contributions',
						ROUND_STATE_CONT => 'Check Contributions',
						ROUND_STATE_PAYREQ => 'Request Payment',
						ROUND_STATE_WAIT_FUND => 'Waiting for Funds',
						ROUND_STATE_WAIT_APPR => 'Waiting for Approval',
						ROUND_STATE_PAYING => 'Paying',
						ROUND_STATE_DONE => 'Done');
		//------------------
		return $names[$this->state];		
	}
	//------------------
	function step()
	{
		//------------------
		if ($this->state === ROUND_STATE_FAHSTATSREQ)
		{
			if (!$this->requestFahWorkers())
			{
				XLogError("Round::step requestFahWorkers failed");
				return false;
			}
		}
		else if ($this->state === ROUND_STATE_FAHSTATS)
		{
			// nothing to do but wait
		}
		else if ($this->state === ROUND_STATE_FAHCLIENTREQ)
		{
			if (!$this->requestFahStats())
			{
				XLogError("Round::step requestFahStats failed");
				return false;
			}
		}
		else if ($this->state === ROUND_STATE_FAHCLIENT)
		{
			if (!$this->checkFahStats())
			{
				XLogError("Round::step checkFahStats failed");
				return false;
			}
		}
		else if ($this->state === ROUND_STATE_STATSREQ)
		{
			if (!$this->requestStats())
			{
				XLogError("Round::step requestStats failed");
				return false;
			}
		}
		else if ($this->state === ROUND_STATE_STATS)
		{
			if (!$this->checkStats())
			{
				XLogError("Round::step checkStats failed");
				return false;
			}
		}
		else if ($this->state == ROUND_STATE_CONT_REQ)
		{
			if (!$this->requestContributions())
			{
				XLogError("Round::step requestContributions ailed");
				return false;
			}
		}
		else if ($this->state == ROUND_STATE_CONT)
		{
			if (!$this->stepContributions())
			{
				XLogError("Round::step stepContributions failed");
				return false;
			}
		}
		else if ($this->state === ROUND_STATE_PAYREQ)
		{
			if (!$this->requestPayouts())
			{
				XLogError("Round::step requestPayouts failed");
				$Payouts = new Payouts() or die("Create object failed");
				if (!$Payouts->deleteAllRound($this->id))
					XLogError("Round::step Payouts deleteAllRound failed");
				return false;
			}
		}
		else if ($this->state === ROUND_STATE_WAIT_FUND || $this->state === ROUND_STATE_WAIT_APPR || $this->state === ROUND_STATE_PAYING)
		{
			if (!$this->checkPayout())
			{
				XLogError("Round::step checkPayout failed");
				return false;
			}
		}
		//------------------
		return true;
	}
	//------------------
	function set($row)
	{
		//------------------
		$this->comment = false; // load on demand
		//------------------
		$this->id   = $row[DB_ROUND_ID];
		$this->dtStarted = (isset($row[DB_ROUND_DATE_STARTED]) ? $row[DB_ROUND_DATE_STARTED] : "");
		$this->dtFahStatsRequested = (isset($row[DB_ROUND_DATE_FAH_STATS_REQUESTED]) ? $row[DB_ROUND_DATE_FAH_STATS_REQUESTED] : "");
		$this->dtFahStatsDone = (isset($row[DB_ROUND_DATE_FAH_STATS_DONE]) ? $row[DB_ROUND_DATE_FAH_STATS_DONE] : "");
		$this->dtStatsRequested = (isset($row[DB_ROUND_DATE_STATS_REQUESTED]) ? $row[DB_ROUND_DATE_STATS_REQUESTED] : "");
		$this->dtStatsDone = (isset($row[DB_ROUND_DATE_STATS_DONE]) ? $row[DB_ROUND_DATE_STATS_DONE] : "");
		$this->dtContRequested = (isset($row[DB_ROUND_DATE_CONT_REQUESTED]) ? $row[DB_ROUND_DATE_CONT_REQUESTED] : "");
		$this->dtContDone = (isset($row[DB_ROUND_DATE_CONT_DONE]) ? $row[DB_ROUND_DATE_CONT_DONE] : "");
		$this->dtPayRequested = (isset($row[DB_ROUND_DATE_PAY_REQUESTED]) ? $row[DB_ROUND_DATE_PAY_REQUESTED] : "");
		$this->dtPaid = (isset($row[DB_ROUND_DATE_PAID]) ? $row[DB_ROUND_DATE_PAID] : "");
		$this->statsMode = (isset($row[DB_ROUND_STATS_MODE]) ? $row[DB_ROUND_STATS_MODE] : ROUND_STATS_MODE_NONE);
		$this->teamId = $row[DB_ROUND_TEAM_ID];
		$this->payMode = (isset($row[DB_ROUND_PAY_MODE]) ? $row[DB_ROUND_PAY_MODE] : ROUND_PAY_MODE_NONE);
		$this->payRate = (isset($row[DB_ROUND_PAY_RATE]) ? $row[DB_ROUND_PAY_RATE] : 0.0);
		$this->totalWork = (isset($row[DB_ROUND_TOTAL_WORK]) ? $row[DB_ROUND_TOTAL_WORK] : 0);
		$this->totalPay = (isset($row[DB_ROUND_TOTAL_PAY]) ? $row[DB_ROUND_TOTAL_PAY] : 0.0);
		$this->approved = (isset($row[DB_ROUND_APPROVED]) &&  $row[DB_ROUND_APPROVED] != 0 ?  true : false); 
		$this->funded = (isset($row[DB_ROUND_FUNDED]) &&  $row[DB_ROUND_FUNDED] != 0 ?  true : false);  
		//------------------
		if ($this->dtStarted != "")
			if (strtotime($this->dtStarted) === false)
				$this->dtStarted = "";
		if ($this->dtFahStatsRequested != "")
			if (strtotime($this->dtFahStatsRequested) === false)
				$this->dtFahStatsRequested = "";
		if ($this->dtFahStatsDone != "")
			if (strtotime($this->dtFahStatsDone) === false)
				$this->dtFahStatsDone = "";
		if ($this->dtStatsRequested != "")
			if (strtotime($this->dtStatsRequested) === false)
				$this->dtStatsRequested = "";
		if ($this->dtStatsDone != "")
			if (strtotime($this->dtStatsDone) === false)
				$this->dtStatsDone = "";
		if ($this->dtContRequested != "")
			if (strtotime($this->dtContRequested) === false)
				$this->dtContRequested = "";
		if ($this->dtContDone != "")
			if (strtotime($this->dtContDone) === false)
				$this->dtContDone = "";
		if ($this->dtPayRequested != "")
			if (strtotime($this->dtPayRequested) === false)
				$this->dtPayRequested = "";
		if ($this->dtPaid != "")
			if (strtotime($this->dtPaid) === false)
				$this->dtPaid = "";
		//------------------
		$this->updateState();
		//------------------
	}
	//------------------
	function setMaxSizes()
	{
		global $dbRoundFields;
		//------------------		
		$this->id 	 = -1;
		$this->comment	 = $dbRoundFields->GetMaxSize(DB_ROUND_COMMENT);
		$this->dtStarted	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_STARTED);
		$this->dtFahStatsRequested	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_FAH_STATS_REQUESTED);
		$this->dtFahStatsDone	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_FAH_STATS_DONE);
		$this->dtStatsRequested	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_STATS_REQUESTED);
		$this->dtStatsDone	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_STATS_DONE);
		$this->dtContRequested	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_CONT_REQUESTED);
		$this->dtContDone	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_CONT_DONE);
		$this->dtPayRequested= $dbRoundFields->GetMaxSize(DB_ROUND_DATE_PAY_REQUESTED);
		$this->dtPaid	 	 = $dbRoundFields->GetMaxSize(DB_ROUND_DATE_PAID);
		$this->statsMode	 	= $dbRoundFields->GetMaxSize(DB_ROUND_STATS_MODE);
		$this->teamId	 		 = $dbRoundFields->GetMaxSize(DB_ROUND_TEAM_ID);
		$this->payMode	 		 = $dbRoundFields->GetMaxSize(DB_ROUND_PAY_MODE);
		$this->payRate	 		 = $dbRoundFields->GetMaxSize(DB_ROUND_PAY_RATE);
		$this->totalWork	 = $dbRoundFields->GetMaxSize(DB_ROUND_TOTAL_WORK);
		$this->totalPay	 = $dbRoundFields->GetMaxSize(DB_ROUND_TOTAL_PAY);
		$this->approved	 = $dbRoundFields->GetMaxSize(DB_ROUND_APPROVED);
		$this->funded	 = $dbRoundFields->GetMaxSize(DB_ROUND_FUNDED);
		//------------------
	}
	//------------------
	function Update()
	{
		global $db, $dbRoundFields;
		//---------------------------------
		$dbRoundFields->ClearValues();
		$dbRoundFields->SetValue(DB_ROUND_DATE_FAH_STATS_REQUESTED, $this->dtFahStatsRequested);
		$dbRoundFields->SetValue(DB_ROUND_DATE_FAH_STATS_DONE, $this->dtFahStatsDone);
		$dbRoundFields->SetValue(DB_ROUND_DATE_STATS_REQUESTED, $this->dtStatsRequested);
		$dbRoundFields->SetValue(DB_ROUND_DATE_STATS_DONE, $this->dtStatsDone);
		$dbRoundFields->SetValue(DB_ROUND_DATE_CONT_REQUESTED, $this->dtContRequested);
		$dbRoundFields->SetValue(DB_ROUND_DATE_CONT_DONE, $this->dtContDone);
		$dbRoundFields->SetValue(DB_ROUND_DATE_PAY_REQUESTED, $this->dtPayRequested);
		$dbRoundFields->SetValue(DB_ROUND_DATE_PAID, $this->dtPaid);
		$dbRoundFields->SetValue(DB_ROUND_STATS_MODE, $this->statsMode);
		$dbRoundFields->SetValue(DB_ROUND_TEAM_ID, $this->teamId);
		$dbRoundFields->SetValue(DB_ROUND_PAY_MODE, $this->payMode);
		$dbRoundFields->SetValue(DB_ROUND_PAY_RATE, $this->payRate);
		$dbRoundFields->SetValue(DB_ROUND_TOTAL_WORK, $this->totalWork);
		$dbRoundFields->SetValue(DB_ROUND_TOTAL_PAY, $this->totalPay);
		$dbRoundFields->SetValue(DB_ROUND_APPROVED, $this->approved);
		$dbRoundFields->SetValue(DB_ROUND_FUNDED, $this->funded);
		//---------------------------------
		$sql = $dbRoundFields->scriptUpdate(DB_ROUND_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Round::Update - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->updateState();
		//------------------
		return true;
	}
	//------------------
	function getComment($default = "")
	{
		global $db, $dbRoundFields;
		//------------------
		if ($this->comment !== false)
			return $this->comment;
		//------------------
		$dbRoundFields->ClearValues();
		$dbRoundFields->SetValue(DB_ROUND_COMMENT);
		//------------------
		$sql = $dbRoundFields->scriptSelect(DB_ROUND_ID."=".$this->id, false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Rounds::getComment - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$row = $qr->GetRowArray();
		if ($row === false || sizeof($row) == 0)
		{
			XLogError("Rounds::getComment - round not found, idx: $this->id, reply: ".XVarDump($row));
			return false;
		}
		//------------------
		if (!isset($row[DB_ROUND_COMMENT]))
			return $default;
		//------------------
		$this->comment =$row[DB_ROUND_COMMENT];
		//------------------
		return $this->comment;
	}
	//------------------
	function setComment($comment = false)
	{
		global $db, $dbRoundFields;
		//------------------
		$dbRoundFields->ClearValues();
		$dbRoundFields->SetValue(DB_ROUND_COMMENT, ($comment === false ? NULL : $comment));
		//------------------
		$sql = $dbRoundFields->scriptUpdate(DB_ROUND_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Round::setComment - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function requestFahWorkers()
	{
		//------------------
		$automation = new Automation() or die("Create object failed");
		//------------------
		if (!$automation->StartFahStatsPage())
		{
			XLogError("Round::requestFahWorkers - automation StartFahStatsPage failed");
			return false;
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtFahStatsRequested = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::requestFahWorkers update failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function requestFahStats()
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$FahClient = new FahClient() or die ("Create object failed");
		//------------------
		if (!$FahClient->pollTeam($this->id, $this->teamId))
		{
			$Stats = new Stats() or die ("Create object failed");
			XLogError("Round::requestFahStats FahClient pollTeam failed. Deleting all stats for this round ($this->id), as they may be incomplete.");
			if (!$Stats->deleteAllRound($this->id)) // clean up from failed incomplete stat list
				XLogError("Round::requestFahStats cleanup after failed FahStats poll failed to deleteAllRound Stats");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 20)
			XLogDebug("Round::requestFahStats: $secDiff");
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtFahStatsRequested = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::requestFahStats update failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function checkFahStats()
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$FahClient = new FahClient() or die ("Create object failed");
		//------------------
		if (!$FahClient->checkStats($this->id))
		{
			XLogError("Round::checkFahStats FahClient checkStats failed");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::checkFahStats: $secDiff");
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtStatsDone = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::checkFahStats update failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function requestStats()
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$stats = new Stats() or die ("Create object failed");
		$workers = new Workers() or die ("Create object failed");
		$wallet = new Wallet() or die ("Create object failed");
		//------------------
		$sectionStartUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$workerList = $workers->getWorkers();
		if ($workerList === false)
		{
			XLogError("Round::requestStats workers failed to getWorkers");
			return false;
		}
		//------------------
		$addWorkers = array();
		foreach ($workerList as $worker)
			if (!$worker->disabled)
			{
				if (!$worker->validAddress || $worker->address != "")
				{
					$isValid = $wallet->isValidAddress($worker->address);
					if ($isValid === false)
					{
						XLogError("Round::requestStats wallet isValidAddress failed");
						return false;
					}
					$worker->validAddress = ($isValid == $worker->address ? true : false);
					if ($worker->validAddress && !$worker->UpdateValidAddress())
					{
						XLogError("Round::requestStats worker failed to UpdateValidAddress");
						return false;
					}
				}
				
				$skip = false;
				
				if (!XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS) && (!$worker->validAddress || $worker->address == ""))
					$skip = 1;
				else if (XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_INC_UNKOWN_WORKERS) && $worker->validAddress && $worker->address != "")
					$skip = 2;
				else if (XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_NAMED_WORKERS_ONLY) && $worker->address == $worker->uname)
					$skip = 3;
					
				//XLogDebug("Round::requestStats skip: ".($skip === false ? "F" : $skip).", worker name: $worker->uname, address: $worker->address, validAddress: ".($worker->validAddress ? "T" : "F"));
				if ($skip === false)
					$addWorkers[] = $worker;
			}
		//------------------
		$secDiff = XDateTimeDiff($sectionStartUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::requestStats(validate): $secDiff");
		//------------------
		$sectionStartUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		foreach ($addWorkers as $worker)
			if (!$stats->addStat($this->id, $this->statsMode, $worker->id, false /*reload*/))
			{
				XLogError("Round::requestStats stats failed to addStat");
				return false;
			}
		//------------------
		$secDiff = XDateTimeDiff($sectionStartUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::requestStats(addStat): $secDiff");
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtStatsRequested = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::requestStats update failed");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::requestStats: $secDiff");
		//------------------
		return true;
	}
	//------------------
	function checkStats()
	{
		//------------------
		$stats = new Stats() or die ("Create object failed");
		//------------------
		$statsList = $stats->findRoundStats($this->id);
		if ($statsList === false)
		{
			XLogError("Round::checkStats stats failed to findRoundStats");
			return false;
		}
		//------------------
		foreach ($statsList as $stat)
			if ($stat->dtPolled === false || $stat->dtPolled == "")
				return true; // not done
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtStatsDone = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::checkStats update failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function getPayoutBalance()
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		if (!function_exists("bcsub"))
		{
			XLogError("Round::getPayoutBalance Required PHP dependency BC Math not found.");
			return false;
		}
		//------------------
		$Wallet = new Wallet() or die ("Create object failed");
		//------------------
		if (!$Wallet->Init())
		{
			XLogError("Round::getPayoutBalance Wallet init failed");
			return false;
		}
		//------------------
		$fullBalance = $Wallet->getBalance();
		if ($fullBalance === false)
		{
			XLogError("Round::getPayoutBalance Wallet failed to getBalance");
			return false;
		}
		//------------------
		//$totalFees = $Wallet->getTotalFees();
		$totalFees = $Wallet->getEstFee();
		if ($totalFees === false)
		{
			XLogError("Round::getPayoutBalance Wallet failed to getTotalFees");
			return false;
		}
		//------------------
		$balance = bcsub($fullBalance, $totalFees, 8);
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::getPayoutBalance: $secDiff");
		//------------------
		return $balance;		
	}
	//------------------
	function requestPayouts($reparse = false)
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		if (!function_exists("bcmul") || !function_exists("bcadd") || !function_exists("bccomp"))
		{
			XLogError("Round::requestPayouts Required PHP dependency BC Math not found.");
			return false;
		}
		//------------------
		$stats = new Stats() or die ("Create object failed");
		$workers = new Workers() or die ("Create object failed");
		$payouts = new Payouts() or die ("Create object failed");
		$Config = new Config() or die("Create object failed");
		$Contributions = new Contributions() or die("Create object failed");
		//------------------
		$digits = $Config->Get(CFG_ROUND_PAY_DIGITS);
		if ($digits === false)
		{
			XLogError("Rounds::requestPayouts Config Get pay digits failed");
			return false;
		}
		//------------------
		$statsList = $stats->findRoundStats($this->id, false/*orderBy default*/, ($reparse ? false : true)/*includeWithPayout*/);
		if ($statsList === false)
		{
			XLogError("Round::requestPayouts stats failed to findRoundStats");
			return false;
		}
		//------------------
		$roundConts = $Contributions->findRoundContributions($this->id);
		if ($roundConts === false)
		{
			XLogError("Round::requestPayouts Contributions findRoundContributions id $this->id failed");
			return false;
		}
		//------------------
		$totWork = 0;
		$totPay = "0";
		//------------------
		if ($this->payMode == ROUND_PAY_MODE_NONE)
		{
			XLogWarn("Round::requestPayouts paymode set to none. Completing without payouts. (worker ID was $stat->workerUID), but stat $stat->id exist for round $this->id.");
		}
		else if ( ($this->payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_RATE)
		{
			//------------------
			foreach ($statsList as $stat)
			{
				//------------------
				$work = $stat->work();
				//------------------
				if ($work !== false && $work != 0)
				{
					//------------------
					$worker = $workers->getWorker($stat->workerIdx);
					if ($worker === false)
						XLogWarn("Round::requestPayouts worker index $stat->workerIdx not found (worker ID was $stat->workerUID), but stat $stat->id exist for round $this->id.");
					else 
					{
						//------------------
						$pay = bcmul($work, $this->payRate, 8);
						//------------------
						foreach ($roundConts as $cont)
							if ($cont->mode == CONT_MODE_EACH && $cont->dtDone !== "" && $cont->value > 0.0)
								$pay = bcadd($pay, $cont->value, 8);
						//------------------
						$totWork += $work;
						$totPay = bcadd($totPay, $pay, 8);
						//------------------
						$payoutIdx = $payouts->addPayout($this->id, $stat->workerIdx, $worker->address, $pay);
						if ($payoutIdx === false)
						{
							XLogError("Round::requestPayouts payouts failed to addPayout");
							return false;
						}
						//------------------
						$stat->payoutIdx = $payoutIdx;
						//------------------
						if (!$stat->Update())
						{
							XLogError("Round::requestPayouts Update stat with new payoutIdx failed");
							return false;
						}
						//------------------
					}
				}
				//------------------
			} // foreach ($statsList as $stat)
			//------------------
		} // else if ( ($this->payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_RATE)
		else if ( ($this->payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_PERCENT)
		{
			//------------------
			$transWait = $Config->Get(CFG_ROUND_TRANS_WAIT);
			if ($transWait === false)
			{
				$transWait = DEFAULT_ROUND_TRANS_WAIT;
				if (!$Config->Set(CFG_ROUND_TRANS_WAIT, $transWait))
				{
					XLogError("Rounds::requestPayouts Config Set def trans wait failed");
					return false;
				}
			}
			if (!is_numeric($transWait))
			{
				XLogError("Rounds::requestPayouts validate trans wait failed: ".XVarDump($transWait));
				return false;
			}
			$lastTrans = $Config->Get(CFG_ROUND_LAST_TRANSACTION);
			//------------------
			$payMinimum = $Config->Get(CFG_ROUND_PAY_MINIMUM);
			if ($payMinimum === false)
			{
				$payMinimum = "".DEFAULT_ROUND_PAY_MINIMUM;
				if (!$Config->Set(CFG_ROUND_PAY_MINIMUM, $payMinimum))
				{
					XLogError("Rounds::requestPayouts Config Set def pay minimum failed");
					return false;
				}
			}
			//------------------
			if ($lastTrans === false || $lastTrans == "")
			{
				XLogWarn("Rounds::requestPayouts Config Get last trans, no previous transaction time recorded.");
			}
			else
			{
				$minDiff = XDateTimeDiff($lastTrans, false/*now*/, false/*UTC*/, 'i'/*minutes*/);
				if ($minDiff === false)
				{
					XLogError("Rounds::requestPayouts XDateTimeDiff failed. Cannot verify last trans time. lastPoll: '$lastTrans'");
					return false;
				}
				if ($minDiff < $transWait)
				{
					XLogWarn("Rounds::requestPayouts Waiting $transWait minutes for previous transactions. Current elapsed $minDiff minutes.");
					return true;
				}
			}
			//------------------
			if (XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_DRYRUN))
				$balance = $this->totalPay; // dry run, balance after fake contributions in totalPay
			else
				$balance = $this->getPayoutBalance();
			//------------------
			if ($balance === false)
			{
				XLogError("Round::requestPayouts getPayoutBalance failed");
				return false;
			}
			//------------------ adjust balance for each contributions
			$activeWorkers = 0;
			foreach ($statsList as $stat)
			{
				//------------------
				$work = $stat->work();
				//------------------
				if ($work !== false && $work != 0 && $stat->team == $this->teamId)
					$activeWorkers++;
				//------------------
			}
			//------------------
			$originalBalance = $balance;
			if ($activeWorkers > 0)
				foreach ($roundConts as $cont)
					if ($cont->mode == CONT_MODE_EACH && $cont->dtDone !== "" && $cont->value > 0.0)
					{
						$awarded = bcmul($activeWorkers, $cont->value, 8);
						XLogNotify("Round::requestPayouts deducting cont $cont->id: $cont->value (each) x $activeWorkers (worker count) = $awarded from balance(pre): $balance");
						$balance = bcsub($balance, $awarded, 8);
					}
			//------------------
			XLogWarn("Round::requestPayouts balance for percentage payout is $balance, full balance detectected $originalBalance");
			//------------------
			if (bccomp($balance, "0.0", 8) == 1) // greater than zero 
			{
				//------------------
				foreach ($statsList as $stat)
				{
					//------------------
					$work = $stat->work();
					//------------------
					if ($work !== false && $work != 0 && $stat->team == $this->teamId)
					{
						$worker = $workers->getWorker($stat->workerIdx);
						if ($worker === false)
							XLogWarn("Round::requestPayouts worker index $stat->workerIdx not found (worker ID was $stat->workerUID), but stat $stat->id exist for round $this->id.");
						else 
							$totWork += $work;
					}
					//------------------
				} // foreach ($statsList as $stat)
				//------------------
				if ($reparse) // override totals to start where left off
				{
					$totWork = $this->totalWork;
					$totPay = $this->totalPay;
				}
				//------------------
				XLogDebug("Round::requestPayouts Percent balance: '$balance', total work: '$totWork' ");
				foreach ($statsList as $stat)
				{
					//------------------
					$work = $stat->work();
					//------------------
					if ($work !== false && $work != 0 && $stat->team == $this->teamId)
					{
						//------------------
						$worker = $workers->getWorker($stat->workerIdx);
						if ($worker === false)
							XLogWarn("Round::requestPayouts worker index $stat->workerIdx not found (worker ID was $stat->workerUID), but stat $stat->id exist for round $this->id.");
						else 
						{
							//------------------
							$pay = bcmul($balance, bcdiv($work, $totWork, 8), $digits);
							//------------------
							foreach ($roundConts as $cont)
								if ($cont->mode == CONT_MODE_EACH && $cont->dtDone !== "" && $cont->value > 0.0)
									$pay = bcadd($pay, $cont->value, 8);
							//------------------
							if ($payMinimum === "" || bccomp($pay, $payMinimum, 8) >= 0) // greater than minimum
							{ 
								$totPay = bcadd($totPay, $pay, 8);
								XLogDebug("Round::requestPayouts Percent round id: $this->id, stat id: '$stat->id', worker idx: '$stat->workerIdx', worker name: '$worker->uname',worker address: '$worker->address', pay: '$pay'/'$totPay', work: $work/$totWork, digits: $digits");
								$payoutIdx = $payouts->addPayout($this->id, $stat->workerIdx, $worker->address, $pay);
								if ($payoutIdx === false)
								{
									XLogError("Round::requestPayouts payouts failed to addPayout");
									return false;
								}
								$stat->payoutIdx = $payoutIdx;
								if (!$stat->Update())
								{
									XLogError("Round::requestPayouts Update stat with new payoutIdx failed");
									return false;
								}
							}
							//------------------
						}
						//------------------
					}
					//------------------
				} // foreach ($statsList as $stat) 
				//------------------
			} // if (bccomp($balance, "0.0", 8) == 1) // greater than zero
			//------------------
		} // else if ( ($this->payMode & ROUND_PAY_MODE_MASK) == ROUND_PAY_MODE_PERCENT)
		else
		{
			XLogWarn("Round::requestPayouts paymode unsuported '".XVarDump($this->payMode)."'. Completing without payouts. (worker ID was $stat->workerUID), but stat $stat->id exist for round $this->id.");
		}
		//------------------
		$this->totalWork = $totWork;
		$this->totalPay = $totPay;
		//------------------
		if (!$reparse)
		{
			$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
			$this->dtPayRequested = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		}
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::requestPayouts update failed");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::requestPayouts: $secDiff");
		//------------------
		return true;
	}
	//------------------
	function requestContributions()
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$Contributions = new Contributions() or die("Create object failed");
		//------------------
		$contList = $Contributions->findRoundContributions(-1 /*roundIdx*/ );
		if ($contList === false)
		{
			XLogError("Round::requestContributions Contributions findRoundContributions failed");
			return false;
		}
		//------------------
		if (XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_DRYRUN))
		{
			//------------------
			$this->totalPay = $this->getPayoutBalance(); // dry run, balance after fake contributions in totalPay, prime with balance
			//------------------
			if ($this->totalPay === false)
			{
				XLogError("Round::requestContributions dry run getPayoutBalance failed");
				return false;
			}
			//------------------
		}
		//------------------
		XLogDebug("Round::requestContributions total pay: ".XVarDump($this->totalPay).", contList size: ".sizeof($contList));
		foreach ($contList as $cont)
		{
			//------------------
			if ($cont->mode != CONT_MODE_NONE)
				if (!$Contributions->addContribution($this->id, $cont->number, $cont->name, $cont->mode, $cont->account, $cont->value, $cont->flags))
				{
					XLogError("Round::requestContributions Contributions addContribution number $cont->number failed");
					return false; 
				}
			//------------------
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtContRequested = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::requestContributions update failed");
			return false;
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::requestContributions: $secDiff");
		//------------------
		return true;
	}
	//------------------
	function stepContributions($reparse = false)
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$Contributions = new Contributions() or die("Create object failed");
		//------------------
		$roundConts = $Contributions->findRoundContributions($this->id);
		if ($roundConts === false)
		{
			XLogError("Round::stepContributions Contributions findRoundContributions round $this->id failed");
			return false; 
		}
		//------------------
		$done = true;
		//------------------
		foreach ($roundConts as $cont)
			if ($cont->dtDone == "")
			{
				if (!$this->stepContribution($cont, $reparse))
				{
					XLogError("Round::stepContributions stepContribution failed, cont id: $cont->id");
					$done = false;
					break;
				}
				if ($cont->dtDone == "")
					$done = false;
			}
		//------------------
		if ($done)
		{
			//------------------
			$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
			$this->dtContDone = $nowUtc->format(MYSQL_DATETIME_FORMAT);
			//------------------
			if (!$this->Update())
			{
				XLogError("Round::stepContributions update failed");
				return false;
			}
			//------------------
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::stepContributions: $secDiff");
		//------------------
		return true;
	}
	//------------------
	function stepContribution($cont, $reparse = false)
	{
		//------------------
		$Stats = new Stats() or die ("Create object failed");
		$Wallet = new Wallet() or die("Create object failed");
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!$Wallet->Init())
		{
			XLogError("Round::stepContribution Wallet init failed");
			return false;
		}
		//------------------
		$fullBalance = $Wallet->getBalance();
		if ($fullBalance === false)
		{
			XLogError("Round::stepContribution Wallet failed to getBalance");
			return false;
		}
		//------------------
		$digits = $Config->Get(CFG_ROUND_PAY_DIGITS);
		if ($digits === false)
		{
			XLogError("Rounds::stepContribution Config Get pay digits failed");
			return false;
		}
		//------------------
		$isDryRun = XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_DRYRUN);
		//------------------
		XLogDebug("Round::stepContribution dry run: ".($isDryRun ? "YES" : "NO").", mode: $cont->mode, value: $cont->value, account: $cont->account, flags: $cont->flags");
		//------------------
		if ($cont->mode == CONT_MODE_NONE)
		{
			//------------------
			if (!$cont->UpdateOutcome(CONT_OUTCOME_NONE, true/*isDone*/))
			{
				XLogError("Round::stepContribution UpdateOutcome (disabled) failed");
				return false;
			}
			//------------------
			return true;
		}
		//------------------
		if (!is_numeric($cont->value))
		{
			XLogError("Round::stepContribution validate value is numer failed, number: $cont->id, value: ".XVarDump($cont->value));
			if (!$cont->UpdateOutcome(CONT_OUTCOME_FAILED_WONTFIX, true/*isDone*/))
				XLogError("Round::stepContribution UpdateOutcome (disabled) failed");
			return false; 
		}
		//------------------
		$retVal = true;
		$outcome = CONT_OUTCOME_NONE;
		$txid = false;
		//------------------
		$value = $cont->value;
		$isRequired = XMaskContains($cont->flags, CONT_FLAG_REQUIRED);
		//------------------
		$activeAccount = $Wallet->getActiveAccount();
		//------------------
		$accountBalance = $Wallet->getBalance($cont->account); // returns 0.0 if the account doesn't exist
		if ($accountBalance === false)
		{
			XLogError("Round::stepContribution Wallet getBalance, account $cont->account, failed");
			return false; 
		}
		//------------------
		$estcontfee = $Wallet->getEstContFee();
		if ($estcontfee === false)
		{
			XLogError("Round::stepContribution Wallet getEstContFee failed");
			return false; 
		}
		//------------------
		$accountBalance = bcsub($accountBalance, $estcontfee, $digits);
		//------------------
		if ($cont->mode == CONT_MODE_FLAT)
		{
			//------------------
			// flat rate, value doesn't change
			//------------------
		}
		else if ($cont->mode == CONT_MODE_EACH)
		{
			//------------------
			$statsList = $Stats->findRoundStats($this->id, false/*orderBy default*/, ($reparse ? false : true)/*includeWithPayout*/);
			if ($statsList === false)
			{
				XLogError("Round::requestPayouts Stats failed to findRoundStats");
				return false;
			}
			//------------------
			$value = "0.0";
			foreach ($statsList as $stat)
			{
				$work = $stat->work();
				if ($work !== false && $work > 0)
					$value = bcadd($value, $cont->value, 8);
			}
			//------------------
		}
		else if ($cont->mode == CONT_MODE_ALL)
		{
			//------------------
			$value = $accountBalance; 
			$cont->value = $value;
			if (!$cont->Update())
				XLogError("Round::stepContribution Update contribution (all value) failed");
			//------------------
		}
		else if ($cont->mode == CONT_MODE_PERCENT)
		{
			//------------------
			$transWait = $Config->Get(CFG_ROUND_TRANS_WAIT);
			if ($transWait === false)
			{
				$transWait = DEFAULT_ROUND_TRANS_WAIT;
				if (!$Config->Set(CFG_ROUND_TRANS_WAIT, $transWait))
				{
					XLogError("Rounds::requestPayouts Config Set def trans wait failed");
					return false;
				}
			}
			if (!is_numeric($transWait))
			{
				XLogError("Rounds::stepContribution validate trans wait failed: ".XVarDump($transWait));
				return false;
			}
			$lastTrans = $Config->Get(CFG_ROUND_LAST_TRANSACTION);
			if ($lastTrans === false || $lastTrans == "")
			{
				XLogWarn("Rounds::stepContribution Config Get last trans, no previous transaction time recorded.");
			}
			else
			{
				$minDiff = XDateTimeDiff($lastTrans, false/*now*/, false/*UTC*/, 'i'/*minutes*/);
				if ($minDiff === false)
				{
					XLogError("Rounds::stepContribution XDateTimeDiff failed. Cannot verify last trans time. lastPoll: '$lastTrans'");
					return false;
				}
				if ($minDiff < $transWait)
				{
					XLogWarn("Rounds::stepContribution Waiting $transWait minutes for previous transactions. Current elapsed $minDiff minutes.");
					return false;
				}
			}
			//------------------
			if ($isDryRun === true)
				$balance = $this->totalPay;
			else
				$balance = $fullBalance;
			//------------------
			$valueWas = $value;
			$value = $value / 100.0; // Value is a whole number of percent. Convert to fractional decimal value.
			$value = bcmul($balance, $value, $digits); // multiply by current payout ballance (ballance minus fee)
			//------------------
			XLogError("Round::stepContribution number: $cont->id, percent. Bal: $balance, valueWas: $valueWas, value: $value, digits: $digits");
			//------------------
		}
		else // $cont->mode
		{
			XLogError("Round::stepContribution number: $cont->id, unsupported mode: ".XVarDump($cont->mode));
			if (!$cont->UpdateOutcome(CONT_OUTCOME_FAILED_WONTFIX, true/*isDone*/))
				XLogError("Round::stepContribution UpdateOutcome (unsupported mode) failed");
			return false; 
		}
		//------------------
		if ($retVal === false)
		{
			//------------------
			// already want to return fail (wait), no more payments, just update outcome and return			
			//------------------
		}
		else if ($isDryRun == true)
		{
			//------------------
			XLogDebug("Round::stepContribution number $cont->id faking dry run. cont account: ".XVarDump($cont->account).", activeAccount: ".XVarDump($activeAccount).", value: ".XVarDump($value).", prev Accountbal: ".XVarDump($accountBalance));
			$this->totalPay = bcadd($this->totalPay, $value, $digits);
			$outcome = CONT_OUTCOME_PAID;
			//------------------
		}
		else if (bccomp($accountBalance, $value, 8) < 0) // accountBalance not greater than (1) or equal to (0) value
		{
			//------------------
			XLogNotify("Round::stepContribution number $cont->id insufficient funds. Accountbal: ".XVarDump($accountBalance).", value: ".XVarDump($value).", return :".bccomp($accountBalance, $value, 8));
			$outcome = ($isRequired ? CONT_OUTCOME_NOFUNDS_WAITING : CONT_OUTCOME_NOFUNDS_SKIPPED);
			$retVal = ($isRequired ? false : true);
			//------------------
		}
		else if (bccomp($value, "0.0", 8) != 1) // value not greater than zero
		{
			//------------------
			XLogNotify("Round::stepContribution number $cont->id value zero, marking paid. value: ".XVarDump($value).", return :".bccomp($value, "0.0", 8));
			$outcome = CONT_OUTCOME_PAID;
			//------------------
		}
		else 
		{
			//------------------
			$activeAddress = $Wallet->getAccountAddress($activeAccount);
			if ($activeAddress === false || sizeof($activeAddress) < 1 || strlen($activeAddress[0]) < 2)
			{
				XLogError("Rounds::stepContribution Wallet getAccountAddress of activeAccount: ".XVarDump($activeAccount)." failed result: ".XVarDump($activeAddress));
				return false;
			}
			$activeAddress = $activeAddress[0];
			//------------------
			$value = round(bcadd($value, "0.0", 8), 8); // trim to 8 decimal points by adding zero
			//------------------
			$txid = $Wallet->send($cont->account, $activeAddress, $value, false/*minConf*/, $cont->name);
			if ($txid === false)
			{
				XLogNotify("Round::stepContribution number $cont->id Wallet send failed. cont account: ".XVarDump($cont->account).", activeAddress: ".XVarDump($activeAddress).", value: ".XVarDump($value));
				$outcome = ($isRequired ? CONT_OUTCOME_FAILED_WAITING : CONT_OUTCOME_FAILED_SKIPPING);
			}
			else
			{
				//------------------
				XLogDebug("Round::stepContribution number $cont->id move success. cont account: ".XVarDump($cont->account).", activeAccount: ".XVarDump($activeAccount).", value: ".XVarDump($value).", prev Accountbal: ".XVarDump($accountBalance));
				$outcome = CONT_OUTCOME_PAID;
				//------------------
				$Config = new Config() or die("Create object failed");
				//------------------
				$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
				if (!$Config->Set(CFG_ROUND_LAST_TRANSACTION, $nowUtc->format(MYSQL_DATETIME_FORMAT)))
					XLogError("Rounds::stepContribution number $cont->id, Config failed to set last transaction time (continuing).");
				//------------------
			}
			//------------------
		} 
		//------------------
		if (!$cont->UpdateOutcome($outcome, ($outcome == CONT_OUTCOME_PAID || !$isRequired)/*isDone*/, $txid))
			XLogError("Round::stepContribution UpdateOutcome failed");
		//------------------
		return $retVal;
	}
	//------------------
	function checkPayout()
	{
		//------------------
		if (!$this->checkFunded())
		{
			XLogError("Round::checkPayout checkFunded failed");
			return false;
		}
		//------------------
		if ($this->funded != true)
			return true; // waiting for funded
		//------------------
		if ($this->approved != true)
			return true; // waiting for approved
		//------------------
		if (!$this->sendPayout())
		{
			XLogError("Round::checkPayout sendPayout failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function checkFunded()
	{
		//------------------
		$wasFunded = $this->funded;
		//------------------
		if (XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_DRYRUN))
			$this->funded = true; // fake payout, dry run, always funded
		else
		{
			//------------------
			$wallet = new Wallet() or die ("Create object failed");
			//------------------
			if (!$wallet->Init())
			{
				XLogError("Round::checkFunded wallet init failed");
				return false;
			}
			//------------------
			$fullBalance = $wallet->getBalance();
			if ($fullBalance === false)
			{
				XLogError("Round::checkFunded wallet failed to getBalance");
				return false;
			}
			//------------------
			$totalFees = $wallet->getTotalFees();
			if ($totalFees === false)
			{
				XLogError("Round::checkFunded wallet failed to getTotalFees");
				return false;
			}
			//------------------
			$balance = bcsub($fullBalance, $totalFees, 8);
			//------------------
			if (bccomp($this->totalPay, $balance, 8) <= 0) // balance is larger or equal
				$this->funded = true;
			else
				$this->funded = false;
			//------------------
		}
		//------------------
		if ($wasFunded != $this->funded)
			if (!$this->Update())
			{
				XLogError("Round::checkFunded update failed");
				return false;
			}
		//------------------
		return true;
	}
	//------------------
	function sendPayout($reparse = false)
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		$payouts = new Payouts() or die ("Create object failed");
		//------------------
		$payoutList = $payouts->findRoundPayouts($this->id, false/*orderBy default*/, $reparse /*includePaid*/);
		if ($payoutList === false)
		{
			XLogError("Round::sendPayout payouts failed to findRoundPayouts");
			return false;
		}
		//------------------
		$total = "0";
		$addressValues = array();
		foreach ($payoutList as $payout)
		{
			if (isset($addressValues[$payout->address])) // duplicate addresses may cause send to fail
				$addressValues[$payout->address] = round(bcadd($addressValues[$payout->address], $payout->pay, 8), 8);
			else
				$addressValues[$payout->address] = round(bcadd($payout->pay, "0.0", 8), 8); // trim to 8 decimal points by adding zero
			$total = bcadd($total, $payout->pay, 8);
			XLogDebug("Round::sendPayout total: $total, address: '".$payout->address."', value: '".XVarDump($addressValues[$payout->address])."', pay: ".XVarDump($payout->pay));
		}
		//------------------
		if (bccomp($this->totalPay, $total, 8) != 0)
		{
			XLogError("Round::sendPayout payout totals don't match. Payouts from list total ".var_export($total, true).", but request total was ".var_export($this->totalPay, true));
			return false;
		}
		//------------------
		if (bccomp($this->totalPay, "0.0", 0) == 0 || bccomp($this->totalPay, "-0.0", 0) == 0) // zero total payout rounded to 0 decimal points. preventative work around for bccomp -0 != 0
		{
			//------------------
			XLogWarn("Round::sendPayout Round $this->id total pay determined to be none/zero. (Payouts from list total: ".var_export($total, true).", payout from request total:".var_export($this->totalPay, true) + ") Marking round complete and returning success. Payout List: ".var_export($payoutList, true));
			//------------------
		}
		else if (XMaskContains($this->payMode, ROUND_PAY_MODE_FLAG_DRYRUN))
		{
			//------------------
			XLogWarn("Round::sendPayout Round $this->id payMode set to dry run, FAKING PAYOUT!");
			$txid = "<test>";
			//------------------
		}
		else // Payout has output to pay
		{
			//------------------
			if (sizeof($payoutList) == 0)
			{
				XLogError("Round::sendPayout Round $this->id total pay present, but no payouts listed. (Payouts from list total: ".var_export($total, true).", payout from request total:".var_export($this->totalPay, true) + ") Marking round complete and returning success. Payout List: ".var_export($payoutList, true));
				return false;
			}
			//------------------
			$wallet = new Wallet() or die ("Create object failed");
			//------------------
			if (!$wallet->Init())
			{
				XLogError("Round::sendPayout wallet init failed");
				return false;
			}
			//------------------
			if (is_numeric($this->id) && is_string($this->dtStarted))
				$txComment = BRAND_TX_COMMENT."$this->id $this->dtStarted";
			else
				$txComment = "";
			//------------------
			$txid = $wallet->sendMany(false /*sendAccount*/, $addressValues, false/*minConf*/, $txComment);
			//------------------
			if ($txid === false)
			{
				XLogError("Round::sendPayout wallet failed to sendMany");
				return false;
			}
			//------------------
			$Config = new Config() or die("Create object failed");
			$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
			if (!$Config->Set(CFG_ROUND_LAST_TRANSACTION, $nowUtc->format(MYSQL_DATETIME_FORMAT)))
				XLogError("Rounds::sendPayout Config failed to set last transaction time (continuing).");
			//------------------
		}
		//------------------ Payout completed successfuly, update Round and payouts (if any)
		$retValue = true;
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->dtPaid = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Update())
		{
			XLogError("Round::sendPayout update failed");
			$retValue = false;
		}
		//------------------
		foreach ($payoutList as $payout)
		{
			//------------------
			$payout->dtPaid = $this->dtPaid;
			$payout->txid = $txid;
			//------------------
			if (!$payout->Update())
			{
				XLogError("Round::sendPayout payout update failed");
				$retValue = false;
			}
			//------------------
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("Round::sendPayout: $secDiff");
		//------------------
		return $retValue;
	}
	//------------------
} // class Round
//---------------
class Rounds
{
	//------------------
	var $rounds = array();
	var $isLoaded = false;
	//------------------
	function Install()
	{
		global $db, $dbRoundFields;
		//------------------------------------
		$sql = $dbRoundFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Rounds::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbRoundFields;
		//------------------------------------
		$sql = $dbRoundFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Rounds::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbRoundFields;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case 1: // added field comment in ver 2
				//---------------
				$sql = "INSERT INTO $dbRoundFields->tableName SELECT $oldTableName.*,'' AS ".DB_ROUND_COMMENT." FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Rounds::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			case $dbRoundFields->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbRoundFields->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Rounds::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Rounds::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function GetMaxSizes()
	{
		//------------------------------------
		$msizeRound = new Round();
		$msizeRound->setMaxSizes();
		//------------------------------------
		return $msizeRound;		
	}
	//------------------
	function Init()
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$val = $Config->Get(CFG_ROUND_TEAM_ID);
		if ($val === false && !$Config->Set(CFG_ROUND_TEAM_ID, DEFAULT_ROUND_TEAM_ID))
		{
			XLogError("Rounds::Init Config Set teamId failed");
			return false;
		}
		//------------------
		$val = $Config->Get(CFG_ROUND_STATS_MODE);
		if ($val === false && !$Config->Set(CFG_ROUND_STATS_MODE, DEFAULT_ROUND_STATS_MODE))
		{
			XLogError("Rounds::Init Config Set stats mode failed");
			return false;
		}
		//------------------
		$val = $Config->Get(CFG_ROUND_PAY_MODE);
		if ($val === false && !$Config->Set(CFG_ROUND_PAY_MODE, DEFAULT_ROUND_PAY_MODE))
		{
			XLogError("Rounds::Init Config Set pay mode failed");
			return false;
		}
		//------------------
		$val = $Config->Get(CFG_ROUND_PAY_RATE);
		if ($val === false && !$Config->Set(CFG_ROUND_PAY_RATE, DEFAULT_ROUND_PAY_RATE))
		{
			XLogError("Rounds::Init Config Set pay rate failed");
			return false;
		}
		//------------------
		$val = $Config->Get(CFG_ROUND_PAY_DIGITS);
		if ($val === false && !$Config->Set(CFG_ROUND_PAY_DIGITS, DEFAULT_ROUND_PAY_DIGITS))
		{
			XLogError("Rounds::Init Config Set pay digits failed");
			return false;
		}
		//------------------
		$val = $Config->Get(CFG_ROUND_TRANS_WAIT);
		if ($val === false && !$Config->Set(CFG_ROUND_TRANS_WAIT, DEFAULT_ROUND_TRANS_WAIT))
		{
			XLogError("Rounds::Init Config Set trans wait failed");
			return false;
		}
		//------------------
		$val = $Config->Get(CFG_ROUND_PAY_MINIMUM);
		if ($val === false && !$Config->Set(CFG_ROUND_PAY_MINIMUM, DEFAULT_ROUND_PAY_MINIMUM))
		{
			XLogError("Rounds::Init Config Set pay minimum failed");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function deleteRound($idx)
	{
		global $db, $dbRoundFields;
		//---------------------------------
		$sql = $dbRoundFields->scriptDelete(DB_ROUND_ID."=".$idx);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Rounds::deleteRound - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadRounds() === false)
		{
			XLogError("Rounds::deleteRound - loadRounds failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear()
	{
		global $db, $dbRoundFields;
		//---------------------------------
		$sql = $dbRoundFields->scriptDelete();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Rounds::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->rounds = array();
		//---------------------------------
		$this->isLoaded = true;
		//------------------
		return true;
	}
	//---------------------------------	
	function loadRoundRaw($idx)
	{
		global $db, $dbRoundFields;
		//------------------
		$dbRoundFields->SetValues();
		//------------------
		$sql = $dbRoundFields->scriptSelect(DB_ROUND_ID."=".$idx, false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Rounds::loadRoundRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadRound($idx)
	{
		//------------------
		$qr = $this->loadRoundRaw($idx);
		//------------------
		if ($qr === false)
		{
			XLogError("Rounds::loadRound - loadRoundRaw failed");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogWarn("Rounds::loadRound - index $idx not found.");
			return false;
		}
		//------------------
		return new Round($s);
	}
	//------------------
	function getRound($idx)
	{
		//---------------------------------
		if ($this->isLoaded)
			foreach ($this->rounds as $r)
				if ($r->id == $idx)
					return $r;
		//---------------------------------
		return $this->loadRound($idx);
	}
	//------------------
	function loadRoundsRaw($onlyDone = false, $maxCount = false, $sort = false)
	{
		global $db, $dbRoundFields;
		//------------------
		$dbRoundFields->SetValues();
		//------------------
		if ($onlyDone === true)
			$where = DB_ROUND_DATE_PAID." IS NOT NULL AND ".DB_ROUND_DATE_PAID."<>''";
		else
			$where = false;
		//------------------
		if ($sort === false)
			$orderby = DB_ROUND_ID;
		else
			$orderby = $sort;
		//------------------
		$sql = $dbRoundFields->scriptSelect($where /*where*/, $orderby /*orderby*/, $maxCount /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Rounds::loadRoundsRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadRounds($onlyDone = false, $maxCount = false, $sort = false)
	{
		$this->rounds = array();
		//------------------
		$qr = $this->loadRoundsRaw($onlyDone, $maxCount, $sort);
		//------------------
		if ($qr === false)
		{
			XLogError("Rounds::loadRounds - loadRoundsRaw failed");
			return false;
		}
		//------------------
		while ($s = $qr->GetRowArray())
			$this->rounds[] = new Round($s);
		//------------------
		$this->isLoaded = true;
		//------------------
		return $this->rounds;
	}
	//------------------
	function addRound($teamId, $statsMode, $payMode, $payRate = 0.0, $startAutomation = true)
	{
		global $db, $dbRoundFields;
		//------------------
		if (!is_numeric($statsMode))
		{
			XLogError("Rounds::addRound validate statsMode is_numeric failed for '$statsMode'");
			return false;
		}
		//------------------
		if (!is_numeric($teamId))
		{
			XLogError("Rounds::addRound validate teamId is_numeric failed for '$teamId'");
			return false;
		}
		//------------------
		if (!is_numeric($payRate))
		{
			XLogError("Rounds::addRound validate rate is_numeric failed for '$payRate'");
			return false;
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//---------------------------------
		$dbRoundFields->ClearValues();
		$dbRoundFields->SetValue(DB_ROUND_DATE_STARTED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbRoundFields->SetValue(DB_ROUND_STATS_MODE, $statsMode);
		$dbRoundFields->SetValue(DB_ROUND_PAY_MODE, $payMode);
		$dbRoundFields->SetValue(DB_ROUND_PAY_RATE, $payRate);
		$dbRoundFields->SetValue(DB_ROUND_TEAM_ID, $teamId);
		//------------------
		$sql = $dbRoundFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Rounds::addRound - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadRounds() === false)
		{
			XLogError("Rounds::addRound - loadRounds failed.");
			return false;
		}
		//---------------------------------
		if ($startAutomation !== false)
		{
			//---------------------------------
			$automation = new Automation() or die("Create object failed");
			//---------------------------------
			if (!$automation->StartRoundCheckPage())
			{
				XLogError("Rounds::addRound - automation StartRoundCheckPage failed");
				return false;
			}
			//---------------------------------
		}
		//---------------------------------
		return true;
	}
	//------------------
	function getRounds($onlyDone = false, $maxCount = false, $lastSort = false)
	{
		//---------------------------------
		if ($this->isLoaded)
			return $this->rounds;
		//---------------------------------
		return $this->loadRounds($onlyDone, $maxCount, $lastSort);
	}
	//------------------
	function getRoundDate($idx)
	{
		global $db, $dbRoundFields;
		//------------------
		if ($this->isLoaded)
			foreach ($this->rounds as $r)
				if ($r->id == $idx)
					return $r->dtStarted;
		//---------------------------------
		$dbRoundFields->ClearValues();
		$dbRoundFields->SetValue(DB_ROUND_DATE_STARTED);
		$sql = $dbRoundFields->scriptSelect(DB_ROUND_ID."=$idx", false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Rounds::getRoundDate - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$r = $qr->GetRow(); // returns one row, numerically indexed field array
		if (!$r || !isset($r[0]))
			return false;
		//------------------
		return $r[0];
	}
	//------------------
	function getLastRoudnIndex()
	{
		global $db, $dbRoundFields;
		//------------------
		$sql = "SELECT MAX(".DB_ROUND_ID.") as ID FROM ".DB_ROUND;
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Rounds::getLastRoudnIndex - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		if (!($qr = $qr->GetRowArray()))
		{
			XLogWarn("Rounds::getLastRoudnIndex - replied with no results.");
			return -1;
		}
		//------------------
		if (!isset($qr['ID']) || is_null($qr['ID']) || !is_numeric($qr['ID']))
		{
			//------------------
			XLogError("Rounds::getLastRoudnIndex - validate ID field in row failed: ".XVarDump($qr));
			return false;
			//------------------
		}
		//------------------
		return (int)$qr['ID'];
	}
	//------------------
	function getRoundBefore($idx)
	{
		global $db, $dbRoundFields;
		//------------------
		if (!is_numeric($idx))
		{
			XLogError("Rounds::getRoundBefore - validate is numeric failed idx: ".XVarDump($idx));
			return false;
		}
		//------------------
		$sql = "SELECT MAX(".DB_ROUND_ID.") as ID FROM ".DB_ROUND." WHERE ".DB_ROUND_ID."<$idx";
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Rounds::getRoundBefore - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		if (!($qr = $qr->GetRowArray()))
		{
			XLogWarn("Rounds::getRoundBefore - replied with no results.");
			return -1;
		}
		//------------------
		if (!isset($qr['ID']))
		{
			//------------------
			if (!is_null($qr['ID']))
				return -1;
			//------------------
			if (!is_numeric($qr['ID']))
			{
				XLogError("Rounds::getRoundBefore - validate ID field in row failed: ".XVarDump($qr));
				return false;
			}
			//------------------
		}
		//------------------
		return (int)$qr['ID'];
	}
	//------------------
} // class Rounds
//---------------
?>
