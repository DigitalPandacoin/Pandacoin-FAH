<?php
/*
 *	include/Config.php
 * 
 * 
* 
*/
//---------------
//---------------
class Config
{
	//------------------
	function Install()
	{
		global $db, $dbConfigFields;
		//------------------------------------
		$sql = $dbConfigFields->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Config::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbConfigFields;
		//------------------------------------
		$sql = $dbConfigFields->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Config::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Init()
	{
		//------------------------------------
		// set new database default entries here
		//------------------------------------
		$rounds = new Rounds() or die("Create object failed");
		if (!$rounds->Init())
			{XLogError("Config::Init rounds Init failed");return false;}
		//------------------------------------
		$wallet = new Wallet() or die("Create object failed");
		if (!$wallet->Init())
			{XLogError("Config::Init wallet Init failed");return false;}
		//------------------------------------
		$Display = new Display() or die("Create object failed");
		if (!$Display->Init())
			{XLogError("Config::Init Display Init failed");return false;}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbConfigFields;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case $dbConfigFields->tableVersion: // same version, do a manual copy to being mindful of already set form Init's
				//---------------
				$sql = "SELECT ".$dbConfigFields->GetNameListString(false/*SkipNotSet*/)." FROM $oldTableName";
				//------------------
				if (!($qr = $db->Query($sql)))
				{
					XLogError("Config::Import - db Query old values failed.\nsql: $sql");
					return false;
				}
				//------------------
				$oldValues = array();
				while ($s = $qr->GetRowArray())
					$oldValues[$s[DB_CFG_NAME]] = (isset($s[DB_CFG_VALUE]) ? $s[DB_CFG_VALUE] : "");
				//------------------
				foreach ($oldValues as $n => $v)
					if (!$this->Set($n, $v))
					{
						XLogError("Config::Import - set failed. Name: $n");
						return false;
					}
				//---------------
				break;
			default:
				XLogError("Config::Import import from ver $oldTableVer not supported");
				return false;
		} // switch ($oldTableVer)
		//------------------------------------
		return true;
	} // Import
	//------------------
	function Set($name, $value)
	{
		global $db, $dbConfigFields;
		//---------------------------------
		try
		{
			if (MYSQL_REAL_ESCAPE)
				$value = $db->sanitize($value);
		}
		catch (exception $e)
		{
			XLogError("Config::Set - sanitize exception: ".XVarDump($e));
			return false;
		}
		//---------------------------------
		$sql = "INSERT INTO ".$dbConfigFields->tableName." (".DB_CFG_NAME.",".DB_CFG_VALUE.") VALUES ('$name','$value') ";
		$sql .= "ON DUPLICATE KEY UPDATE ".DB_CFG_VALUE."='$value'";
		//---------------------------------
		//XLogDebug("config set: $sql");
		if (!$db->Execute($sql))
		{
			XLogError("Config::Set - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear($name)
	{
		global $db, $dbConfigFields;
		//---------------------------------
		$sql = "INSERT INTO ".$dbConfigFields->tableName." (".DB_CFG_NAME.",".DB_CFG_VALUE.") VALUES ('$name',null) ";
		$sql .= "ON DUPLICATE KEY UPDATE ".DB_CFG_VALUE."=null";
		//---------------------------------
		//XLogDebug("config clear: $sql");
		if (!$db->Execute($sql))
		{
			XLogError("Config::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Get($name, $default = false)
	{
		global $db, $dbConfigFields;
		//---------------------------------
		$sql = "SELECT ".DB_CFG_VALUE." FROM ".$dbConfigFields->tableName." WHERE ".DB_CFG_NAME."='$name'";
		//---------------------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Config::Get - db Query failed.\nsql: $sql");
			return $default;
		}
		//------------------
		$r = $qr->GetRow(); // returns one row, numerically indexed field array
		if (!$r || !isset($r[0]))
			return $default;
		//------------------
		return $r[0];
	}
	//---------------------------------	
	function getList($where = false, $default = false)
	{
		global $db, $dbConfigFields;
		//---------------------------------
		$dbConfigFields->SetValues();
		$sql = $dbConfigFields->scriptSelect($where, DB_CFG_NAME /*orderby*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Config::getList - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$clist = array();
		while ($s = $qr->GetRowArray())
			$clist[$s[DB_CFG_NAME]] = (isset($s[DB_CFG_VALUE]) ? $s[DB_CFG_VALUE] : $default);
		//------------------
		return $clist;
	}
	//---------------------------------	
} // class
//---------------------------------	
?>
