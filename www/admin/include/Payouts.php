<?php
/*
 *	www/include/Payouts.php
 * 
 * 
* 
*/
//---------------
class Payout
{
	var $id = -1;
	var $dtcreated = "";
	var $dtPaid = "";
	var $roundIdx = "";
	var $workerIdx = 0.0;
	var $address = 0;
	var $pay = 0.0;
	var $txid = false;
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
		$this->id   = $row[DB_PAYOUT_ID];
		$this->dtcreated = (isset($row[DB_PAYOUT_DATE_CREATED]) ? $row[DB_PAYOUT_DATE_CREATED] : "");
		$this->dtPaid = (isset($row[DB_PAYOUT_DATE_PAID]) ? $row[DB_PAYOUT_DATE_PAID] : "");
		$this->roundIdx = $row[DB_PAYOUT_ROUND];
		$this->workerIdx = $row[DB_PAYOUT_WORKER];
		$this->address = $row[DB_PAYOUT_ADDRESS];
		$this->pay = $row[DB_PAYOUT_PAY]; 
		$this->txid = (isset($row[DB_PAYOUT_TXID]) ? $row[DB_PAYOUT_TXID] : "");  
		//------------------
		if ($this->dtcreated != "")
			if (strtotime($this->dtcreated) === false)
				$this->dtcreated = "";
		if ($this->dtPaid != "")
			if (strtotime($this->dtPaid) === false)
				$this->dtPaid = "";
		//------------------
	}
	//------------------
	function setMaxSizes()
	{
		global $dbPayoutFields;
		//------------------
		$this->id 	 = -1;
		$this->dtcreated	 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_DATE_CREATED);
		$this->dtPaid	 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_DATE_PAID);
		$this->roundIdx	 		 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_ROUND);
		$this->workerIdx	 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_WORKER);
		$this->address	 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_ADDRESS);
		$this->pay	 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_PAY);
		$this->txid	 = $dbPayoutFields->GetMaxSize(DB_PAYOUT_TXID);
		//------------------
	}
	//------------------
	function Update()
	{
		global $db, $dbPayoutFields;
		//---------------------------------
		$dbPayoutFields->ClearValues();
		$dbPayoutFields->SetValue(DB_PAYOUT_DATE_CREATED,   $this->dtcreated);
		$dbPayoutFields->SetValue(DB_PAYOUT_DATE_PAID, $this->dtPaid);
		$dbPayoutFields->SetValue(DB_PAYOUT_ROUND, $this->roundIdx);
		$dbPayoutFields->SetValue(DB_PAYOUT_WORKER, $this->workerIdx);
		$dbPayoutFields->SetValue(DB_PAYOUT_ADDRESS, $this->address);
		$dbPayoutFields->SetValue(DB_PAYOUT_PAY, $this->pay);
		$dbPayoutFields->SetValue(DB_PAYOUT_TXID, $this->txid);
		//---------------------------------
		$sql = $dbPayoutFields->scriptUpdate(DB_PAYOUT_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Payout::Update - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
} // class Payout
//---------------
class Payouts
{
	//------------------
	var $payouts = array();
	var $isLoaded = false;
	//------------------
	function Install()
	{
		global $db, $dbPayoutFields;
		//------------------------------------
		$sql = $dbPayoutFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Payouts::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbPayoutFields;
		//------------------------------------
		$sql = $dbPayoutFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Payouts::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbPayoutFields;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case $dbPayoutFields->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbPayoutFields->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Payouts::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Payouts::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function GetMaxSizes()
	{
		//------------------------------------
		$msizePayout = new Payout();
		$msizePayout->setMaxSizes();
		//------------------------------------
		return $msizePayout;		
	}
	//------------------
	function deletePayout($idx)
	{
		global $db, $dbPayoutFields;
		//---------------------------------
		$sql = $dbPayoutFields->scriptDelete(DB_PAYOUT_ID."=".$idx);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Payouts::deletePayout - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadPayouts() === false)
		{
			XLogError("Payouts::deletePayout - loadPayouts failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear()
	{
		global $db, $dbPayoutFields;
		//---------------------------------
		$sql = $dbPayoutFields->scriptDelete();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Payouts::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->payouts = array();
		//---------------------------------
		$this->isLoaded = true;
		//------------------
		return true;
	}
	//---------------------------------	
	function loadPayoutRaw($idx)
	{
		global $db, $dbPayoutFields;
		//------------------
		$dbPayoutFields->SetValues();
		//------------------
		$sql = $dbPayoutFields->scriptSelect(DB_PAYOUT_ID."=".$idx, false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Payouts::loadPayoutRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadPayout($idx)
	{
		//------------------
		if (!is_numeric($idx))
		{
			XLogError("Payouts::loadPayout - validate index failed");
			return false;
		}
		//------------------
		$qr = $this->loadPayoutRaw($idx);
		//------------------
		if ($qr === false)
		{
			XLogError("Payouts::loadPayout - loadPayoutRaw failed");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogWarn("Payouts::loadPayout - index $idx not found.");
			return false;
		}
		//------------------
		return new Payout($s);
	}
	//------------------
	function getPayout($idx)
	{
		//---------------------------------
		if (!is_numeric($idx))
		{
			XLogError("Payouts::getPayout - validate index failed");
			return false;
		}
		//------------------
		if ($this->isLoaded)
			foreach ($this->payouts as $p)
				if ($p->id == $idx)
					return $p;
		//---------------------------------
		return $this->loadPayout($idx);
	}
	//------------------
	function loadPayoutsRaw()
	{
		global $db, $dbPayoutFields;
		//------------------
		$dbPayoutFields->SetValues();
		$sql = $dbPayoutFields->scriptSelect(false /*where*/, DB_PAYOUT_ID /*orderby*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Payouts::loadPayoutsRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadPayouts()
	{
		$this->payouts = array();
		//------------------
		$qr = $this->loadPayoutsRaw();
		//------------------
		if ($qr === false)
		{
			XLogError("Payouts::loadPayouts - loadPayoutsRaw failed");
			return false;
		}
		//------------------
		while ($p = $qr->GetRowArray())
			$this->payouts[] = new Payout($p);
		//------------------
		$this->isLoaded = true;
		//------------------
		return $this->payouts;
	}
	//------------------
	function findRoundPayouts($roundIdx, $orderBy = false, $includePaid = true)
	{
		global $db, $dbPayoutFields;
		//------------------
		if ($orderBy === false)
			$orderBy = DB_PAYOUT_ID;
		//------------------
		$where = DB_PAYOUT_ROUND."=$roundIdx";
		if (!$includePaid)
			$where .= " AND (".DB_PAYOUT_DATE_PAID." IS NULL OR ".DB_PAYOUT_DATE_PAID."='') AND (".DB_PAYOUT_TXID." IS NULL OR ".DB_PAYOUT_TXID."='')";
		//------------------
		$dbPayoutFields->SetValues();
		$sql = $dbPayoutFields->scriptSelect($where, $orderBy);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Payouts::findRoundPayouts - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$payouts = array();
		while ($p = $qr->GetRowArray())
			$payouts[] = new Payout($p);
		//------------------
		return $payouts;
	}
	//------------------
	function addPayout($roundIdx, $workerIdx, $address, $pay)
	{
		global $db, $dbPayoutFields;
		//------------------
		if (!is_numeric($roundIdx))
		{
			XLogError("Payouts::addPayout validate roundIdx is_numeric failed for '$roundIdx'");
			return false;
		}
		//------------------
		if (!is_numeric($workerIdx))
		{
			XLogError("Payouts::addPayout validate workerIdx is_numeric failed for '$workerIdx'");
			return false;
		}
		//------------------
		if (!is_numeric($pay))
		{
			XLogError("Payouts::addPayout validate pay is_numeric failed for '$pay'");
			return false;
		}
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		$nowUtcString = $nowUtc->format(MYSQL_DATETIME_FORMAT);
		//---------------------------------
		$dbPayoutFields->ClearValues();
		$dbPayoutFields->SetValue(DB_PAYOUT_DATE_CREATED, $nowUtcString);
		$dbPayoutFields->SetValue(DB_PAYOUT_ROUND, $roundIdx);
		$dbPayoutFields->SetValue(DB_PAYOUT_WORKER, $workerIdx);
		$dbPayoutFields->SetValue(DB_PAYOUT_ADDRESS, $address);
		$dbPayoutFields->SetValue(DB_PAYOUT_PAY, $pay);
		//------------------
		$sql = $dbPayoutFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Payouts::addPayout - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->payouts = array();
		$this->isLoaded = false; // list modified, set to reload
		//------------------
		$where = DB_PAYOUT_DATE_CREATED."='$nowUtcString' AND ".DB_PAYOUT_ROUND."=$roundIdx AND ".DB_PAYOUT_WORKER."=$workerIdx";
		//------------------
		$dbPayoutFields->ClearValues();
		$dbPayoutFields->SetValue(DB_PAYOUT_ID);
		//------------------
		$sql = $dbPayoutFields->scriptSelect($where, DB_PAYOUT_ID /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Payouts::addPayout - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$row = $qr->GetRowArray();
		//------------------
		if ($row === false)
		{
			XLogWarn("Payouts::addPayout - new payout not found (created $nowUtcString round $roundIdx, worker $workerIdx).");
			return false;
		}
		//------------------
		return (int)$row[DB_PAYOUT_ID];
	}
	//------------------
	function getPayouts()
	{
		//---------------------------------
		if ($this->isLoaded)
			return $this->payouts;
		//---------------------------------
		return $this->loadPayouts();
	}
	//------------------
	function deleteAllRound($ridx)
	{
		global $db, $dbPayoutFields;
		//------------------
		$sql = $dbPayoutFields->scriptDelete(DB_PAYOUT_ROUND."=$ridx");
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Payouts::deleteAllRound - db Execute scriptDelete failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		//---------------------------------
		return true;
	}
	//------------------
} // class Payouts
//---------------
?>
