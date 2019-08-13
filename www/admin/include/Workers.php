<?php
/*
 *	www/include/Workers.php
 * 
 * 
* 
*/
//---------------
class Worker
{
	var $id = -1;
	var $uname = "";
	var $address = "";
	var $dtCreated = "";
	var $dtUpdated = "";
	var $updatedBy = "";
	var $validAddressKnown = true; 
	var $validAddress = false;
	var $disabled = false;
	var $activity = 0;
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
		$this->id   = $row[DB_WORKER_ID];
		$this->uname = (isset($row[DB_WORKER_USER_NAME]) ? $row[DB_WORKER_USER_NAME] : "");
		$this->address = (isset($row[DB_WORKER_PAYOUT_ADDRESS]) ? $row[DB_WORKER_PAYOUT_ADDRESS] : "");
		$this->dtCreated = (isset($row[DB_WORKER_DATE_CREATED]) ? $row[DB_WORKER_DATE_CREATED] : "");
		if ($this->dtCreated != "")
			if (strtotime($this->dtCreated) === false)
				$this->dtCreated = "";
		$this->dtUpdated = (isset($row[DB_WORKER_DATE_UPDATED]) ? $row[DB_WORKER_DATE_UPDATED] : "");
		if ($this->dtUpdated != "")
			if (strtotime($this->dtUpdated) === false)
				$this->dtUpdated = "";
		$this->updatedBy 	= (isset($row[DB_WORKER_UPDATED_BY]) ? $row[DB_WORKER_UPDATED_BY] : "");
		$this->disabled 	= (isset($row[DB_WORKER_DISABLED]) 		&& $row[DB_WORKER_DISABLED] != 0 ? true : false);
		$this->activity 	= (isset($row[DB_WORKER_ACTIVITY]) ? $row[DB_WORKER_ACTIVITY] : 0);		
		//------------------
		$this->validAddressKnown = (isset($row[DB_WORKER_VALID_ADDRESS]) ? true : false);
		$this->validAddress = ($this->validAddressKnown && $row[DB_WORKER_VALID_ADDRESS] != 0 ? true : false);
		//------------------
	}
	//------------------
	function setMaxSizes()
	{
		global $dbWorkerFields;
		//------------------
		$this->id 	 = -1;
		$this->uname = $dbWorkerFields->GetMaxSize(DB_WORKER_USER_NAME);
		$this->address = $dbWorkerFields->GetMaxSize(DB_WORKER_PAYOUT_ADDRESS);
		$this->dtCreated = $dbWorkerFields->GetMaxSize(DB_WORKER_DATE_CREATED);
		$this->dtUpdated = $dbWorkerFields->GetMaxSize(DB_WORKER_DATE_UPDATED);
		$this->updatedBy = $dbWorkerFields->GetMaxSize(DB_WORKER_UPDATED_BY);
		$this->validAddress = $dbWorkerFields->GetMaxSize(DB_WORKER_VALID_ADDRESS);
		$this->disabled = $dbWorkerFields->GetMaxSize(DB_WORKER_DISABLED);
		$this->activity = $dbWorkerFields->GetMaxSize(DB_WORKER_ACTIVITY);
		//------------------
	}
	//------------------
	function Update($updatedBy = "")
	{
		global $db, $dbWorkerFields;
		//---------------------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//---------------------------------
		try
		{
			if (MYSQL_REAL_ESCAPE)
				$this->uname = $db->sanitize($this->uname);
		}
		catch (exception $e)
		{
			XLogError("Worker::Update - sanitize exception: ".XVarDump($e));
			return false;
		}
		//---------------------------------
		$dbWorkerFields->ClearValues();
		$dbWorkerFields->SetValue(DB_WORKER_USER_NAME, $this->uname);
		$dbWorkerFields->SetValue(DB_WORKER_DATE_UPDATED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbWorkerFields->SetValue(DB_WORKER_UPDATED_BY, $updatedBy);
		$dbWorkerFields->SetValue(DB_WORKER_VALID_ADDRESS, $this->validAddress);
		$dbWorkerFields->SetValue(DB_WORKER_PAYOUT_ADDRESS, $this->address);
		$dbWorkerFields->SetValue(DB_WORKER_DISABLED, $this->disabled);
		$dbWorkerFields->SetValue(DB_WORKER_ACTIVITY, $this->activity);
		//---------------------------------
		$sql = $dbWorkerFields->scriptUpdate(DB_WORKER_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Worker::Update - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
	function UpdateValidAddress()
	{
		global $db, $dbWorkerFields;
		//---------------------------------
		$dbWorkerFields->ClearValues();
		$dbWorkerFields->SetValue(DB_WORKER_VALID_ADDRESS, $this->validAddress);
		$dbWorkerFields->SetValue(DB_WORKER_PAYOUT_ADDRESS, $this->address);
		//---------------------------------
		$sql = $dbWorkerFields->scriptUpdate(DB_WORKER_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Worker::UpdateValidAddress - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
} // class Worker
//---------------
class Workers
{
	//------------------
	var $workers = array();
	var $isLoaded = false;
	var $workersSortedBy = false;
	var $workersFilter = false;
	//------------------
	function Install()
	{
		global $db, $dbWorkerFields;
		//------------------------------------
		$sql = $dbWorkerFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Workers::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbWorkerFields;
		//------------------------------------
		$sql = $dbWorkerFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Workers::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbWorkerFields;
		//------------------------------------
		XLogError("Workers::Import newVersion: ".$dbWorkerFields->tableVersion.", oldVersion: ".$oldTableVer.", oldTableName: ".$oldTableName);
		switch ($oldTableVer)
		{
			case 0: // fall through
			case 1:
				//--------------- Add new Activity field defaulted to zero
				$sql = "INSERT INTO $dbWorkerFields->tableName SELECT $oldTableName.*,0 AS ".DB_WORKER_ACTIVITY." FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Workers::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
				//---------------
			case $dbWorkerFields->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbWorkerFields->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Workers::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Workers::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function GetMaxSizes()
	{
		//------------------------------------
		$msizeWorker = new Worker();
		$msizeWorker->setMaxSizes();
		//------------------------------------
		return $msizeWorker;		
	}
	//------------------
	function deleteWorker($idx)
	{
		global $db, $dbWorkerFields;
		//---------------------------------
		$sql = $dbWorkerFields->scriptDelete(DB_WORKER_ID."=".$idx);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Workers::deleteWorker - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadWorkers() === false)
		{
			XLogError("Workers::deleteWorker - loadWorkers failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear()
	{
		global $db, $dbWorkerFields;
		//---------------------------------
		$sql = $dbWorkerFields->scriptDelete();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Workers::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->workers = array();
		//---------------------------------
		$this->isLoaded = true;
		//------------------
		return true;
	}
	//---------------------------------	
	function loadWorkerRaw($idx = false, $uname = false)
	{
		global $db, $dbWorkerFields;
		//------------------
		$dbWorkerFields->SetValues();
		//------------------
		if (($idx === false && $uname === false) || ($idx !== false && $uname !== false))
		{
			XLogError("Workers::loadWorkerRaw - you must search by idx or uname, but not both.");
			return false;
		}
		//------------------
		$where = "";
		if ($idx !== false)
			$where = DB_WORKER_ID."=".$idx;
		if ($uname !== false)
			$where .= DB_WORKER_USER_NAME."=".$uname;
		//------------------
		$sql = $dbWorkerFields->scriptSelect($where, false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Workers::loadWorkerRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadWorker($idx, $uname = false)
	{
		//------------------
		$qr = $this->loadWorkerRaw($idx, $uname);
		//------------------
		if ($qr === false)
		{
			XLogError("Workers::loadWorker - loadWorkerRaw failed");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogWarn("Workers::loadWorker - worker ($idx, $uname) not found.");
			return false;
		}
		//------------------
		return new Worker($s);
	}
	//------------------
	function getWorker($idx, $uname = false)
	{
		//---------------------------------
		if ($this->isLoaded)
			foreach ($this->workers as $w)
			{
				if ($idx !== false && $w->id == $idx)
					return $w;
				if ($uname !== false && $w->uname == $uname)
					return $w;
				else
				{
					XLogWarn("Workers::getWorker - worker ($idx, $uname) not found.");
					return false;
				}
			}
		//---------------------------------
		return $this->loadWorker($idx, $uname);
	}
	//------------------
	function loadWorkersRaw($sort = false, $filter = false)
	{
		global $db, $dbWorkerFields;
		//------------------
		if ($sort === false)
			$sort = $this->workersSortedBy;
		if ($filter === false)
			$filter = $this->workersFilter;
		//------------------
		if ($sort == "uname" || $sort == "name")
		{
			$this->workersSortedBy = "uname";
			$sort = DB_WORKER_USER_NAME;
		}
		else if ($sort == "address")
		{
			$this->workersSortedBy = "address";
			$sort = DB_WORKER_PAYOUT_ADDRESS;
		}
		else 
		{
			$this->workersSortedBy = false;
			$sort = DB_WORKER_ID;
		}
		//------------------
		if ($filter !== false && is_array($filter))
		{
			//------------------
			$strWhere = "";
			//------------------
			if (isset($filter['disabled']))
				$strWhere .= ($strWhere == "" ? "" : " AND ").($filter['disabled'] === true ? DB_WORKER_DISABLED." <> 0" : "(".DB_WORKER_DISABLED." = 0 OR ".DB_WORKER_DISABLED." IS NULL )");
			//------------------
			if (isset($filter['valid']))
			{
				$strWhere .= ($strWhere == "" ? "" : " AND ");
				if ($filter['valid'] === true)
					$strWhere .= DB_WORKER_VALID_ADDRESS." IS NOT NULL AND ".DB_WORKER_VALID_ADDRESS." <> 0 AND ".DB_WORKER_PAYOUT_ADDRESS." IS NOT NULL AND ".DB_WORKER_PAYOUT_ADDRESS." <> '' AND ".DB_WORKER_PAYOUT_ADDRESS." <> 'x'";
				else
					$strWhere .= DB_WORKER_VALID_ADDRESS." IS NULL OR ".DB_WORKER_VALID_ADDRESS."=0 OR ".DB_WORKER_PAYOUT_ADDRESS." IS NULL OR ".DB_WORKER_PAYOUT_ADDRESS."='' OR ".DB_WORKER_PAYOUT_ADDRESS."='x'";
			}
			//------------------
			$this->workersFilter = $filter;
		}
		else 
		{
			$strWhere = false;
			$this->workersFilter = false;
		}
		//------------------
		$dbWorkerFields->SetValues();
		$sql = $dbWorkerFields->scriptSelect( $strWhere /*where*/, $sort /*orderby*/);
		//XLogDebug("Workers::loadWorkersRaw sql: $sql");
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Workers::loadWorkersRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadWorkers($sort = false, $filter = false)
	{
		$this->workers = array();
		//------------------
		$qr = $this->loadWorkersRaw($sort, $filter);
		//------------------
		if ($qr === false)
		{
			XLogError("Workers::loadWorkers - loadWorkersRaw failed");
			return false;
		}
		//------------------
		while ($s = $qr->GetRowArray())
			$this->workers[] = new Worker($s);
		//------------------
		$this->isLoaded = true;
		//------------------
		return $this->workers;
	}
	//------------------
	function addWorker($uname, $address = false, $updatedBy = "", $reloadWorkers = false)
	{
		global $db, $dbWorkerFields;
		//------------------
		if ($address === false)
			$address = "";
		//------------------
		$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
		//---------------------------------
		try
		{
			if (MYSQL_REAL_ESCAPE)
			{
				$uname = $db->sanitize($uname);
				if ($address !== false)
					$address = $db->sanitize($address);
			}
		}
		catch (exception $e)
		{
			XLogError("Worker::addWorker - sanitize exception: ".XVarDump($e));
			return false;
		}
		//---------------------------------
		if (strlen($uname) > C_MAX_NAME_TEXT_LENGTH)
		{
			XLogError("Worker::addWorker - uname length exceeds max: ".XVarDump($uname));
			return false;
		}
		//---------------------------------
		if ($address !== false && strlen($address) > C_MAX_WALLET_ADDRESS_LENGTH)
		{
			XLogError("Worker::addWorker - address length exceeds max: ".XVarDump($address));
			return false;
		}
		//---------------------------------
		$dbWorkerFields->ClearValues();
		$dbWorkerFields->SetValue(DB_WORKER_USER_NAME, $uname);
		$dbWorkerFields->SetValue(DB_WORKER_PAYOUT_ADDRESS, $address);
		$dbWorkerFields->SetValue(DB_WORKER_DATE_CREATED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbWorkerFields->SetValue(DB_WORKER_DATE_UPDATED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		$dbWorkerFields->SetValue(DB_WORKER_UPDATED_BY, $updatedBy);
		//------------------
		$sql = $dbWorkerFields->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Workers::addWorker - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		//---------------------------------
		if ($reloadWorkers === true)
			if ($this->loadWorkers() === false)
			{
				XLogError("Workers::addWorker - loadWorkers failed.");
				return false;
			}
		//---------------------------------
		return true;
	}
	//------------------
	function getWorkers($sort = false, $filter = false)
	{
		//---------------------------------
		if ($this->isLoaded && $sort === $this->workersSortedBy && $filter === $this->workersFilter)
			return $this->workers;
		//---------------------------------
		return $this->loadWorkers($sort, $filter);
	}
	//------------------
	function getWorkerName($idx)
	{
		global $db, $dbWorkerFields;
		//------------------
		if ($this->isLoaded)
			foreach ($this->workers as $w)
				if ($w->id == $idx)
					return $w->uname;
		//---------------------------------
		$dbWorkerFields->ClearValues();
		$dbWorkerFields->SetValue(DB_WORKER_USER_NAME);
		$sql = $dbWorkerFields->scriptSelect(DB_WORKER_ID."=$idx", false /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Workers::getWorkerName - db Query failed.\nsql: $sql");
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
	function resetAllAddressValidations()
	{
		global $db, $dbWorkerFields;
		//------------------
		$dbWorkerFields->ClearValues();
		$dbWorkerFields->SetValue(DB_WORKER_VALID_ADDRESS, false);
		$sql = $dbWorkerFields->scriptUpdate();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Workers::resetAllAddressValidations - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		//------------------
		return true;
	}
	//------------------
	function retestInvalidAddresses($updatedBy = "", $clearInvalidAddresses = true)
	{
		//------------------
		$Wallet = new Wallet() or die ("Create object failed");
		//------------------
		$wlist = $this->loadWorkers(false/*$sort*/, false/*$filter*/);
		if ($wlist === false)
		{
			XLogError("Workers::retestInvalidAddresses - loadWorkers failed.");
			return false;
		}
		if (sizeof($wlist) == 0)
		{
			XLogNotify("Workers::retestInvalidAddresses - getWorkers returned an empty list.");
			return true;
		}
		//------------------
		foreach ($wlist as $w)
		{
			//------------------
			if (!$w->disabled && (!$w->validAddressKnown || !$w->validAddress || $w->address == ""))
			{
				//------------------
				if ($w->address != "" && $w->address != "x")
				{
					//------------------
					$address = $w->address;
					//------------------
					if ($w->uname === "0" && strlen($w->address) >= 4)
						$w->uname = $w->address;
					//------------------
				}
				else if ($w->uname != "")
					$address = $w->uname;
				else
					$address = false;
				//------------------
				if ($address !== false)
				{
					//------------------
					$isValid = $Wallet->isValidAddress($address);
					if ($isValid === false)
					{
						XLogError("Workers::retestInvalidAddresses wallet isValidAddress failed");
						return false;
					}
					//------------------
					$w->validAddress = ($isValid == $address ? true : false);
					XLogDebug("Workers::retestInvalidAddresses w-id: $w->id, w-usr: ".XVarDump($w->uname).", w-address: ".XVarDump($w->address).", address: ".XVarDump($address).", isValid: ".XVarDump($isValid).", validAddress: ".XVarDump($w->validAddress));
					if ($w->validAddress)
					{
						//------------------
						$w->address = $address; // could be changing to uname
						if (!$w->Update($updatedBy))
						{
							XLogError("Workers::retestInvalidAddresses worker failed to Update");
							return false;
						}
						//------------------
					} 
					else if (!$w->validAddressKnown || ($clearInvalidAddresses && $w->address != "x")) // not valid, clear address
					{
						//------------------
						if ($clearInvalidAddresses)
							$w->address = "x";
						if (!$w->Update($updatedBy))
						{
							XLogError("Workers::retestInvalidAddresses worker failed to Update");
							return false;
						}
						//------------------
					}
					//------------------
				}
				//------------------
			}
			//------------------
		}
		//------------------
		return true;
	}
	//------------------
	function invalidateAllUsernameAddressMismatches($updatedBy = false)
	{
		global $db, $dbWorkerFields;
		//------------------
		$dbWorkerFields->ClearValues();
		$dbWorkerFields->SetValue(DB_WORKER_VALID_ADDRESS, false);
		$dbWorkerFields->SetValue(DB_WORKER_PAYOUT_ADDRESS, "");
		if ($updatedBy !== false)
		{
			$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
			$dbWorkerFields->SetValue(DB_WORKER_UPDATED_BY, $updatedBy);
			$dbWorkerFields->SetValue(DB_WORKER_DATE_UPDATED, $nowUtc->format(MYSQL_DATETIME_FORMAT));
		}
		//---------------------------------
		$where = DB_WORKER_VALID_ADDRESS." IS NOT NULL AND ".DB_WORKER_VALID_ADDRESS."<>0 AND ".DB_WORKER_PAYOUT_ADDRESS."<>".DB_WORKER_USER_NAME;
		//---------------------------------
		$sql = $dbWorkerFields->scriptUpdate($where);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Workers::invalidateAllUsernameAddressMismatches - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		//------------------
		return true;
	}
	//------------------
} // class Workers
//---------------
?>
