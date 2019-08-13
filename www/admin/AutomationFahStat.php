<?php
//-------------------------------------------------------------
/*
*	AutomationFahStats.php
*
* This page shouldn't be accessible to the web.
* This script should be executed before each round, 
* no more than once an hour.
* 
* 
*/
//-------------------------------------------------------------
define('TEST_FAH', false);
//-------------------------------------------------------------
define('POLL_FAH_STATS_PAGE', 'http://fah-web.stanford.edu/daily_user_summary.txt.bz2');
define('TEST_POLL_FAH_STATS_PAGE', './daily_user_summary.txt.bz2');
define('POLL_FAH_STATS_PAGE_CMP_FILE_EXT', '.txt.bz2');
define('POLL_FAH_STATS_PAGE_FILE_EXT', '.txt');
//---------------------------
define('POLL_FAH_ARCHIVE_FOLDER', './log/archive/');
//---------------------------
define('CFG_LAST_FAH_POLL_STAT', 'last-fah-poll-stat');
//---------------------------
require('./include/Init.php');
//---------------------------
XLogNotify("AutomationFahStats.php activated");
//---------------------------
class AutomationFahStats
{
	var $Config = false;
	var $Round = false;
	var $foundWorkers = array();
	var $automationStopped = false;
	var $Automation = false;
	//---------------------------
	function Init()
	{
		//------------------ 
		$this->Config = new Config() or die("Create object failed");
		$this->Automation = new Automation() or die("Create object failed");
		//------------------
		return true;
	}
	//---------------------------
	function findActiveRound()
	{
		//------------------
		$Rounds = new Rounds() or die("Create object failed");
		$roundList = $Rounds->getRounds();
		if ($roundList === false)
		{
			XLogError("AutomationFahStats::findActiveRound Rounds failed to getRounds");
			return false;
		}
		//------------------
		foreach ($roundList as $round)
			if ($round->state == ROUND_STATE_FAHSTATS)
			{
				$this->Round = $round;
				return true;
			}
		//------------------
		return false; // none found ready for Fah Stats
	}
	//---------------------------
	function checkRateLimit()
	{
		//------------------
		$lastPoll = $this->Config->Get(CFG_LAST_FAH_POLL_STAT);
		if ($lastPoll === false || $lastPoll === "")
		{
			 XLogWarn("AutomationFahStats::checkRateLimit No Rate limit info");
			 return true;
		}
		//------------------
		$d = XDateTimeDiff($lastPoll, false/*now*/, false/*UTC*/, 'n'/*minutes*/);
		//------------------
		if ($d === false)
		{
			 XLogError("AutomationFahStats::checkRateLimit XDateTimeDiff failed. lastPoll: '$lastPoll'");
			 return false;
		}
		//------------------
		if ($d < 60.0) // maximum rate, once every 60 min / 1 hour
		{
			 XLogWarn("AutomationFahStats::checkRateLimit rate limitted. minutes since last fah poll: $d");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function updateRateLimit()
	{
		//------------------
		XLogNotify("AutomationFahStats::updateRateLimit");
		$dtNow = new DateTime('now', new DateTimeZone('UTC'));
		if (!$this->Config->Set(CFG_LAST_FAH_POLL_STAT, $dtNow->format(MYSQL_DATETIME_FORMAT)))
		{
			 XLogError("AutomationFahStats::updateRateLimit Config Set Last Fah Poll Stat failed");
			 return true;
		}
		//------------------
		return true;
	}
	//---------------------------
	function startAutomation()
	{
		//------------------
		XLogDebug("AutomationFahStats::startAutomation");
		//------------------
		if (!$this->Automation->StartFahStatsPage())
		{
			 XLogError("AutomationFahStats::startAutomation Automation StartFahStatsPage failed");
			 return true;
		}
		//------------------
		return true;
	}
	//---------------------------
	function stopAutomation()
	{
		//------------------
		XLogDebug("AutomationFahStats::startAutomation");
		//------------------
		if (!$this->Automation->StopFahStatsPage())
		{
			 XLogError("AutomationFahStats::stopAutomation Automation StopFahStatsPage failed");
			 return true;
		}
		//------------------
		$this->automationStopped = true;
		//------------------
		return true;
	}
	//---------------------------
	function Main()
	{
		//------------------
		if (!$this->Init())
		{
			XLogError("AutomationFahStats::Main Init failed");
			return false;
		}
		//------------------
		if (!$this->findActiveRound())
		{
			XLogWarn("AutomationFahStats::Main findActiveRound didn't succeed");
			return false;
		}
		//------------------
		if (!$this->checkRateLimit())
		{
			XLogWarn("AutomationFahStats::Main checkRateLimit returned false");
			return false;
		}
		//------------------
		if (!$this->stopAutomation())
		{
			XLogError("AutomationFahStats::Main stopAutomation failed");
			return false;
		}
		//------------------
		if (!$this->Poll())
		{
			XLogError("AutomationFahStats::Main Poll failed, restarting automation");
			if (!$this->startAutomation())
			{
				XLogError("AutomationFahStats::Main startAutomation failed");
				return false;
			}
			return false;
		}
		//------------------
		if (!$this->addNewWorkers())
		{
			XLogError("AutomationFahStats::Main addNewWorkers failed");
			return false;
		}
		//------------------
		if (!$this->updateRound())
		{
			XLogError("AutomationFahStats::Main updateRound failed");
			return false;
		}
		//------------------
		if (!$this->Automation->UpdateLastFahStats())
		{
			XLogError("AutomationFahStats::Main Automation UpdateLastFahStats failed");
			return false;
		}
		//---------------------------
		return true;
	}
	//---------------------------
	function Poll()
	{
		global $XLogInstance;
		//------------------
		if ($this->Automation->bzip2_cmd === false || $this->Automation->bzip2_cmd === "")
		{
			XLogError("AutomationFahStats::Poll Automation bzip2_cmd not ready");
			return false;
		}
		//------------------
		$tempFileName = $this->getTempFileName();
		if ($tempFileName === false)
		{
			XLogError("AutomationFahStats::Poll getTempFileName failed");
			return false;
		}
		//------------------
		$now = new DateTime('now',  ($XLogInstance->TimeZone !== false ? $XLogInstance->TimeZone : new DateTimeZone('UTC')));
		$cmpFileName = $tempFileName.POLL_FAH_STATS_PAGE_CMP_FILE_EXT;
		$fullFileName = $tempFileName.POLL_FAH_STATS_PAGE_FILE_EXT;
		$outFileNameBase = POLL_FAH_ARCHIVE_FOLDER."fah_stats_round_".$this->Round->id."_team_".$this->Round->teamId."_".$now->format("m-d-Y_H-i");
		//------------------
		$outFileName = $outFileNameBase.POLL_FAH_STATS_PAGE_FILE_EXT;
		$t = 0;
		while (file_exists($outFileName))
		{
			$outFileName = $outFileNameBase."_".$t.POLL_FAH_STATS_PAGE_FILE_EXT;
			$t++;
		}
		//------------------
		$supportWget = ($this->Automation->wget_cmd === false || $this->Automation->wget_cmd === "" ? false : true);
		//------------------
		if (TEST_FAH != false)
		{
			$supportWget = false; // override
			$dataSource = TEST_POLL_FAH_STATS_PAGE;
		}
		else $dataSource = POLL_FAH_STATS_PAGE;
		//------------------
		XLogNotify("AutomationFahStats::Poll downloading stats, supports wget: ".XVarDump($supportWget).", cmp filename: $cmpFileName, full filename: $fullFileName, out filename: $outFileName");
		//------------------
		if ($supportWget)
		{
			if (!$this->downloadStatsWget($dataSource, $cmpFileName))
			{
				XLogError("AutomationFahStats::Poll downloadStatsWget failed");
				if (file_exists($cmpFileName))
					unlink($cmpFileName);
				return false;
			}
		}
		else
		{
			if (!$this->downloadStatsPHP($dataSource, $cmpFileName))
			{
				XLogError("AutomationFahStats::Poll downloadStatsPHP failed");
				if (file_exists($cmpFileName))
					unlink($cmpFileName);
				return false;
			}
		}
		//------------------
		XLogNotify("AutomationFahStats::Poll Download Done");
		if (!$this->updateRateLimit())
		{
			XLogError("AutomationFahStats::Poll updateRateLimit failed");
			return false;
		}
		//------------------
		if (!$this->extractBz2File($cmpFileName))
		{
			XLogError("AutomationFahStats::Poll extractBz2File failed");
			if (file_exists($cmpFileName))
				unlink($cmpFileName);
			return false;
		}
		//------------------
		if (file_exists($cmpFileName)) // extraction should have already delted this
			unlink($cmpFileName);
		//------------------
		if (!file_exists($fullFileName))
		{
			XLogError("AutomationFahStats::Poll verify extracted file exists failed. filename: $fullFileName");
			return false;
		}
		//------------------
		if (!$this->filterTeamStatsFile($fullFileName, $outFileName)) // saves gzip compressed output file
		{
			XLogError("AutomationFahStats::Poll filterTeamStatsFile failed.");
			return false;
		}
		//------------------
		XLogNotify("AutomationFahStats::Poll Done");
		return true;
	}
	//---------------------------
	function getTempFileName($appendExtention = "")
	{
		//------------------
		$tempDir = sys_get_temp_dir(); // shouldn't fail
		if ($tempDir === false || $tempDir == "")
		{
			XLogError("AutomationFahStats::getTempFileName validate sys_get_temp_dir failed, returned: ".XVarDump($tempDir));
			return false;
		}
		//------------------
		$tempFileName = tempnam($tempDir, "dah_fah_stat_");
		if ($tempFileName === false || $tempFileName == "")
		{
			XLogError("AutomationFahStats::getTempFileName validate tempnam failed, returned: ".XVarDump($tempFileName));
			return false;
		}
		$tempFileName .= $appendExtention;
		//------------------
		return $tempFileName;
	}
	//---------------------------
	function downloadStatsWget($dataSource, $outputFileName)
	{
		//------------------
		$cmd = $this->Automation->wget_cmd." -nv -t 3 -O '$outputFileName' ".$dataSource; // -nv no verbose, -t 3 retries, -O output file
		$retValue = exec($cmd, $output, $value); 
		if ($value != 0)
		{
			XLogError("AutomationFahStats::downloadStatsWget exec wget failed. value: $value, cmd: '$cmd', output: ".XVarDump($output).", retValue: ".XVarDump($retValue));
			return false;
		}
		//------------------
		if (!file_exists($outputFileName))
		{
			XLogError("AutomationFahStats::downloadStatsWget validate output file exists after download failed. output file name: '$outputFileName'");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function downloadStatsPHP($dataSource, $outputFileName)
	{
		//------------------
		$hout = fopen($outputFileName, 'wb');
		if ($hout === false)
		{
			XLogError("AutomationFahStats::downloadStatsPHP fopen failed to open output file: $outputFileName");
			return false;
		}
		//------------------
		$hin = fopen($dataSource, 'rb');
		if ($hin === false)
		{
			XLogError("AutomationFahStats::downloadStatsPHP fopen failed to open remote page: $dataSource");
			return false;
		}
		//------------------
		$readFailed = false;
		$writeFailed = false;
		//------------------
		while (!feof($hin) && !$readFailed && !$writeFailed) 
		{
			$data = fread($hin,  8192);
			if ($data === false)
				$readFailed = true;
			else if (false === fwrite($hout, $data))
				$writeFailed = true;
		}
		//------------------
		if (!fclose($hin))
			XLogWarn("AutomationFahStats::downloadStatsPHP fclose remote handle failed");
		//------------------
		if (!fclose($hout))
		{
			XLogError("AutomationFahStats::downloadStatsPHP fclose local file handle failed");
			return false;
		}
		//------------------
		if ($readFailed || $writeFailed)
		{
			XLogError("AutomationFahStats::downloadStatsPHP read/write failed. read: ".XVarDump($readFailed).", write: ".XVarDump($writeFailed).", output file name: $outputFileName");
			return false;
		}
		//------------------
		if (!file_exists($outputFileName))
		{
			XLogError("AutomationFahStats::downloadStatsPHP validate output file exists after download failed. output file name: '$outputFileName'");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function extractBz2File($inFile)
	{
		//------------------
		XLogNotify("AutomationFahStats::extractBz2File");
		$cmd = $this->Automation->bzip2_cmd." -d -f '$inFile'"; // -d decompress, -f force overwrite existing output file
		$retValue = exec($cmd, $output, $value); 
		if ($value != 0)
		{
			XLogError("AutomationFahStats::extractBz2File exec bzip2 failed. value: $value, cmd: '$cmd', output: ".XVarDump($output).", retvalue: ".XVarDump($retValue));
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function compressBz2File($inFile)
	{
		//------------------
		XLogNotify("AutomationFahStats::compressBz2File");
		$cmd = $this->Automation->bzip2_cmd." --best -f '$inFile'"; // -f force overwrite existing output file
		$retValue = exec($cmd, $output, $value); 
		if ($value != 0)
		{
			XLogError("AutomationFahStats::compressBz2File exec bzip2 failed. value: $value, cmd: '$cmd', output: ".XVarDump($output).", retvalue: ".XVarDump($retValue));
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function filterTeamStatsFile($inFileName, $outFileName)
	{
		//------------------
		XLogNotify("AutomationFahStats::filterTeamStatsFile");
		$outFileName .= ".gz";
		$hout = gzopen($outFileName, 'w9'); // write only new binary file created/truncated, max compression
		if ($hout === false)
		{
			XLogError("AutomationFahStats::filterTeamStatsFile gzopen failed to open output file: $outFileName");
			return false;
		}
		//------------------
		$hin = fopen($inFileName, 'r');
		if ($hin === false)
		{
			XLogError("AutomationFahStats::filterTeamStatsFile fopen failed to open input file: $inFileName");
			return false;
		}
		//------------------
		$readFailed = false;
		$writeFailed = false;
		//------------------
		$line = "Filtered by team #".$this->Round->teamId."\n";
		if (false === gzwrite($hout, $line))
			$writeFailed = true;
		//------------------
		if (!feof($hin) && !$writeFailed) // copy first line (time stamp)
		{
			$line = fgets($hin);
			if ($line === false)
				$readFailed = true;
			else if (false === gzwrite($hout, $line))
				$writeFailed = true;
		}
		//------------------
		$linenum = 0;
		while (!feof($hin) && !$readFailed && !$writeFailed) 
		{
			$line = fgets($hin);
			if ($line === false)
			{
				if (!feof($hin))
				{
					XLogError("AutomationFahStats::filterTeamStatsFile fgets failed");
					$readFailed = true;
				}
			}
			else
			{
				$cols = explode("\t", $line);
				
				if (sizeof($cols) != 4)
					XLogError("AutomationFahStats::filterTeamStatsFil ignoring linenum: $linenum, col count: ".sizeof($cols).", line: '$line', teamid: ".$this->Round->teamId.", cols: ".XVarDump($cols));
					
				if (sizeof($cols) == 4 && trim($cols[3]) == $this->Round->teamId)
				{
					$this->foundWorkers[] = array($cols[0], $cols[2]); // name, total
					$line = $cols[0]."\t".$cols[2]."\n";
					if (false === fwrite($hout, $line))
						$writeFailed = true;
				}
			}
			$linenum++;
		}
		//------------------
		if (!fclose($hin))
			XLogWarn("AutomationFahStats::filterTeamStatsFile fclose input file handle failed");
		//------------------
		if (!gzclose($hout))
		{
			XLogError("AutomationFahStats::filterTeamStatsFile fclose output file handle failed");
			return false;
		}
		//------------------
		if ($readFailed || $writeFailed)
		{
			XLogError("AutomationFahStats::filterTeamStatsFile read/write failed. read: ".XVarDump($readFailed).", write: ".XVarDump($writeFailed).", input filename: $inFileName, output filename: $outFileName");
			return false;
		}
		//------------------
		if (!file_exists($outFileName))
		{
			XLogError("AutomationFahStats::filterTeamStatsFile validate output file exists after download failed. output file name: '$outFileName'");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	function addNewWorkers()
	{
		//------------------
		XLogNotify("AutomationFahStats::addNewWorkers");

		if (sizeof($this->foundWorkers) == 0)
		{
			XLogWarn("AutomationFahStats::addNewWorkers no workers found. Round: ".$this->Round->id.", Team ID: ".$this->Round->teamId);
			return true;
		}
		//------------------
		$Workers = new Workers() or die("Create object failed");
		$Wallet = new Wallet() or die("Create object failed"); 
		//------------------
		$workerList = $Workers->loadWorkers();
		if ($workerList === false)
		{
			XLogError("AutomationFahStats::addNewWorkers Workers loadWorkers failed");
			return false;
		}
		//------------------
		$newWorkers = array();
		foreach ($this->foundWorkers as $foundWorker)
		{
			//------------------
			$found = false;
			//------------------
			foreach ($workerList as $worker)
				if ($foundWorker[0] == $worker->uname) // $foundWorker = array(name, total)
				{
					$found = true;
					break;
				}
			//------------------
			if (!$found)
				$newWorkers[] = $foundWorker;
			//------------------
		}
		//------------------
		XLogWarn("AutomationFahStats::addNewWorkers found ".sizeof($newWorkers)." new in ".sizeof($this->foundWorkers)." workers.");
		foreach ($newWorkers as $newWorker)
		{
			//------------------
			$uname = $newWorker[0];
			//------------------
			if ($Wallet->isValidAddress($uname) == $uname)
				$address = $uname;
			else
				$address = "";
			if (!$Workers->addWorker($uname, $address, "<Fah Stats Automation>"))
			{
				XLogError("AutomationFahStats::addNewWorkers Workers addWorker failed");
				return false;
			}
			//------------------
			XLogNotify("AutomationFahStats::addNewWorkers added new worker. uname: $uname, address: $address");
			//------------------
		}
		//------------------
		XLogDebug("AutomationFahStats::addNewWorkers done");
		return true;
	}
	//---------------------------
	function updateRound()
	{
		//------------------
		XLogNotify("AutomationFahStats::updateRound");
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$this->Round->dtFahStatsDone = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		if (!$this->Round->Update())
		{
			XLogError("AutomationFahStats::updateRound Round Update failed");
			return false;
		}
		//------------------
		return true;
	}
	//---------------------------
	
} // class AutomationFahStats
//---------------------------
$as = new AutomationFahStats() or die("Create object failed");
if (!$as->Main())
{
	XLogNotify("AutomationFahStats Main failed");
	echo "failed";
}
else
{
	XLogNotify("AutomationFahStats done successfully");
	echo "ok";
}
//---------------------------
?>
