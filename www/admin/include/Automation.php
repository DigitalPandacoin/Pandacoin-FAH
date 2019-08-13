<?php
/*
 *	www/include/Automation.php
 * 
 * 
* 
*/
//---------------
define('AUTO_DEBUG_INCCLEAR_RATE', false); // verbose notification of incrementing or clearing no active counts
define('AUTO_DEBUG_INCCLEAR_NO_CRON', false); // verbose warning when clearning/incrementing No Action on a cron that isn't active
//---------------
define('CFG_CRON_CMD', 'cron_cmd');
define('CFG_PHP_CMD', 'php_cmd');
define('CFG_PHPCLI_CMD', 'phpcli_cmd');
define('CFG_WGET_CMD', 'wget_cmd');
define('CFG_BZIP2_CMD', 'bzip2_cmd');
//---------------
define('CFG_AUTO_ROUND_RATE_M', 'round_rate_m');
define('CFG_AUTO_CHECK_ROUND_RATE_M', 'check_round_rate_m');
define('CFG_AUTO_CHECK_STATS_RATE_M', 'check_stats_rate_m');
//---------------
define('CFG_AUTO_ROUND_NOACTION_COUNT', 'auto_round_noaction_count');
define('CFG_AUTO_STATS_NOACTION_COUNT', 'auto_stats_noaction_count');
define('CFG_AUTO_ROUND_CURRENT_RATE', 'auto_round_cur_rate');
define('CFG_AUTO_STATS_CURRENT_RATE', 'auto_stats_cur_rate');
define('CFG_AUTO_ROUND_START_RATE', 'auto_round_start_rate');
define('CFG_AUTO_STATS_START_RATE', 'auto_stats_start_rate');
//---------------
define('CFG_AUTO_PUBDATA_NOACTION_COUNT', 'auto_pubdata_noaction_count');
define('CFG_AUTO_PUBDATA_CURRENT_RATE', 'auto_pubdata_cur_rate');
define('CFG_AUTO_PUBDATA_START_RATE', 'auto_pubdata_start_rate');
//---------------
define('CFG_AUTO_NEWROUND_RATE_H', 'auto_newround_rate_h');
define('CFG_AUTO_NEWROUND_RATE_M', 'auto_newround_rate_m');
define('CFG_AUTO_NEWROUND_RATE_W', 'auto_newround_rate_w');
//---------------
define('AUTOMATION_ROUND_NOACTION_DOUBLE', 5); // double rate at nocation count
define('AUTOMATION_STATS_NOACTION_DOUBLE', 5); // double rate at nocation count
define('AUTOMATION_PUBDATA_NOACTION_DOUBLE', 5); // double rate at nocation count
define('AUTOMATION_ROUND_MAX_AUTO_RATE', 60); // minutes
define('AUTOMATION_STATS_MAX_AUTO_RATE', 60); // minutes
define('AUTOMATION_PUBDATA_MAX_AUTO_RATE', 60); // minutes
//---------------
define('CFG_AUTO_LAST_NEWROUND', 'auto_last_newround');
define('CFG_AUTO_LAST_ROUND', 'auto_last_round');
define('CFG_AUTO_LAST_STAT', 'auto_last_stat');
define('CFG_AUTO_LAST_FAH_STATS', 'auto_last_fahstat');
define('CFG_AUTO_LAST_PUBDATA', 'auto_last_pubdata');
//---------------
define('AUTOMATION_PAGE_NEWROUND', './AutomationNewRound.php');
define('AUTOMATION_PAGE_ROUND', './AutomationRound.php');
define('AUTOMATION_PAGE_STAT', './AutomationStat.php');
define('AUTOMATION_PAGE_FAH_STATS', './AutomationFahStat.php');
define('AUTOMATION_PAGE_PUBLIC_DATA', './AutomationPublicData.php');
//---------------
class Automation
{
	var $cron_cmd = false;
	var $php_cmd = false;
	var $phpcli_cmd = false;
	var $wget_cmd = false;
	var $bzip2_cmd = false;
	var $checkRoundRateM = false;
	var $checkStatsRateM = false;
	var $lockHandle = false;
	//------------------
	function __construct()
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$this->cron_cmd = $Config->Get(CFG_CRON_CMD);
		$this->php_cmd = $Config->Get(CFG_PHP_CMD);
		$this->phpcli_cmd = $Config->Get(CFG_PHPCLI_CMD);
		$this->wget_cmd = $Config->Get(CFG_WGET_CMD);
		$this->bzip2_cmd = $Config->Get(CFG_BZIP2_CMD);
		$this->checkRoundRateM = $Config->Get(CFG_AUTO_CHECK_ROUND_RATE_M);
		$this->checkStatsRateM = $Config->Get(CFG_AUTO_CHECK_STATS_RATE_M);
		//------------------
	}
	//------------------
	function Install($_cron_cmd = false, $_php_cmd = false, $_phpcli_cmd = false, $_wget_cmd = false, $_bzip2_cmd = false)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if ($_cron_cmd === false)
		{
			$this->cron_cmd = $Config->Get(CFG_CRON_CMD);
			if ($this->cron_cmd === false)
			{
				$this->cron_cmd = exec("which crontab", $output, $value);
				if ($value != 0 || $this->cron_cmd == "")
				{
					XLogError("Automation::Install failed to resolve crontab command. Output: ".var_export($output, true).", value: ".var_export($value, true));
					return false;
				}
			}
		}
		else 
			$this->cron_cmd = $_cron_cmd;
		if (!$Config->Set(CFG_CRON_CMD, $this->cron_cmd))
		{
			XLogError("Automation::Install Config failed to set cron cmd");
			return false;
		}
		//------------------
		if ($_php_cmd === false)
		{
			$this->php_cmd = $Config->Get(CFG_PHP_CMD);
			if ($this->php_cmd === false)
			{
				$this->php_cmd = exec("which php", $output, $value);
				if ($value != 0 || $this->php_cmd == "")
				{
					XLogError("Automation::Install failed to resolve php command. Output: ".var_export($output, true).", value: ".var_export($value, true));
					return false;
				}
			}
		}
		else
			$this->php_cmd = $_php_cmd;
		if (!$Config->Set(CFG_PHP_CMD, $this->php_cmd))
		{
			XLogError("Automation::Install Config failed to set php cmd");
			return false;
		}
		//------------------
		if ($_phpcli_cmd === false)
		{
			$this->phpcli_cmd = $Config->Get(CFG_PHPCLI_CMD);
			if ($this->phpcli_cmd === false)
			{
				$this->phpcli_cmd = exec("which php-cli", $output, $value);
				if ($value != 0 || $this->phpcli_cmd == "")
				{
					XLogWarn("Automation::Install (not supported) failed to resolve php-cli command. Output: ".var_export($output, true).", value: ".var_export($value, true));
					$this->phpcli_cmd = "";
				}
			}
		}
		else
			$this->phpcli_cmd = $_phpcli_cmd;
		if (!$Config->Set(CFG_PHPCLI_CMD, $this->phpcli_cmd))
		{
			XLogError("Automation::Install Config failed to set php-cli cmd");
			return false;
		}
		//------------------
		if ($_wget_cmd === false)
		{
			$this->wget_cmd = $Config->Get(CFG_WGET_CMD);
			if ($this->wget_cmd === false)
			{
				$this->wget_cmd = exec("which wget", $output, $value);
				if ($value != 0 || $this->wget_cmd == "")
				{
					XLogWarn("Automation::Install (not supported) failed to resolve wget command. Output: ".var_export($output, true).", value: ".var_export($value, true));
					$this->wget_cmd = "";
				}
			}
		}
		else
			$this->wget_cmd = $_wget_cmd;
		if (!$Config->Set(CFG_WGET_CMD, $this->wget_cmd))
		{
			XLogError("Automation::Install Config failed to set wget cmd");
			return false;
		}
		//------------------
		if ($_bzip2_cmd === false)
		{
			$this->bzip2_cmd = $Config->Get(CFG_BZIP2_CMD);
			if ($this->bzip2_cmd === false)
			{
				$this->bzip2_cmd = exec("which bzip2", $output, $value);
				if ($value != 0 || $this->bzip2_cmd == "")
				{
					XLogWarn("Automation::Install (not supported) failed to resolve bzip2 command. Output: ".var_export($output, true).", value: ".var_export($value, true));
					$this->bzip2_cmd = "";
				}
			}
		}
		else
			$this->bzip2_cmd = $_bzip2_cmd;
		if (!$Config->Set(CFG_BZIP2_CMD, $this->bzip2_cmd))
		{
			XLogError("Automation::Install Config failed to set bzip2 cmd");
			return false;
		}
		//------------------
		$checkRateRoundM = $Config->Get(CFG_AUTO_CHECK_ROUND_RATE_M);
		if ($checkRateRoundM === false)
		{
			$checkRateRoundM = 2;
			if (!$Config->Set(CFG_AUTO_CHECK_ROUND_RATE_M, $checkRateRoundM))
			{
				XLogError("Automation::Install Config failed to set checkRateRoundM");
				return false;
			}
		}
		//------------------
		$checkRateStatsM = $Config->Get(CFG_AUTO_CHECK_STATS_RATE_M);
		if ($checkRateStatsM === false)
		{
			$checkRateStatsM = 1;
			if (!$Config->Set(CFG_AUTO_CHECK_STATS_RATE_M, $checkRateStatsM))
			{
				XLogError("Automation::Install Config failed to set checkRateStatsM");
				return false;
			}
		}
		//------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
	
	}
	//------------------
	function lock($owner)
	{
		global $XLogInstance;
		global $Login;
		//------------------
		if ($this->lockHandle !== false)
		{
			XLogError("Automation::lock ($owner) already locked");
			return false;
		}
		//------------------
		if (!isset($XLogInstance) || !$XLogInstance->Initted || !is_writable($XLogInstance->LogFileDir))
		{
			XLogError("Automation::lock ($owner) Logger is not ready or the log directory is not writable. Wanted to write lock file");
			return false;
		}
		//------------------
		$owner .=  ' '.$Login->UserID." ".session_id();
		$fileName = XEnsureBackslash($XLogInstance->LogFileDir)."automation.lock";
		//------------------
		$fh = @fopen($fileName, "c");
		if ($fh === false)
		{
			XLogError("Automation::lock ($owner) open lock file failed: $fileName");
			return false;
		}
		//------------------
		//XLogDebug("Automation::lock ($owner) fileName: $fileName, handle: ".XVarDump($fh));
		$waitTill = time() + 15;
		//------------------
		while (!@flock($fh, LOCK_EX | LOCK_NB))
		{
			//------------------
			if (time() > $waitTill)
			{
				@fclose($fh);
				XLogError("Automation::lock ($owner) timed out waiting for lock");
				return false;
			}
			//------------------
			usleep(round(rand(1, 100)*1000)); //1-100 miliseconds (usleep is microseconds, not miliseconds) 
			//------------------
		}
		//------------------
		if (@fwrite($fh, $owner) === false)
		{
			@fclose($fh);
			XLogError("Automation::lock ($owner) write to file failed to verify lock");
			return false;
		}
		//------------------
		$this->lockHandle = $fh;
		//------------------
		return true;
	}
	//------------------
	function unlock()
	{
		//------------------
		$cleanUnlock = true;
		//------------------
		if ($this->lockHandle === false)
		{
			XLogError("Automation::unlock not locked");
			return false;
		}
		//------------------
		//XLogDebug("Automation::unlock handle: ".XVarDump($this->lockHandle));
		if (!@ftruncate($this->lockHandle, 0))
		{
			XLogError("Automation::unlock truncate lock file failed");
			$cleanUnlock = false;
		}
		//------------------
		if (!@flock($this->lockHandle, LOCK_UN))
		{
			XLogError("Automation::unlock unlock file failed");
			$cleanUnlock = false;
		}
		//------------------
		@fclose($this->lockHandle);
		$this->lockHandle = false;
		//------------------
		return $cleanUnlock;
	}
	//------------------
	function UpdateLastAuto($cfg)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//------------------
		if (!$Config->Set($cfg, $nowUtc->format(MYSQL_DATETIME_FORMAT)))
		{
			XLogError("Automation::UpdateLastAuto Config failed to set cfg: '$cfg'");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function StartPublicData($rate, $isnew = true)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!$this->AddCrontab($rate, AUTOMATION_PAGE_PUBLIC_DATA))
		{
			XLogError("Automation::StartPublicData - automation failed to AddCrontab");
			return false;
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_PUBDATA_NOACTION_COUNT, 0))
		{
			XLogError("Automation::StartPublicData - Config failed to set inactive count");
			return false;
		}
		//------------------
		if ($isnew)
		{
			if (!$Config->Set(CFG_AUTO_PUBDATA_START_RATE, $rate))
			{
				XLogError("Automation::StartPublicData - Config failed to set start rate");
				return false;
			}
			if (!$Config->Set(CFG_AUTO_PUBDATA_CURRENT_RATE, $rate))
			{
				XLogError("Automation::StartPublicData - Config failed to set current rate");
				return false;
			}
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_PUBDATA_CURRENT_RATE, $rate))
		{
			XLogError("Automation::StartPublicData - Config failed to set current rate");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function StopPublicData()
	{
		//------------------
		return $this->RemoveCrontab(AUTOMATION_PAGE_PUBLIC_DATA);
	}
	//------------------
	function ExecutePublicData()
	{
		return $this->executePage(AUTOMATION_PAGE_PUBLIC_DATA);
	}
	//------------------
	function UpdateLastPublicData()
	{
		//------------------
		return $this->UpdateLastAuto(CFG_AUTO_LAST_PUBDATA);
	}
	//------------------
	function IncPublicDataNoAction()
	{
		//------------------
		$hasCron = $this->hasCrontab(AUTOMATION_PAGE_PUBLIC_DATA);
		if ($hasCron === false)
		{
			XLogError("Automation::IncPublicDataNoAction - hasCrontab failed");
			return false;
		}
		//------------------
		if ($hasCron != AUTOMATION_PAGE_PUBLIC_DATA)
		{
			if (AUTO_DEBUG_INCCLEAR_NO_CRON)
				XLogWarn("Automation::IncPublicDataNoAction - cron not active");
			return true;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$noaction = $Config->Get(CFG_AUTO_PUBDATA_NOACTION_COUNT);
		if ($noaction === false || !is_numeric($noaction))
		{
			XLogError("Automation::IncPublicDataNoAction - config failed to get noaction count: ".XVarDump($noaction));
			return false;
		}
		//------------------
		$startrate = $Config->Get(CFG_AUTO_PUBDATA_START_RATE);
		if ($startrate === false || !is_numeric($startrate)) // not used here, but check that it is valid so we can go back to orig rate
		{
			XLogError("Automation::IncPublicDataNoAction - config failed to get start rate: ".XVarDump($startrate));
			return false;
		}
		//------------------
		$currate = $Config->Get(CFG_AUTO_PUBDATA_CURRENT_RATE);
		if ($currate === false || !is_numeric($currate))
		{
			XLogError("Automation::IncPublicDataNoAction - config failed to get cur rate: ".XVarDump($currate));
			return false;
		}
		//------------------
		$noaction = $noaction + 1;
		//------------------
		if ($noaction >= AUTOMATION_PUBDATA_NOACTION_DOUBLE && $currate < AUTOMATION_PUBDATA_MAX_AUTO_RATE)
		{
			//------------------
			$newrate = $currate * 2;
			if ($newrate > AUTOMATION_PUBDATA_MAX_AUTO_RATE)
				$newrate = AUTOMATION_PUBDATA_MAX_AUTO_RATE;
			//------------------
			if (AUTO_DEBUG_INCCLEAR_RATE)
				XLogDebug("Automation::IncPublicDataNoAction count incremented to: $noaction, triggered rate increase from start: $startrate, cur rate: $currate, to new rate: $newrate");
			//------------------
			if (!$this->StopPublicData())
			{
				XLogError("Automation::IncPublicDataNoAction - StopPublicData failed");
				return false;
			}
			//------------------
			if(!$this->StartPublicData($newrate, false /*isnew*/)) // resets noaction count, saves new rate, leaves start rate
			{
				XLogError("Automation::IncPublicDataNoAction - StartPublicData failed");
				return false;
			}
			//------------------
		}
		else if ($noaction < 100) // don't bother updating when super high
		{
			//------------------
			if (AUTO_DEBUG_INCCLEAR_RATE)
				XLogDebug("Automation::IncPublicDataNoAction count incremented to: $noaction");
			//------------------ no rate change just update noaction count
			if (!$Config->Set(CFG_AUTO_PUBDATA_NOACTION_COUNT, $noaction))
			{
				XLogError("Automation::IncPublicDataNoAction - config failed to set noaction count");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function ClearPublicDataNoAction()
	{
		//------------------
		$hasCron = $this->hasCrontab(AUTOMATION_PAGE_PUBLIC_DATA);
		if ($hasCron === false)
		{
			XLogError("Automation::ClearPublicDataNoAction - hasCrontab failed");
			return false;
		}
		//------------------
		if ($hasCron != AUTOMATION_PAGE_PUBLIC_DATA)
		{
			if (AUTO_DEBUG_INCCLEAR_NO_CRON)
				XLogWarn("Automation::ClearPublicDataNoAction - cron not active");
			return true;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$startrate = $Config->Get(CFG_AUTO_PUBDATA_START_RATE);
		if ($startrate === false || !is_numeric($startrate))
		{
			XLogError("Automation::ClearPublicDataNoAction - config failed to get start rate: ".XVarDump($startrate));
			return false;
		}
		//------------------
		$currate = $Config->Get(CFG_AUTO_PUBDATA_CURRENT_RATE);
		if ($currate === false || !is_numeric($currate))
		{
			XLogError("Automation::ClearPublicDataNoAction - config failed to get cur rate: ".XVarDump($currate));
			return false;
		}
		//------------------
		if ($startrate != $currate)
		{
			//------------------
			XLogDebug("Automation::ClearPublicDataNoAction setting rate back to start rate: $startrate");
			if (!$this->StopPublicData())
			{
				XLogError("Automation::ClearPublicDataNoAction - StopPublicData failed");
				return false;
			}
			//------------------
			if(!$this->StartPublicData($startrate, false /*isnew*/)) // resets noaction count, saves new rate
			{
				XLogError("Automation::ClearPublicDataNoAction - StartPublicData failed");
				return false;
			}
			//------------------
		}
		else
		{
			//------------------ no rate change just clear noaction count
			if (!$Config->Set(CFG_AUTO_PUBDATA_NOACTION_COUNT, 0))
			{
				XLogError("Automation::ClearPublicDataNoAction - config failed to set noaction count");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function StartNewRoundPage($min, $hour, $weekday)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if (!$this->AddCrontab("", AUTOMATION_PAGE_NEWROUND, 'none' /*days*/, $min, $hour, $weekday))
		{
			XLogError("Automation::StartNewRoundPage - automation failed to AddCrontab");
			return false;
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_NEWROUND_RATE_H, $hour))
		{
			XLogError("Automation::StartNewRoundPage - Config failed to set rate h");
			return false;
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_NEWROUND_RATE_M, $min))
		{
			XLogError("Automation::StartNewRoundPage - Config failed to set rate m");
			return false;
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_NEWROUND_RATE_W, $weekday)) // can be '*' for any
		{
			XLogError("Automation::StartNewRoundPage - Config failed to set rate w");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function StopNewRoundPage()
	{
		//------------------
		return $this->RemoveCrontab(AUTOMATION_PAGE_NEWROUND);
	}
	//------------------
	function ExecuteNewRoundPage()
	{
		return $this->executePage(AUTOMATION_PAGE_NEWROUND);
	}
	//------------------
	function UpdateLastNewRound()
	{
		//------------------
		return $this->UpdateLastAuto(CFG_AUTO_LAST_NEWROUND);
	}
	//------------------
	function StartFahStatsPage()
	{
		//------------------
		if (!$this->AddCrontab(1 /*rate*/, AUTOMATION_PAGE_FAH_STATS))
		{
			XLogError("Automation::StartNewRoundPage - automation failed to AddCrontab");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function StopFahStatsPage()
	{
		//------------------
		return $this->RemoveCrontab(AUTOMATION_PAGE_FAH_STATS);
	}
	//------------------
	function UpdateLastFahStats()
	{
		//------------------
		return $this->UpdateLastAuto(CFG_AUTO_LAST_FAH_STATS);
	}
	//------------------
	function StartRoundCheckPage($rate = false, $isnew = true)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if ($rate === false || !is_numeric($rate))
		{
			if ($this->checkRoundRateM === false)
			{
				XLogError("Automation::StartRoundCheckPage - automation checkRoundRateM is not ready");
				return false;
			}
			$rate = $this->checkRoundRateM;
		}
		//------------------
		if (!$this->AddCrontab($rate, AUTOMATION_PAGE_ROUND))
		{
			XLogError("Automation::StartRoundCheckPage - automation failed to AddCrontab");
			return false;
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_ROUND_NOACTION_COUNT, 0))
		{
			XLogError("Automation::StartRoundCheckPage - Config failed to set inactive count");
			return false;
		}
		//------------------
		if ($isnew)
			if (!$Config->Set(CFG_AUTO_ROUND_START_RATE, $rate))
			{
				XLogError("Automation::StartRoundCheckPage - Config failed to set start rate");
				return false;
			}
		//------------------
		if (!$Config->Set(CFG_AUTO_ROUND_CURRENT_RATE, $rate))
		{
			XLogError("Automation::StartRoundCheckPage - Config failed to set current rate");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function StopRoundCheckPage()
	{
		//------------------
		return $this->RemoveCrontab(AUTOMATION_PAGE_ROUND);
	}
	//------------------
	function ExecuteRoundCheckPage()
	{
		return $this->executePage(AUTOMATION_PAGE_ROUND);
	}
	//------------------
	function UpdateLastRoundCheck()
	{
		//------------------
		return $this->UpdateLastAuto(CFG_AUTO_LAST_ROUND);
	}
	//------------------
	function IncRoundCheckNoAction()
	{
		//------------------
		$hasCron = $this->hasCrontab(AUTOMATION_PAGE_ROUND);
		if ($hasCron === false)
		{
			XLogError("Automation::IncRoundCheckNoAction - hasCrontab failed");
			return false;
		}
		//------------------
		if ($hasCron != AUTOMATION_PAGE_ROUND)
		{
			if (AUTO_DEBUG_INCCLEAR_NO_CRON)
				XLogWarn("Automation::IncRoundCheckNoAction - cron not active");
			return true;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$noaction = $Config->Get(CFG_AUTO_ROUND_NOACTION_COUNT);
		if ($noaction === false || !is_numeric($noaction))
		{
			XLogError("Automation::IncRoundCheckNoAction - config failed to get noaction count: ".XVarDump($noaction));
			return false;
		}
		//------------------
		$startrate = $Config->Get(CFG_AUTO_ROUND_START_RATE);
		if ($startrate === false || !is_numeric($startrate)) // not used here, but check that it is valid so we can go back to orig rate
		{
			XLogError("Automation::IncRoundCheckNoAction - config failed to get start rate: ".XVarDump($startrate));
			return false;
		}
		//------------------
		$currate = $Config->Get(CFG_AUTO_ROUND_CURRENT_RATE);
		if ($currate === false || !is_numeric($currate))
		{
			XLogError("Automation::IncRoundCheckNoAction - config failed to get cur rate: ".XVarDump($currate));
			return false;
		}
		//------------------
		$noaction = $noaction + 1;
		//------------------
		if ($noaction >= AUTOMATION_ROUND_NOACTION_DOUBLE && $currate < AUTOMATION_ROUND_MAX_AUTO_RATE)
		{
			//------------------
			$newrate = $currate * 2;
			if ($newrate > AUTOMATION_ROUND_MAX_AUTO_RATE)
				$newrate = AUTOMATION_ROUND_MAX_AUTO_RATE;
			//------------------
			if (AUTO_DEBUG_INCCLEAR_RATE)
				XLogDebug("Automation::IncRoundCheckNoAction count incremented to: $noaction, triggered rate increase from start: $startrate, cur rate: $currate, to new rate: $newrate");
			//------------------
			if (!$this->StopRoundCheckPage())
			{
				XLogError("Automation::IncRoundCheckNoAction - StopRoundCheckPage failed");
				return false;
			}
			//------------------
			if(!$this->StartRoundCheckPage($newrate, false /*isnew*/)) // resets noaction count, saves new rate, leaves start rate
			{
				XLogError("Automation::IncRoundCheckNoAction - StartRoundCheckPage failed");
				return false;
			}
			//------------------
		}
		else if ($noaction < 100) // don't bother updating when super high
		{
			//------------------
			if (AUTO_DEBUG_INCCLEAR_RATE)
				XLogDebug("Automation::IncRoundCheckNoAction count incremented to: $noaction");
			//------------------ no rate change just update noaction count
			if (!$Config->Set(CFG_AUTO_ROUND_NOACTION_COUNT, $noaction))
			{
				XLogError("Automation::IncRoundCheckNoAction - config failed to set noaction count");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function ClearRoundCheckNoAction()
	{
		//------------------
		$hasCron = $this->hasCrontab(AUTOMATION_PAGE_ROUND);
		if ($hasCron === false)
		{
			XLogError("Automation::ClearRoundCheckNoAction - hasCrontab failed");
			return false;
		}
		//------------------
		if ($hasCron != AUTOMATION_PAGE_ROUND)
		{
			if (AUTO_DEBUG_INCCLEAR_NO_CRON)
				XLogWarn("Automation::ClearRoundCheckNoAction - cron not active");
			return true;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$startrate = $Config->Get(CFG_AUTO_ROUND_START_RATE);
		if ($startrate === false || !is_numeric($startrate))
		{
			XLogError("Automation::ClearRoundCheckNoAction - config failed to get start rate: ".XVarDump($startrate));
			return false;
		}
		//------------------
		$currate = $Config->Get(CFG_AUTO_ROUND_CURRENT_RATE);
		if ($currate === false || !is_numeric($currate))
		{
			XLogError("Automation::ClearRoundCheckNoAction - config failed to get cur rate: ".XVarDump($currate));
			return false;
		}
		//------------------
		if ($startrate != $currate)
		{
			//------------------
			XLogDebug("Automation::ClearRoundCheckNoAction setting rate back to start rate: $startrate");
			if (!$this->StopRoundCheckPage())
			{
				XLogError("Automation::ClearRoundCheckNoAction - StopRoundCheckPage failed");
				return false;
			}
			//------------------
			if(!$this->StartRoundCheckPage($startrate, false /*isnew*/)) // resets noaction count, saves new rate
			{
				XLogError("Automation::ClearRoundCheckNoAction - StartRoundCheckPage failed");
				return false;
			}
			//------------------
		}
		else
		{
			//------------------ no rate change just clear noaction count
			if (!$Config->Set(CFG_AUTO_ROUND_NOACTION_COUNT, 0))
			{
				XLogError("Automation::ClearRoundCheckNoAction - config failed to set noaction count");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function StartStatCheckPage($rate = false, $isnew = true)
	{
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		if ($rate === false || !is_numeric($rate))
		{
			if ($this->checkStatsRateM === false)
			{
				XLogError("Automation::StartStatCheckPage - automation checkStatsRateM is not ready");
				return false;
			}
			$rate = $this->checkStatsRateM;
		}
		//------------------
		if (!$this->AddCrontab($rate, AUTOMATION_PAGE_STAT))
		{
			XLogError("Automation::StartStatCheckPage - automation failed to AddCrontab");
			return false;
		}
		//------------------
		if (!$Config->Set(CFG_AUTO_STATS_NOACTION_COUNT, 0))
		{
			XLogError("Automation::StartStatCheckPage - Config failed to set inactive count");
			return false;
		}
		//------------------
		if ($isnew)
			if (!$Config->Set(CFG_AUTO_STATS_START_RATE, $rate))
			{
				XLogError("Automation::StartStatCheckPage - Config failed to set start rate");
				return false;
			}
		//------------------
		if (!$Config->Set(CFG_AUTO_STATS_CURRENT_RATE, $rate))
		{
			XLogError("Automation::StartStatCheckPage - Config failed to set current rate");
			return false;
		}
		//------------------
		return true;
	}
	//------------------
	function StopStatCheckPage()
	{
		//------------------
		return $this->RemoveCrontab(AUTOMATION_PAGE_STAT);
	}
	//------------------
	function ExecuteStatCheckPage()
	{
		return $this->executePage(AUTOMATION_PAGE_STAT);
	}
	//------------------
	function UpdateLastStatCheck()
	{
		//------------------
		return $this->UpdateLastAuto(CFG_AUTO_LAST_STAT);
	}
	//------------------
	function IncStatCheckNoAction()
	{
		//------------------
		$hasCron = $this->hasCrontab(AUTOMATION_PAGE_STAT);
		if ($hasCron === false)
		{
			XLogError("Automation::IncStatCheckNoAction - hasCrontab failed");
			return false;
		}
		//------------------
		if ($hasCron != AUTOMATION_PAGE_STAT)
		{
			if (AUTO_DEBUG_INCCLEAR_NO_CRON)
				XLogWarn("Automation::IncStatCheckNoAction - cron not active");
			return true;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$noaction = $Config->Get(CFG_AUTO_STATS_NOACTION_COUNT);
		if ($noaction === false || !is_numeric($noaction))
		{
			XLogError("Automation::IncStatCheckNoAction - config failed to get noaction count: ".XVarDump($noaction));
			return false;
		}
		//------------------
		$startrate = $Config->Get(CFG_AUTO_STATS_START_RATE);
		if ($startrate === false || !is_numeric($startrate)) // not used here, but check that it is valid so we can go back to orig rate
		{
			XLogError("Automation::IncStatCheckNoAction - config failed to get start rate: ".XVarDump($startrate));
			return false;
		}
		//------------------
		$currate = $Config->Get(CFG_AUTO_STATS_CURRENT_RATE);
		if ($currate === false || !is_numeric($currate))
		{
			XLogError("Automation::IncStatCheckNoAction - config failed to get cur rate: ".XVarDump($currate));
			return false;
		}
		//------------------
		$noaction = $noaction + 1;
		//------------------
		if ($noaction >= AUTOMATION_STATS_NOACTION_DOUBLE && $currate < AUTOMATION_STATS_MAX_AUTO_RATE)
		{
			//------------------
			$newrate = $currate * 2;
			if ($newrate > AUTOMATION_STATS_MAX_AUTO_RATE)
				$newrate = AUTOMATION_STATS_MAX_AUTO_RATE;
			//------------------
			XLogDebug("Automation::ClearStatCheckNoAction no act count: $noaction, old rate: $currate, new rate: $newrate");
			if (!$this->StopStatCheckPage())
			{
				XLogError("Automation::IncStatCheckNoAction - StopStatCheckPage failed");
				return false;
			}
			//------------------
			if(!$this->StartStatCheckPage($newrate, false /*isnew*/)) // resets noaction count, saves new rate, leaves start rate
			{
				XLogError("Automation::IncStatCheckNoAction - StartStatCheckPage failed");
				return false;
			}
			//------------------
		}
		else
		{
			//------------------
			if (AUTO_DEBUG_INCCLEAR_RATE)
				XLogDebug("Automation::IncStatCheckNoAction no act count: $noaction no rate change");
			//------------------ no rate change just update noaction count
			if (!$Config->Set(CFG_AUTO_STATS_NOACTION_COUNT, $noaction))
			{
				XLogError("Automation::IncStatCheckNoAction - config failed to set noaction count");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function ClearStatCheckNoAction()
	{
		//------------------
		$hasCron = $this->hasCrontab(AUTOMATION_PAGE_STAT);
		if ($hasCron === false)
		{
			XLogError("Automation::ClearStatCheckNoAction - hasCrontab failed");
			return false;
		}
		//------------------
		if ($hasCron != AUTOMATION_PAGE_STAT)
		{
			if (AUTO_DEBUG_INCCLEAR_NO_CRON)
				XLogWarn("Automation::ClearStatCheckNoAction - cron not active");
			return true;
		}
		//------------------
		$Config = new Config() or die("Create object failed");
		//------------------
		$startrate = $Config->Get(CFG_AUTO_STATS_START_RATE);
		if ($startrate === false || !is_numeric($startrate))
		{
			XLogError("Automation::ClearStatCheckNoAction - config failed to get start rate: ".XVarDump($startrate));
			return false;
		}
		//------------------
		$currate = $Config->Get(CFG_AUTO_STATS_CURRENT_RATE);
		if ($currate === false || !is_numeric($currate))
		{
			XLogError("Automation::ClearStatCheckNoAction - config failed to get cur rate: ".XVarDump($currate));
			return false;
		}
		//------------------
		if ($startrate != $currate)
		{
			//------------------
			XLogDebug("Automation::ClearStatCheckNoAction setting rate back to start rate: $startrate");
			//------------------
			if (!$this->StopStatCheckPage())
			{
				XLogError("Automation::ClearStatCheckNoAction - StopStatCheckPage failed");
				return false;
			}
			//------------------
			if(!$this->StartStatCheckPage($startrate, false /*isnew*/)) // resets noaction count, saves new rate
			{
				XLogError("Automation::ClearStatCheckNoAction - StartStatCheckPage failed");
				return false;
			}
			//------------------
		}
		else
		{
			//------------------ no rate change just clear noaction count
			if (!$Config->Set(CFG_AUTO_STATS_NOACTION_COUNT, 0))
			{
				XLogError("Automation::ClearStatCheckNoAction - config failed to set noaction count");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function AddCrontab($rate, $page, $rateUnit = 'm', $defMin = '*', $defHour = '*', $weekday = '*')
	{
		//------------------
		if ($this->cron_cmd === false || $this->php_cmd === false)
		{
			XLogError("Automation::AddCrontab cron/php cmds not ready");
			return false;
		}
		//------------------
		$pwd = exec('pwd', $output, $value); // this is the base PHP file's directory, ignoring require/include
		if ($value != 0)
		{
			XLogError("Automation::AddCrontab get present working directory failed");
			return false;
		}
		//------------------
		if ($rateUnit == 'm')
			$addcrontext = "*/$rate * * * $weekday cd $pwd; $this->php_cmd $page";
		else if ($rateUnit == 'h')
			$addcrontext = "$defMin */$rate * * $weekday cd $pwd; $this->php_cmd $page";
		else if ($rateUnit == 'd')
			$addcrontext = "$defMin $defHour */$rate * $weekday cd $pwd; $this->php_cmd $page";
		else if ($rateUnit == 'none')
			$addcrontext = "$defMin $defHour * * $weekday cd $pwd; $this->php_cmd $page";
		else 
		{
			XLogError("Automation::AddCrontab unsupported rate unit '$rateUnit'");
			return false;
		}
		//------------------
		$testcrontext =  " cd $pwd; $this->php_cmd $page";
		//------------------
		unset($output);
		//------------------
		if (!$this->lock("AddCrontab $page "))
		{
			XLogError("Automation::AddCrontab lock failed");
			return false;
		}
		//------------------
		$cmd = "$this->cron_cmd -l";
		exec($cmd, $output, $value);
		if (!is_array($output)) // returns value 1 on empty
		{
			XLogError("Automation::AddCrontab list current crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			if (!$this->unlock())
				XLogError("Automation::AddCrontab failed to unlock cleanly");
			return false;
		}
		//------------------
		$header = 'MAILTO=""'.PHP_EOL;
		//XLogError("Automation::AddCrontab list current crontabs half success. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
		$newtabs = array();
		foreach($output as $entry)
			if (strpos($entry, $testcrontext) === false && strpos($entry,  'MAILTO=') === false)
				$newtabs[] = $entry; // don't add matching entries
		//------------------
		$newtabs[] = $addcrontext.PHP_EOL;
		$tmpfilename = tempnam(sys_get_temp_dir(), 'dfhCronUpdate.txt');
		if (file_put_contents($tmpfilename, $header.join(PHP_EOL, $newtabs)) === false)
		{
			XLogError("Automation::AddCrontab output temporary file $tmpfilename failed");
			if (!$this->unlock())
				XLogError("Automation::AddCrontab failed to unlock cleanly");
			return false;
		}
		//------------------
		$cmd = "$this->cron_cmd $tmpfilename";
		exec($cmd, $output, $value); // this should replace the existing entries
		if ($value != 0)
		{
			XLogError("Automation::AddCrontab set/replace crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			if (!$this->unlock())
				XLogError("Automation::AddCrontab failed to unlock cleanly");
			return false;
		}
		//------------------
		if (!$this->unlock())
			XLogError("Automation::AddCrontab failed to unlock cleanly");
		//------------------
		//XLogError("Automation::AddCrontab list current crontabs success. New tabs: ".sizeof($newtabs).", Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
		XLogDebug("Automation::AddCrontab added: $page");
		//------------------
		return true;
	}
	//------------------
	function hasCrontab($page) // returns "" on success but false, $page on true
	{
		//------------------
		if ($this->cron_cmd === false || $this->php_cmd === false)
		{
			XLogError("Automation::hasCrontab cron/php cmds not ready");
			return false;
		}
		//------------------
		$pwd = exec('pwd', $output, $value); // this is the base PHP file's directory, ignoring require/include
		if ($value != 0)
		{
			XLogError("Automation::hasCrontab get present working directory failed");
			return false;
		}
		//------------------
		$testcrontext =  " cd $pwd; $this->php_cmd $page";
		//------------------
		unset($output);
		//------------------
		if (!$this->lock("hasCrontab $page "))
		{
			XLogError("Automation::hasCrontab lock failed");
			return false;
		}
		//------------------
		$cmd = "$this->cron_cmd -l";
		exec($cmd, $output, $value);
		//------------------
		if (!$this->unlock())
			XLogError("Automation::hasCrontab failed to unlock cleanly");
		//------------------
		if (!is_array($output)) // returns value 1 on empty
		{
			XLogError("Automation::hasCrontab list current crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			return false;
		}
		//------------------
		foreach($output as $entry)
			if (strpos($entry, $testcrontext) !== false)
				return $page;
		//------------------
		return "";
	}
	//------------------
	function RemoveCrontab($page)
	{
		//------------------
		if ($this->cron_cmd === false || $this->php_cmd === false)
		{
			XLogError("Automation::RemoveCrontab cron/php cmds not ready");
			return false;
		}
		//------------------
		$pwd = exec('pwd', $output, $value); // this is the base PHP file's directory, ignoring require/include
		if ($value != 0)
		{
			XLogError("Automation::RemoveCrontab get present working directory failed");
			return false;
		}
		//------------------
		$testcrontext =  " cd $pwd; $this->php_cmd $page";
		//------------------
		unset($output);
		//------------------
		if (!$this->lock("RemoveCrontab $page "))
		{
			XLogError("Automation::RemoveCrontab lock failed");
			return false;
		}
		//------------------
		$cmd = "$this->cron_cmd -l";
		exec($cmd, $output, $value);
		if (!is_array($output)) // returns value 1 on empty
		{
			XLogError("Automation::RemoveCrontab list current crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			if (!$this->unlock())
				XLogError("Automation::RemoveCrontab failed to unlock cleanly");
			return false;
		}
		//------------------
		$entryfound = false;
		$newTabs = array();
		foreach($output as $entry)
			if (strpos($entry, $testcrontext) === false && strpos($entry, 'MAILTO=') === false)
				$newTabs[] = $entry; // don't add matching entries
			else
			{
				//XLogDebug("Automation::RemoveCrontab  match entry found '$entry' == '$testcrontext'");
				$entryfound = true;
			}
		//------------------
		if (!$entryfound)
		{
			XLogWarn("Automation::RemoveCrontab cron entry not found page: $page");
			if (!$this->unlock())
				XLogError("Automation::RemoveCrontab failed to unlock cleanly");
			return true; // no entry, done
		}
		//------------------
		if (sizeof($newTabs) == 0)
			$cmd = "$this->cron_cmd -r"; // none left, remove
		else
		{
			//------------------
			$header = 'MAILTO=""'.PHP_EOL;
			$tmpfilename = tempnam(sys_get_temp_dir(), 'dfhCronUpdate.txt');
			if (file_put_contents($tmpfilename, $header.join(PHP_EOL, $newTabs).PHP_EOL) === false)
			{
				XLogError("Automation::RemoveCrontab output temporary file $tmpfilename failed");
				if (!$this->unlock())
					XLogError("Automation::RemoveCrontab failed to unlock cleanly");
				return false;
			}
			//------------------
			$cmd = "$this->cron_cmd $tmpfilename"; // default replace
			//------------------
		}
		//------------------
		unset($output);
		exec($cmd, $output, $value); // this should replace the existing entries
		if ($value != 0)
		{
			XLogError("Automation::RemoveCrontab set/replace crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			if (!$this->unlock())
				XLogError("Automation::RemoveCrontab failed to unlock cleanly");
			return false;
		}
		//------------------
		if (!$this->unlock())
			XLogError("Automation::RemoveCrontab failed to unlock cleanly");
		//------------------
		XLogDebug("Automation::RemoveCrontab removed: $page");
		return true;
	}
	//------------------
	function hasCrontabs($pages) // takes array of page names, returns array of true/false or failure 
	{
		$testCrons = array();
		$retValue = array();
		//------------------
		if ($this->cron_cmd === false || $this->php_cmd === false)
		{
			XLogError("Automation::hasCrontabs cron/php cmds not ready");
			return false;
		}
		//------------------
		$pwd = exec('pwd', $output, $value); // this is the base PHP file's directory, ignoring require/include
		if ($value != 0)
		{
			XLogError("Automation::hasCrontabs get present working directory failed");
			return false;
		}
		//------------------
		$testStart = " cd $pwd; $this->php_cmd ";
		foreach ($pages as $page)
		{
			$retValue[] = false;
			$testCrons[] = $testStart.$page;
		}
		//------------------
		unset($output);
		//------------------
		if (!$this->lock("hasCrontabs"))
		{
			XLogError("Automation::hasCrontabs lock failed");
			return false;
		}
		//------------------
		$cmd = "$this->cron_cmd -l";
		exec($cmd, $output, $value);
		if (!is_array($output)) // returns value 1 on empty
		{
			XLogError("Automation::hasCrontabs list current crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			if (!$this->unlock())
				XLogError("Automation::hasCrontabs failed to unlock cleanly");
			return false;
		}
		//------------------
		foreach($output as $entry)
			for ($i = 0;$i < sizeof($testCrons);$i++)
				if (strpos($entry, $testCrons[$i]) !== false)
					$retValue[$i] = true;
		//------------------
		if (!$this->unlock())
			XLogError("Automation::hasCrontabs failed to unlock cleanly");
		//------------------
		return $retValue;
	}
	//------------------
	function setAutomationStates($roundActive, $statActive)
	{
		//------------------
		$curActive = $this->hasCrontabs(array(AUTOMATION_PAGE_ROUND, AUTOMATION_PAGE_STAT));
		if ($curActive === false)
		{
			XLogError("Automation::setAutomationStates curAutomationStates failed");
			return false;
		}
		//------------------
		if ($curActive[0] != $roundActive)
		{
			//------------------
			if ($roundActive && !$this->StartRoundCheckPage())
			{
				XLogError("Automation::setAutomationStates StartRoundCheckPage failed");
				return false;
			}
			if (!$roundActive && !$this->StopRoundCheckPage())
			{
				XLogError("Automation::setAutomationStates StopRoundCheckPage failed");
				return false;
			}
			//------------------
		}
		//------------------
		if ($curActive[1] != $statActive)
		{
			//------------------
			if ($statActive && !$this->StartStatCheckPage())
			{
				XLogError("Automation::setAutomationStates StartStatCheckPage failed");
				return false;
			}
			if (!$statActive && !$this->StopStatCheckPage())
			{
				XLogError("Automation::setAutomationStates StopStatCheckPage failed");
				return false;
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function getCrontabsRaw()
	{
		//------------------
		if ($this->cron_cmd === false || $this->php_cmd === false)
		{
			XLogError("Automation::getCrontabsRaw cron/php cmds not ready");
			return false;
		}
		//------------------
		if (!$this->lock("getCrontabsRaw"))
		{
			XLogError("Automation::getCrontabsRaw lock failed");
			return false;
		}
		//------------------
		$cmd = "$this->cron_cmd -l";
		exec($cmd, $output, $value);
		if (!is_array($output)) // returns value 1 on empty
		{
			XLogError("Automation::getCrontabsRaw list current crontabs failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			if (!$this->unlock())
				XLogError("Automation::getCrontabsRaw failed to unlock cleanly");
			return false;
		}
		//------------------
		if (!$this->unlock())
			XLogError("Automation::getCrontabsRaw failed to unlock cleanly");
		//------------------
		return $output;
	}
	//------------------
	function executePage($page)
	{
		//------------------
		if ($this->cron_cmd === false || $this->php_cmd === false)
		{
			XLogError("Automation::executePage cron/php cmds not ready");
			return false;
		}
		//------------------
		$pwd = exec('pwd', $output, $value); // this is the base PHP file's directory, ignoring require/include
		if ($value != 0)
		{
			XLogError("Automation::executePage get present working directory failed");
			return false;
		}
		//------------------
		unset($output);
		//------------------
		if ($this->phpcli_cmd !== false && $this->phpcli_cmd != "")
			$cmd = "cd $pwd; $this->phpcli_cmd $page";
		else
			$cmd = "cd $pwd; $this->php_cmd $page";
		//------------------
		XLogDebug("Automation::executePage page: $page");
		exec($cmd, $output, $value);
		//------------------
		return array($value, $output);
	}
	//------------------
	function getTimeZoneName() 
	{
		//------------------
		$cmd = 'date +%Z';
		$str = exec($cmd, $output, $value); 
		if ($value != 0)
		{
			XLogError("Automation::getTimeZoneName date command failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			return false;
		}
		//------------------
		return $str;
	}
	//------------------
	function getTimeZoneOffset() // returns signed fractional hours
	{
		//------------------
		$cmd = 'date +%:z';
		$str = exec($cmd, $output, $value); 
		if ($value != 0)
		{
			XLogError("Automation::getTimeZoneOffset date command failed. Cmd: $cmd, Output: ".var_export($output, true).", value: ".var_export($value, true));
			return false;
		}
		//------------------
		$parts = explode(":", $str);
		if (sizeof($parts) != 2 || !is_numeric($parts[0]) || !is_numeric($parts[1]))
		{
			XLogError("Automation::getTimeZoneOffset Invalid reply Output: ".var_export($output, true));
			return false;
		}
		//------------------
		$hours = $parts[0];
		$min = bcdiv($parts[1], "60.0", 2);
		if (bccomp($hours, "0.0", 2) == -1)
			$hours = bcsub($hours, $min, 2);
		else
			$hours = bcadd($hours, $min, 2);
		//------------------
		return (double)$hours;
	}
	//------------------
}; // class Automation
//---------------
?>
