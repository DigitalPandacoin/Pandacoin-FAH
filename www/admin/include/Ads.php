<?php
/*
 *	www/include/Ads.php
 * 
 * 
* 
*/
//---------------
define('AD_MODE_NONE', 			0);
define('AD_MODE_SHOW_ALWAYS', 	1);
define('AD_MODE_SHOW_TITLE', 	2);
define('AD_MODE_SHOW_VALUE', 	4);
define('AD_MODE_SHOW_TEXT', 	8);
define('AD_MODE_SHOW_IMAGE', 	16);
define('AD_MODE_LINK_TITLE', 	32);
define('AD_MODE_LINK_TEXT', 	64);
define('AD_MODE_LINK_IMAGE', 	128);
//---------------
class Ad
{
	var $id = -1;
	var $mode = AD_MODE_NONE;
	var $text = "";
	var $link = "";
	var $image = "";
	var $style = "";
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
		$this->id   	= $row[DB_ADS_ID];
		$this->mode 	= (isset($row[DB_ADS_MODE]) ? $row[DB_ADS_MODE] : AD_MODE_NONE);
		$this->text 	= (isset($row[DB_ADS_TEXT]) ? $row[DB_ADS_TEXT] : "");
		$this->link 	= (isset($row[DB_ADS_LINK]) ? $row[DB_ADS_LINK] : "");
		$this->image 	= (isset($row[DB_ADS_IMAGE]) ? $row[DB_ADS_IMAGE] : "");
		$this->style 	= (isset($row[DB_ADS_STYLE]) ? $row[DB_ADS_STYLE] : "");
		//------------------
	}
	//------------------
	function setMaxSizes()
	{
		global $dbAds;
		//------------------
		$this->id		= -1;
		$this->mode		= $dbAds->GetMaxSize(DB_ADS_MODE);
		$this->text		= $dbAds->GetMaxSize(DB_ADS_TEXT);
		$this->link		= $dbAds->GetMaxSize(DB_ADS_LINK);
		$this->image	= $dbAds->GetMaxSize(DB_ADS_IMAGE);
		$this->style	= $dbAds->GetMaxSize(DB_ADS_STYLE);
		//------------------
	}
	//------------------
	function Update()
	{
		global $db, $dbAds;
		//---------------------------------
		$dbAds->ClearValues();
		//---------------------------------
		$dbAds->SetValue(DB_ADS_MODE,		$this->mode);
		$dbAds->SetValue(DB_ADS_TEXT,		$this->text);
		$dbAds->SetValue(DB_ADS_LINK,		$this->link);
		$dbAds->SetValue(DB_ADS_IMAGE,		$this->image);
		$dbAds->SetValue(DB_ADS_STYLE,		$this->style);
		//---------------------------------
		$sql = $dbAds->scriptUpdate(DB_ADS_ID."=".$this->id);
		if (!$db->Execute($sql))
		{
			XLogError("Ad::Update - db Execute scriptUpdate failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		return true;
	}
	//------------------
} // class Ad
//---------------
class Ads
{
	//------------------
	var $ads = array();
	var $isLoaded = false;
	//------------------
	function Install()
	{
		global $db, $dbAds;
		//------------------------------------
		$sql = $dbAds->scriptCreateTable();
		if (!$db->Execute($sql))
		{
			XLogError("Ads::Install db Execute create table failed.\nsql: $sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Uninstall()
	{
		global $db, $dbAds;
		//------------------------------------
		$sql = $dbAds->scriptDropTable();
		if (!$db->Execute($sql))
		{
			XLogError("Ads::Uninstall db Execute drop table failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//------------------
	function Import($oldTableVer, $oldTableName)
	{
		global $db, $dbAds;
		//------------------------------------
		switch ($oldTableVer)
		{
			case 0: // fall through
			case $dbAds->tableVersion: // same version, just do a copy
				//---------------
				$sql = "INSERT INTO $dbAds->tableName SELECT * FROM  $oldTableName";
				//---------------
				if (!$db->Execute($sql))
				{
					XLogError("Ads::Import db Execute table import failed.\nsql:\n$sql");
					return false;
				}
				//---------------
				break;
			default:
				XLogError("Ads::Import import from ver $oldTableVer not supported");
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
	function deleteAd($idx)
	{
		global $db, $dbAds;
		//---------------------------------
		$sql = $dbAds->scriptDelete(DB_ADS_ID."=".$idx);
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Ads::deleteAd - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->isLoaded = false;
		if ($this->loadAds() === false)
		{
			XLogError("Ads::deleteAd - loadAds failed.");
			return false;
		}
		//---------------------------------
		return true;
	}
	//---------------------------------	
	function Clear()
	{
		global $db, $dbAds;
		//---------------------------------
		$sql = $dbAds->scriptDelete();
		//---------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("Ads::Clear - db Execute failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->ads = array();
		//---------------------------------
		$this->isLoaded = true;
		//------------------
		return true;
	}
	//---------------------------------	
	function loadAdRaw($idx)
	{
		global $db, $dbAds;
		//------------------
		$dbAds->SetValues();
		//------------------
		$sql = $dbAds->scriptSelect(DB_ADS_ID."=".$idx, false /*orderby*/, 1 /*limit*/);
		//------------------
		$qr = $db->Query($sql);
		if ($qr === false)
		{
			XLogError("Ads::loadAdRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadAd($idx)
	{
		//------------------
		if (!is_numeric($idx))
		{
			XLogError("Ads::loadAd - validate index failed");
			return false;
		}
		//------------------
		$qr = $this->loadAdRaw($idx);
		//------------------
		if ($qr === false)
		{
			XLogError("Ads::loadAd - loadAdRaw failed");
			return false;
		}
		//------------------
		$s = $qr->GetRowArray();
		//------------------
		if ($s === false)
		{
			XLogWarn("Ads::loadAd - index $idx not found.");
			return false;
		}
		//------------------
		return new Ad($s);
	}
	//------------------
	function getAd($idx)
	{
		//---------------------------------
		if (!is_numeric($idx))
		{
			XLogError("Ads::getAd - validate index failed");
			return false;
		}
		//------------------
		if ($this->isLoaded)
			foreach ($this->ads as $c)
				if ($c->id == $idx)
					return $c;
		//---------------------------------
		return $this->loadAd($idx);
	}
	//------------------
	function loadAdsRaw()
	{
		global $db, $dbAds;
		//------------------
		$dbAds->SetValues();
		$sql = $dbAds->scriptSelect(false /*where*/, DB_ADS_ID /*orderby*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Ads::loadAdsRaw - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		return $qr;
	}
	//------------------
	function loadAds()
	{
		$this->ads = array();
		//------------------
		$qr = $this->loadAdsRaw();
		//------------------
		if ($qr === false)
		{
			XLogError("Ads::loadAds - loadAdsRaw failed");
			return false;
		}
		//------------------
		while ($a = $qr->GetRowArray())
			$this->ads[] = new Ad($a);
		//------------------
		$this->isLoaded = true;
		//------------------
		return $this->ads;
	}
	//------------------
	function getAds()
	{
		//---------------------------------
		if ($this->isLoaded)
			return $this->ads;
		//---------------------------------
		return $this->loadAds();
	}
	//------------------
	function addAd()
	{
		global $db, $dbAds;
		//------------------
		$dbAds->ClearValues();
		//------------------
		$sql = $dbAds->scriptInsert();
		//------------------
		if (!$db->Execute($sql))
		{
			XLogError("Ads::addAd - db Execute scriptInsert failed.\nsql: $sql");
			return false;
		}
		//---------------------------------
		$this->ads = array();
		$this->isLoaded = false; // list modified, set to reload
		//------------------
		$dbAds->ClearValues();
		$dbAds->SetValue(DB_ADS_ID);
		//------------------
		$sql = $dbAds->scriptSelect(false/*$where*/, DB_ADS_ID /*orderby*/, 1 /*limit*/);
		//------------------
		if (!($qr = $db->Query($sql)))
		{
			XLogError("Ads::addAd - db Query failed.\nsql: $sql");
			return false;
		}
		//------------------
		$row = $qr->GetRowArray();
		//------------------
		if ($row === false)
		{
			XLogWarn("Ads::addAd - new payout not found.");
			return false;
		}
		//------------------
		return (int)$row[DB_ADS_ID];
	}
	//------------------
} // class Ads
//---------------
?>
