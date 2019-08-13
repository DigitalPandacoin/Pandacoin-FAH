<?php
//-------------------------------------------------------------
/*
*	AutomationStat.php
*
* This page shouldn't be accessible to the web.
* This script should be executed every couple of minutes
* by a cron job.
* 
* This script will check the state machine for active rounds.
* 
*/
//-------------------------------------------------------------
define('AUTO_STAT_POLLS_PER_STEP', 3);
//---------------------------
require('./include/Init.php');
//---------------------------
XLogNotify("AutomationStat.php activated");
//---------------------------
class AutomationStat
{
	var $statsActive = false;
	var $stats = false;
	var $automation = false;
	//---------------------------
	function Init()
	{
		//------------------
		$this->stats = new Stats() or die("Create object failed");
		$this->automation = new Automation or die("Create object failed");
		//------------------
		$this->statsActive = $this->stats->findIncompleteStats();
		//------------------
		if ($this->statsActive === false)
		{
			XLogError("AutomationStat::Init stats failed to findIncompleteStats");
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
			XLogError("AutomationStat::Main Init failed");
			return false;
		}
		//------------------
		$val = $this->Poll();
		//------------------
		if ($val === true)
		{
			if (!$this->automation->ClearStatCheckNoAction())
			{
				XLogError("AutomationStat::Main automation ClearStatCheckNoAction failed");
				return false;
			}
		}
		else
		{
			if (!$this->automation->IncStatCheckNoAction())
			{
				XLogError("AutomationStat::Main automation IncStatCheckNoAction failed");
				return false;
			}
		}
		//------------------
		if (!$this->automation->UpdateLastStatCheck())
		{
			XLogError("AutomationStat::Main Automation UpdateLastStatCheck failed");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function Poll()
	{
		//------------------
		$startUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		if (sizeof($this->statsActive) != 0)
		{
			//------------------
			$statsPolled = 0;
			XLogDebug("AutomationStat::Poll iterating stats, count: ".sizeof($this->statsActive));
			//------------------
			foreach ($this->statsActive as $stat)
			{
				$val = $stat->pollStats();
				$debugString = "AutomationStat::Poll polled (first round) stat: $stat->id, didPoll: ".XVarDump($stat->didPoll).", return: ".XVarDump($val);
				if ($val !== true)
					XLogNotify($debugString);
				//else
				//	XLogDebug($debugString);
				if ($stat->didPoll === true) 
				{
					$statsPolled++;
					//XLogDebug("AutomationStat::Poll worker $stat->workerIdx stat $stat->id polled");
					if ($statsPolled >= AUTO_STAT_POLLS_PER_STEP)
					{
						//------------------
						$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
						if ($secDiff > 5)
							XLogDebug("AutomationStat::Poll: $secDiff");
						//------------------
						return $val; // one poll per itteration
					}
					sleep(STAT_POLL_RATELIMIT); // from include/Stats.php, wait before polling another
				}
			}
			//------------------
		}
		//------------------
		$secDiff = XDateTimeDiff($startUtc); // default against now in UTC returning seconds
		if ($secDiff > 5)
			XLogDebug("AutomationStat::Poll(None): $secDiff");
		//------------------
		return "None";
	}
	//---------------------------
} // class AutomationStat
//---------------------------
$as = new AutomationStat() or die("Create object failed");
if (!$as->Main())
	echo "failed";
else
	echo "ok";
//---------------------------
?>
