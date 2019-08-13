<?php
//-------------------------------------------------------------
/*
*	AutomationNewRound.php
*
* This page shouldn't be accessible to the web.
* This script should be executed every time a round
* ends, like once a week.
* 
* This script will simply create a new round.
* 
*/
//-------------------------------------------------------------
//---------------------------
require('./include/Init.php');
//---------------------------
XLogNotify("AutomationNewRound.php activated");
//---------------------------
class AutomationNewRound
{
	//---------------------------
	function Main()
	{
		$Rounds = new Rounds() or die("Create object failed");
		$Config = new Config() or die("Create object failed");
		//------------------
		$teamId = $Config->Get(CFG_ROUND_TEAM_ID);
		if ($teamId === false)
		{
			XLogError("AutomationNewRound::Main no round teamId set");
			return false;
		}
		//------------------
		if (!is_numeric($teamId))
		{
			XLogError("AutomationNewRound::Main validate teamId failed");
			return false;
		}
		//------------------
		$statsMode = $Config->Get(CFG_ROUND_STATS_MODE);
		if ($statsMode === false)
		{
			XLogError("AutomationNewRound::Main no round stats mode set");
			return false;
		}
		//------------------
		$payMode = $Config->Get(CFG_ROUND_PAY_MODE);
		if ($payMode === false)
		{
			XLogError("AutomationNewRound::Main no round pay mode set");
			return false;
		}
		//------------------
		$payRate = $Config->Get(CFG_ROUND_PAY_RATE);
		if ($payRate === false)
		{
			XLogError("AutomationNewRound::Main no round pay rate set");
			return false;
		}
		//------------------
		XLogNotify("AutomationNewRound::Main adding new round with pay mode: $payMode rate: $payRate");
		if (!$Rounds->addRound($teamId, $statsMode, $payMode, $payRate, true/*start round check automation*/))
		{
			XLogError("AutomationNewRound::Main rounds failed to addRound");
			return false;
		}
		//------------------
		$Automation = new Automation() or die("Create object failed");
		if (!$Automation->UpdateLastNewRound())
		{
			XLogError("AutomationNewRound::Main Automation UpdateLastNewRound failed");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
} // class AutomationNewRound
//---------------------------
$ar = new AutomationNewRound() or die("Create object failed");
if (!$ar->Main())
	echo "failed";
else
	echo "ok";
//---------------------------
?>