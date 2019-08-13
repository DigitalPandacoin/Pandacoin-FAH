<?php
//-------------------------------------------------------------
/*
*	AutomationRound.php
*
* This page shouldn't be accessible to the web.
* This script should be executed every couple of minutes
* by a cron job.
* 
* This script will check the state machine for active rounds.
* 
*/
//-------------------------------------------------------------
define('CFG_AUTOMATION_ROUND_MUTEX', 'auto_round_mutex');
//-------------------------------------------------------------
require('./include/Init.php');
//---------------------------
XLogNotify("AutomationRound.php activated");
//---------------------------
class AutomationRound
{
	var $Automation = false;
	var $Config = false;
	var $activeRounds = false;
	var $statsActive = false;
	var $noactionRounds = true;
	//---------------------------
	function Init()
	{
		//------------------
		$this->Config = new Config() or die("Create object failed");
		$this->Automation = new Automation() or die("Create object failed");
		$this->statsActive = false;
		//------------------
		return true;
	}
	//---------------------------
	function TryLock()
	{
		//------------------
		if ($this->Config === false)
		{
			XLogError("AutomationRound::TryLock validate Config object failed");
			return false;
		}
		//------------------
		$MutexTime = $this->Config->Get(CFG_AUTOMATION_ROUND_MUTEX);
		//------------------
		if ($MutexTime !== false && $MutexTime !== "")
		{
			//------------------
			$secDiff = XDateTimeDiff($MutexTime); // default against now in UTC returning seconds
			if ($secDiff === false)
			{
				XLogError("AutomationRound::TryLock XDateTimeDiff failed. Cannot verify MutexTime: '$MutexTime'");
				return false;
			}
			//------------------
			if ($secDiff < 240)
			{
				XLogNotify("AutomationRound::TryLock Mutex is still locked. Locked at '$MutexTime', $secDiff seconds ago.");
				return false;
			}
			else XLogNotify("AutomationRound::TryLock ignoring stale mutex '".CFG_AUTOMATION_ROUND_MUTEX."' that is $secDiff seconds old.");
			//------------------
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$strNowUtc = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Config->Set(CFG_AUTOMATION_ROUND_MUTEX, $strNowUtc))
		{
			XLogError("AutomationRound::TryLock Config set mutex failed");
			return false;
		}
		//------------------
		$MutexTime = $this->Config->Get(CFG_AUTOMATION_ROUND_MUTEX);
		//------------------
		if ($MutexTime !== $strNowUtc)
		{
			XLogWarn("AutomationRound::TryLock verify set mutex failed. Lost the race? Current mutex '$MutexTime', but just set it to '$strNowUtc'");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function Unlock()
	{
		//------------------
		if ($this->Config === false)
		{
			XLogError("AutomationRound::Unlock validate Config object failed");
			return false;
		}
		//------------------
		if (!$this->Config->Set(CFG_AUTOMATION_ROUND_MUTEX, ""))
		{
			XLogError("AutomationRound::Unlock Config set mutex to blank failed");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function FindActive()
	{
		//------------------
		$this->activeRounds = array();
		//------------------
		$rounds = new Rounds() or die("Create object failed");
		$roundList = $rounds->getRounds();
		if ($roundList === false)
		{
			XLogError("AutomationRound::FindActive rounds failed to getRounds");
			return false;
		}
		//------------------
		foreach ($roundList as $round)
			if ($round->state != ROUND_STATE_NONE && $round->state != ROUND_STATE_ERROR && $round->state != ROUND_STATE_DONE)
				$this->activeRounds[] = $round;
		//------------------
		return true;
	}
	//---------------------------
	function UpdateAutomation()
	{
		//------------------
		$roundActive = (sizeof($this->activeRounds) == 0 ? false : true); 
		//------------------
		if (!$this->Automation->setAutomationStates($roundActive, $this->statsActive))
		{
			XLogError("AutomationRound::UpdateAutomation automation failed to setAutomationStates");
			return false;
		}
		//------------------
		if ($roundActive === true)
		{
			//------------------
			if ($this->noactionRounds == true && !$this->Automation->IncRoundCheckNoAction())
			{
				XLogError("AutomationRound::UpdateAutomation automation failed to IncRoundCheckNoAction");
				return false;
			}
			//------------------
			if ($this->noactionRounds == false && !$this->Automation->ClearRoundCheckNoAction())
			{
				XLogError("AutomationRound::UpdateAutomation automation failed to ClearRoundCheckNoAction");
				return false;
			}
			//------------------
		}
		//------------------
		if (!$this->Automation->UpdateLastRoundCheck())
		{
			XLogError("AutomationRound::UpdateAutomation automation failed to UpdateLastRoundCheck");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function ExecuteActive($round)
	{
		//------------------
		$lastState = $round->state;
		//------------------
		if (!$round->step())
		{
			XLogError("AutomationRound::ExecuteActive round $round->id failed to step");
			return false;
		}
		//------------------
		if ($round->state === ROUND_STATE_STATSREQ || $round->state === ROUND_STATE_STATS)
			$this->statsActive = true;
		//------------------
		if ($lastState != $round->state)
			$this->noactionRounds = false;
		//------------------
		return true;
	}
	//---------------------------
	function StepActive()
	{
		//------------------
		if ($this->Config === false)
		{
			XLogError("AutomationRound::Unlock validate Config object failed");
			return false;
		}
		//------------------
		if (!$this->FindActive())
		{
			XLogError("AutomationRound::StepActive FindActive failed");
			return false;
		}
		//------------------
		if (sizeof($this->activeRounds) == 0) // none alive
		{
			//------------------
			XLogNotify("AutomationRound::StepActive no active rounds");
			//------------------
			if (!$this->Config->Set(CFG_ROUND_OLDEST_ACTIVE, 0))
			{
				XLogError("AutomationRound::StepActive Config Set oldest active (none) failed");
				return false;
			}
			//------------------
		}
		else
		{
			//------------------
			if (!$this->Config->Set(CFG_ROUND_OLDEST_ACTIVE, $this->activeRounds[0]->id))
			{
				XLogError("AutomationRound::StepActive Config Set oldest active failed");
				return false;
			}
			//------------------
			foreach ($this->activeRounds as $round)
				if (!$this->ExecuteActive($round))
					XLogError("AutomationRound::StepActive ExecuteActive round $round->id failed");
			//------------------
		}
		//------------------
		if (!$this->UpdateAutomation())
		{
			XLogError("AutomationRound::StepActive UpdateAutomation failed");
			return false;
		}
		//------------------
		return true;
	}		
	//---------------------------
	function Main()
	{
		//------------------
		if (!$this->Init())
		{
			XLogError("AutomationRound::Main init failed");
			return false;
		}
		//------------------
		if (!$this->TryLock())
		{
			XLogWarn("AutomationRound::Main TryLock failed");
			return false;
		}
		//------------------
		$retval = $this->StepActive();
		//------------------
		if (!$this->Unlock())
		{
			XLogError("AutomationRound::Main Unlock failed");
			return false;
		}
		//------------------
		return $retval;
	}
	//---------------------------
} // class AutomationRound
//---------------------------
$au = new AutomationRound() or die("Create object failed");
if (!$au->Main())
	echo "failed";
else
	echo "ok";
//---------------------------
?>
