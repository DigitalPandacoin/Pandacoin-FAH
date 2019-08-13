<?php
/*
 *	www/include/Contributions.php
 * 
 * 
* 
*/
//---------------
define('CONT_OUTCOME_NONE',				0);
define('CONT_OUTCOME_PAID',				1);
define('CONT_OUTCOME_NOFUNDS_WAITING',	2);
define('CONT_OUTCOME_NOFUNDS_SKIPPED',	3);
define('CONT_OUTCOME_FAILED_WAITING',	4);
define('CONT_OUTCOME_FAILED_SKIPPING',	5);
define('CONT_OUTCOME_FAILED_WONTFIX',	6);
//---------------
define('CONT_MODE_NONE', 		0);
define('CONT_MODE_FLAT', 		1);
define('CONT_MODE_PERCENT', 	2);
define('CONT_MODE_ALL', 		3);
define('CONT_MODE_EACH',		4);
//---------------
define('CONT_FLAG_NONE', 		0);
define('CONT_FLAG_REQUIRED', 	1);
//---------------
class Contribution
{
	var $id = -1;
	var $dtCreated = "";
	var $dtDone = "";
	var $outcome = CONT_OUTCOME_NONE;
	var $roundIdx = false;
	var $number = false;
	var $name = "";
	var $mode = CONT_MODE_NONE;
	var $account = false;
	var $value = 0.0;
	var $flags = CONT_FLAG_NONE;
	var $txid = false;
	var $ad = false;
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
		$this->id   		= $row[DB_CONTRIBUTION_ID];
		$this->dtCreated 	= (isset($row[DB_CONTRIBUTION_DATE_CREATED]) ? $row[DB_CONTRIBUTION_DATE_CREATED] : "");
		$this->dtDone 		= (isset($row[DB_CONTRIBUTION_DATE_DONE]) ? $row[DB_CONTRIBUTION_DATE_DONE] : "");
		$this->outcome 		= (isset($row[DB_CONTRIBUTION_OUTCOME]) ? $row[DB_CONTRIBUTION_OUTCOME] : CONT_OUTCOME_NONE);
		$this->roundIdx 	= (isset($row[DB_CONTRIBUTION_ROUND]) ? $row[DB_CONTRIBUTION_ROUND] : false);
		$this->number 		= (isset($row[DB_CONTRIBUTION_NUMBER]) ? $row[DB_CONTRIBUTION_NUMBER] : false);
		$this->name 		= (isset($row[DB_CONTRIBUTION_NAME]) ? $row[DB_CONTRIBUTION_NAME] : "");
		$this->mode 		= (isset($row[DB_CONTRIBUTION_MODE]) ? $row[DB_CONTRIBUTION_MODE] : CONT_MODE_NONE);
		$this->account 		= (isset($row[DB_CONTRIBUTION_ACCOUNT]) ? $row[DB_CONTRIBUTION_ACCOUNT] : false);
		$this->value 		= (isset($row[DB_CONTRIBUTION_VALUE]) ? $row[DB_CONTRIBUTION_VALUE] : 0.0);
		$this->flags 		= (isset($row[DB_CONTRIBUTION_FLAGS]) ? $row[DB_CONTRIBUTION_FLAGS] : CONT_FLAG_NONE);  
		$this->txid 		= (isset($row[DB_CONTRIBUTION_TXID]) ? $row[DB_CONTRIBUTION_TXID] : false);  
		$this->ad		 	= (isset($row[DB_CONTRIBUTION_AD]) ? $row[DB_CONTRIBUTION_AD] : false);
		//------------------
		if ($this->dtCreated != "")
			if (strtotime($this->dtCreated) === false)
				$this->dtCreated = "";
		//------------------
		if ($this->dtDone != "")
			if (strtotime($this->dtDone) === false)
				$this->dtDone = "";
		//------------------
	}
	//------------------
	function setMaxSizes()
	{
		global $dbContributionFields;
		//------------------
		$this->id		= -1;
		$this->dtCreated = $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_DATE_CREATED);
		$this->dtDone	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_DATE_DONE);
		$this->outcome	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_OUTCOME);
		$this->roundIdx	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_ROUND);
		$this->number	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_NUMBER);
		$this->name		= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_NAME);
		$this->mode		= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_MODE);
		$this->account	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_ACCOUNT);
		$this->value	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_VALUE);
		$this->flags	= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_FLAGS);
		$this->txid		= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_TXID);
		$this->ad		= $dbContributionFields->GetMaxSize(DB_CONTRIBUTION_AD);
		//------------------
	}
	//------------------
	function Update()
	{
		global $db, $dbContributionFields;
		//---------------------------------
		$dbContributionFields->ClearValues();
		$dbContributionFields->SetValue(DB_CONTRIBUTION_DATE_CREATED,	$this->dtCreated);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_DATE_DONE,	$this->dtDone);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_OUTCOME,	$this->outcome);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_ROUND,		$this->roundIdx);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_NUMBER,		$this->number);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_NAME,		$this->name);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_MODE,		$this->mode);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_ACCOUNT,	$this->account);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_VALUE,		$this->value);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_FLAGS,		$this->flags);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_TXID,		$this->txid);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_AD,			$this->ad);
		//---------------------------------
		$sql = $dbContributionFields->scriptUpdate(DB_CONTRIBUTION_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Contribution::Update - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
	function UpdateOutcome($outcome, $isDone = false, $txid = false)
	{
		global $db, $dbContributionFields;
		//---------------------------------
		$dbContributionFields->ClearValues();
		if ($isDone === true)
		{
			$this->txid = $txid;
			$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
			$this->dtDone = $nowUtc->format(MYSQL_DATETIME_FORMAT);
			$dbContributionFields->SetValue(DB_CONTRIBUTION_DATE_DONE, $this->dtDone);
			$dbContributionFields->SetValue(DB_CONTRIBUTION_TXID, ($this->txid === false ? "" : $this->txid));
		}
		//---------------------------------
		$this->outcome = $outcome;
		$dbContributionFields->SetValue(DB_CONTRIBUTION_OUTCOME, $this->outcome);
		//---------------------------------
		$sql = $dbContributionFields->scriptUpdate(DB_CONTRIBUTION_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Contribution::UpdateOutcome - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
} // class Contribution
//---------------
class Contributions
{
	//------------------
	var $contributions = array();
	var $isLoaded = false;
	//------------------
	function Install()
	{
		global $db, $dbContributionFields;
		//------------------------------------
		$sql = $dbContributionFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Contributions::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbContributionFields;
		//------------------------------------
		$sql = $dbContributionFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Contributions::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbContributionFields;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case 1:
			case 2:
				//---------------
				$dbContributionFields->SetValues();
				$dbContributionFields->ClearValue(DB_CONTRIBUTION_TXID);
				$dbContributionFields->ClearValue(DB_CONTRIBUTION_AD);
				//---------------
				$sql = "INSERT INTO $dbContributionFields->tableName (".$dbContributionFields->GetNameListString(true/*SkipNotSet*/).") SELECT ".$dbContributionFields->GetNameListString(true/*SkipNotSet*/)." FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Contributions::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			case $dbContributionFields->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbContributionFields->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Contributions::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Contributions::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function GetMaxSizes()
	{
		//------------------------------------
		$msize = new Contribution();
		$msizet->setMaxSizes();
		//------------------------------------
		return $msize;		
	}
	//------------------
	function deleteContribution($idx)
	{
		global $db, $dbContributionFields;
		//---------------------------------
		$sql = $dbContributionFields->scriptDelete(DB_CONTRIBUTION_ID."=".$idx);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Contributions::deleteContribution - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadContributions() === false)
		{
			XLogError("Contributions::deleteContribution - loadContributions failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear()
	{
		global $db, $dbContributionFields;
		//---------------------------------
		$sql = $dbContributionFields->scriptDelete();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Contributions::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->contributions = array();
		//---------------------------------
		$this->isLoaded = true;
		//------------------
		return true;
	}
	//---------------------------------	
	function loadContributionRaw($idx)
	{
		global $db, $dbContributionFields;
		//------------------
		$dbContributionFields->SetValues();
		//------------------
		$sql = $dbContributionFields->scriptSelect(DB_CONTRIBUTION_ID."=".$idx, false /*orderby*/, 1 /*limit*/);
		//------------------
		$qr = $db->Query($sql);
		if ($qr === false)
		{
			XLogError("Contributions::loadContributionRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadContribution($idx)
	{
		//------------------
		if (!is_numeric($idx))
		{
			XLogError("Contributions::loadContribution - validate index failed");
			return false;
		}
		//------------------
		$qr = $this->loadContributionRaw($idx);
		//------------------
		if ($qr === false)
		{
			XLogError("Contributions::loadContribution - loadContributionRaw failed");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogWarn("Contributions::loadContribution - index $idx not found.");
			return false;
		}
		//------------------
		return new Contribution($s);
	}
	//------------------
	function getContribution($idx)
	{
		//---------------------------------
		if (!is_numeric($idx))
		{
			XLogError("Contributions::getContribution - validate index failed");
			return false;
		}
		//------------------
		if ($this->isLoaded)
			foreach ($this->contributions as $c)
				if ($c->id == $idx)
					return $c;
		//---------------------------------
		return $this->loadContribution($idx);
	}
	//------------------
	function loadContributionsRaw($roundOnly = false)
	{
		global $db, $dbContributionFields;
		//------------------
		if ($roundOnly === false)
			$where = false;
		else
			$where = DB_CONTRIBUTION_ROUND."=".$roundOnly;
		//------------------
		$dbContributionFields->SetValues();
		$sql = $dbContributionFields->scriptSelect($where, DB_CONTRIBUTION_ID /*orderby*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Contributions::loadContributionsRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadContributions($roundOnly = false)
	{
		$this->contributions = array();
		//------------------
		$qr = $this->loadContributionsRaw($roundOnly);
		//------------------
		if ($qr === false)
		{
			XLogError("Contributions::loadContributions - loadContributionsRaw failed");
			return false;
		}
		//------------------
		while ($p = $qr->GetRowArray())
			$this->contributions[] = new Contribution($p);
		//------------------
		$this->isLoaded = true;
		//------------------
		return $this->contributions;
	}
	//------------------
	function getContributions()
	{
		//---------------------------------
		if ($this->isLoaded)
			return $this->contributions;
		//---------------------------------
		return $this->loadContributions();
	}
	//------------------
	function findRoundContributions($roundIdx, $number = false)
	{
		global $db, $dbContributionFields;
		//------------------
		$where = DB_CONTRIBUTION_ROUND."=$roundIdx";
		if ($number !== false)
			$where .= " AND ".DB_CONTRIBUTION_NUMBER."=$number";
		//------------------
		$dbContributionFields->SetValues();
		$sql = $dbContributionFields->scriptSelect($where, DB_CONTRIBUTION_NUMBER.",".DB_CONTRIBUTION_ID /*orderby*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Contributions::findRoundContributions - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$contributions = array();
		while ($p = $qr->GetRowArray())
			$contributions[] = new Contribution($p);
		//------------------
		return $contributions;
	}
	//------------------
	function addContribution($roundIdx, $number, $name, $mode, $account, $value, $flags)
	{
		global $db, $dbContributionFields;
		//------------------
		if (!is_numeric($roundIdx))
		{
			XLogError("Contributions::addContribution validate roundIdx is_numeric failed for '$roundIdx'");
			return false;
		}
		//------------------
		if (!is_numeric($number))
		{
			XLogError("Contributions::addContribution validate number is_numeric failed for '$number'");
			return false;
		}
		//------------------
		if (!is_numeric($mode))
		{
			XLogError("Contributions::addContribution validate mode is_numeric failed for '$mode'");
			return false;
		}
		//------------------
		if (!is_numeric($value))
		{
			XLogError("Contributions::addContribution validate value is_numeric failed for '$value'");
			return false;
		}
		//------------------
		if (!is_numeric($flags))
		{
			XLogError("Contributions::addContribution validate flags is_numeric failed for '$flags'");
			return false;
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$nowUtcString = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//------------------
		$dbContributionFields->ClearValues();
		$dbContributionFields->SetValue(DB_CONTRIBUTION_DATE_CREATED, $nowUtcString);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_ROUND, $roundIdx);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_NUMBER, $number);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_NAME, $name);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_MODE, $mode);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_ACCOUNT, $account);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_VALUE, $value);
		$dbContributionFields->SetValue(DB_CONTRIBUTION_FLAGS, $flags);
		//------------------
		$sql = $dbContributionFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Contributions::addContribution - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->contributions = array();
		$this->isLoaded = false; // list modified, set to reload
		//------------------
		$where = DB_CONTRIBUTION_DATE_CREATED."='$nowUtcString' AND ".DB_CONTRIBUTION_ROUND."=$roundIdx AND ".DB_CONTRIBUTION_NUMBER."=$number";
		//------------------
		$dbContributionFields->ClearValues();
		$dbContributionFields->SetValue(DB_CONTRIBUTION_ID);
		//------------------
		$sql = $dbContributionFields->scriptSelect($where, DB_CONTRIBUTION_ID /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Contributions::addContribution - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$row = $qr->GetRowArray();
		//------------------
		if ($row === false)
		{
			XLogWarn("Contributions::addContribution - new payout not found (created $nowUtcString round $roundIdx, number $number).");
			return false;
		}
		//------------------
		return (int)$row[DB_CONTRIBUTION_ID];
	}
	//------------------
	function deleteAllRound($ridx)
	{
		global $db, $dbContributionFields;
		//------------------
		$sql = $dbContributionFields->scriptDelete(DB_CONTRIBUTION_ROUND."=$ridx");
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Contributions::deleteAllRound - db Execute scriptDelete failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		//---------------------------------
		return true;
	}
	//------------------
} // class Contributions
//---------------
?>
