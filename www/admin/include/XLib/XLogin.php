<?php
//-------------------------------------------------------------
/*
TODO: 
  log login-failure IP and host data HTTP_USER_AGENT 
  


*/
//-------------------------------------------------------------
define('XLOGIN_DEBUG_TESTPASSWORD', false); // verbose output of testing password, which leaks passwords and hashes
define('XLOGIN_DEBUG_PRIVS', false); // verbose HasPrivilege output
//-------------------------------------------------------------
define('XLogin_User_Name_Length', 32);
define('XLogin_User_Pass_Length', 64); // must fit hash
define('XLogin_Real_Name_Length', 64);
define('XLogin_User_Priv_Length', 32);
define('XLogin_User_TZ_Length',   64); // longest in practice looked like 31
define('XLOGIN_FAIL_BAN', 5);
define('XLOGIN_PASS_HASH_VER', "01"); //allow for later modification with support for importing old pass hashes
define('XLOGIN_SALT_LEN', 2);
//-------------------------------------------------------------
define('XDB_USER_PREFIX', 'xlogin_');
//-------------------------------------------------------------
define('XDB_USER_TABLE', 	'users');
//-------------------------------
define('XDB_USER_ID',		XDB_USER_PREFIX.'id');
define('XDB_USER_USER',		XDB_USER_PREFIX.'user');
define('XDB_USER_PASS',		XDB_USER_PREFIX.'password');
define('XDB_USER_REALNAME',	XDB_USER_PREFIX.'realname');
define('XDB_USER_PRIV',		XDB_USER_PREFIX.'priv');
define('XDB_USER_TZ',		XDB_USER_PREFIX.'tz');
define('XDB_LOCKOUT', 		XDB_USER_PREFIX.'lock');
//-------------------------------------------------------
define('XUSER_PRIV_ADMIN', "U");
//-------------------------------------------------------
$xdbUserFields = new TXDBFields(XDB_USER_TABLE);	
$xdbUserFields->Add(XDB_USER_ID, 		'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
$xdbUserFields->Add(XDB_USER_USER,		'varchar('.XLogin_User_Name_Length.') NOT NULL', XLogin_User_Name_Length);
$xdbUserFields->Add(XDB_USER_PASS,		'varchar('.XLogin_User_Pass_Length.') NOT NULL', XLogin_User_Pass_Length);
$xdbUserFields->Add(XDB_USER_REALNAME, 	'varchar('.XLogin_Real_Name_Length.')', XLogin_Real_Name_Length);
$xdbUserFields->Add(XDB_USER_PRIV, 		'varchar('.XLogin_User_Priv_Length.')', XLogin_User_Priv_Length);
$xdbUserFields->Add(XDB_USER_TZ, 		'varchar('.XLogin_User_TZ_Length.')', XLogin_User_TZ_Length);
$xdbUserFields->Add(XDB_LOCKOUT, 		"INT UNSIGNED DEFAULT '0'");
//-------------------------------------------------------------
class TXLogin
{
	var $LoggedIn = false;
	var $UserID = NULL;
	var $User = NULL;
	var $UserName = NULL;
	var $UserPriv = NULL;
	var $tz = NULL;
	//--------------------------------------------------
	function Install()
	{
		global $db, $xdbUserFields;
		//-------------------------
		if (!defined('IS_INSTALL') || !IS_INSTALL)
			die('Unauthorized access - xinstalling');
		//-------------------------
		if (!$db->Execute($xdbUserFields->scriptCreateTable()))
		{
			XLogError("XLogin::Install db Execute failed.");
			return false;
		}
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function Uninstall()
	{
		global $db, $xdbUserFields;
		//------------------------------------
		if (!defined('IS_INSTALL') || !IS_INSTALL)
			die('Unauthorized access - xinstalling');
		//-------------------------
		if (!$db->Execute($xdbUserFields->scriptDropTable()))
		{
			XLogError("XLogin::Uninstall db Execute failed.");
			return false;
		}
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function RestoreLogin($User)
	{
		global $db;
		//------------------------------------
		if (!$this->IsValidUserName($User))
		{
			XLogError("XLogin::RestoreLogin verify user name failed '$User' from ".$_SERVER['REMOTE_ADDR']);
			return false;
		}
		//------------------------------------
		$sql = "SELECT ".XDB_USER_ID.",".XDB_USER_REALNAME.",".XDB_USER_PRIV.",".XDB_USER_TZ." FROM ".XDB_USER_TABLE." WHERE ".XDB_USER_USER."='$User'";
		if ( !($qr = $db->Query($sql)) )
		{
			XLogError("XLogin::RestoreLogin db query failed");
			XLogDebug("XLogin::RestoreLogin db query failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		$this->User = NULL;
		$this->UserName = NULL;
		$this->UserPriv = NULL;
		$this->tz = NULL;
		$this->LoggedIn = false;
		//------------------------------------
		if (!list($this->UserID, $this->UserName, $this->UserPriv, $this->tz) = $qr->GetRow())
			return false;
		//------------------------------------
		$this->User = $User;
		$this->LoggedIn = true;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function RestoreLoginByID($UserID)
	{
		global $db;
		//------------------------------------
		if (!is_numeric($UserID))
		{
			XLogError("XLogin::RestoreLoginByID validate user id failed '$UserID' from ".$_SERVER['REMOTE_ADDR']);
			return false;
		}
		//------------------------------------
		$sql = "SELECT ".XDB_USER_USER.",".XDB_USER_REALNAME.",".XDB_USER_PRIV.",".XDB_USER_TZ." FROM ".XDB_USER_TABLE." WHERE ".XDB_USER_ID."='$UserID'";
		if ( !($qr = $db->Query($sql)) )
		{
			XLogError("XLogin::RestoreLoginByID db query failed");
			XLogDebug("XLogin::RestoreLoginByID db query failed.\nsql:\n$sql");
			return false;
		}
		//------------------------------------
		$this->User = NULL;
		$this->UserName = NULL;
		$this->UserPriv = NULL;
		$this->tz = NULL;
		$this->LoggedIn = false;
		//------------------------------------
		if (!list($this->User, $this->UserName, $this->UserPriv, $this->tz) = $qr->GetRow())
			return false;
		//------------------------------------
		$this->UserID = $UserID;
		$this->LoggedIn = true;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function Login($User, $Pass)
	{
		global $db;
		//------------------------------------
		$User = substr($db->sanitize($User), 0, XLogin_User_Name_Length);
		$Pass = substr($db->sanitize($Pass), 0, XLogin_User_Pass_Length);
		//------------------------------------
		$rID = $this->TestLogin($User, $Pass, true/*Sanitized*/);
		if ($rID === false)
		{
			XLogNotify("TestLogin failed");
			XLogDebug("TestLogin failed '$User':'$Pass'");			
			return false;
		}
		//------------------------------------
		if (!$UserData = $this->LoadUserInfo($rID))
		{
			XLogError("LoadUserInfo failed");
			return false;
		}
		//------------------------------------
		$this->User = $User;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function Logoff()
	{
		//------------------------------------
		$this->LoggedIn = false;
		$this->User = "";
		//------------------------------------
	}
	//--------------------------------------------------
	function GetUserList()
	{
		global $db;
		//------------------------------------
		$sql = 	"SELECT ".XDB_USER_ID.",".XDB_USER_USER.",".XDB_USER_REALNAME.",".XDB_USER_PRIV.",".XDB_LOCKOUT.",".XDB_USER_TZ." FROM ".XDB_USER_TABLE;
		//------------------------------------
		if (!$qr = $db->Query($sql))
		{
			XLogError("TestLogin - Query for User failed sql:\n $sql");
			return false;
		}
		//------------------------------------
		return $qr;
	}
	//--------------------------------------------------
	/*private*/ function TestLogin($User, $Pass, $Sanitized = false)
	{
		global $db;
		//------------------------------------
		if ($Sanitized === false)
		{
			$User = $db->sanitize($User);
			$Pass = $db->sanitize($Pass);
		}
		//------------------------------------
		if (XLOGIN_DEBUG_TESTPASSWORD)
			XLogDebug("XLogin::TestLogin $User:$Pass");
		//------------------------------------
		$sql = 	"SELECT ".XDB_USER_ID.", ".XDB_USER_PASS.", ".XDB_LOCKOUT." FROM ".XDB_USER_TABLE.
			" WHERE ".XDB_USER_USER."='$User'";
		//------------------------------------
		//XLogDebug("XLogin::TestLogin select info sql: $sql");
		//------------------------------------
		if (!$qr = $db->Query($sql))
		{
			XLogError("XLogin::TestLogin - Query for User failed sql:\n $sql");
			return false;
		}
		//------------------------------------
		while ( list($rID, $rCryptPass, $rFailCnt) = $qr->GetRow() )
		{
			//-------------------------------------
			if ($rFailCnt >= XLOGIN_FAIL_BAN)
			{
				$rFailCnt++;
				XLogDebug("XLogin::TestLogin fail count exceeded ($rFailCnt). User ($rID)'$User' is banned. Current attempt from '".$_SERVER['REMOTE_ADDR']."'");
				XLogUser("User ($rID)'$User' already banned for failed login attempts ($rFailCnt tries). Attempt from ".$_SERVER['REMOTE_ADDR']);
				XLogWarn("User ($rID)'$User' is banned for failed login attempts.");
				return false;
			}
			else
			{
				//-------------------------------------
				$hash = $this->EncryptPassword($Pass);
				if ($this->TestPassword($Pass, $rCryptPass))
				{
					//-------------------------------
					$qr->Release();
					//-------------------------------
					if ($rFailCnt > 0)
						if (!$this->ResetUserFailCount($rID))
							XLogError("TestLogin - ResetUserFailCount for ID $rID failed");
					//-------------------------------
					return $rID;
					//-------------------------------
				}
				else // login failed
				{
					//-------------------------------
					XLogDebug("TestPassword - confirm failed for user ($rID) '$User'");
					XLogUser("Password wrong for user '$User' from ".$_SERVER['REMOTE_ADDR']);
					//-------------------------------
					$rFailCnt++;
					if (!$this->SetUserFailCount($rID, $rFailCnt))
						XLogError("TestLogin - SetUserFailCount for ID $rID failed");
					if ($rFailCnt >= XLOGIN_FAIL_BAN)
					{
						XLogDebug("XLogin::TestLogin User account ($rID)'$User' has been banned for failed password attempts.");
						XLogUser("User ($rID)'$User' has been banned for failed login attempts. Attempt from ".$_SERVER['REMOTE_ADDR']);
						XLogWarn("User ($rID)'$User' has been banned for failed password attempts.");
					}
					//-------------------------------
					return false;
				} 
				//-------------------------------------
			} // if banned else
			//-------------------------------------
		} // while
		//------------------------------------
		XLogNotify("TestLogin User not found '".substr($User, 0, 45)."'");
		return false; // user not found
	}
	//--------------------------------------------------
	function HasPrivilege($tPriv) // multiple characters tested as OR
	{
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::HasPrivilege verify logged in failed");
			return false;
		}
		//------------------------------------
		for ($i = 0;$i < strlen($tPriv);$i++)
		{
			$c = $tPriv[$i];
			if (!$this->IsValidPriv($c))
			{
				XLogNotify("XLogin::HasPrivilege verify valid privilege failed '$tPriv'");
				return false;
			}
			if (strpos($this->UserPriv, $c) !== false)
			{
				if (XLOGIN_DEBUG_PRIVS)
					XLogDebug("Login::HasPrivilege priv = '".$this->UserPriv."', tested to have '$c' of '$tPriv'");
				return true;
			}
		}
		//------------------------------------
		return false;
	}
	//--------------------------------------------------
	function GetIDByName($tUser)
	{
		global $db, $xdbUserFields;
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::GetIDByName verify logged in failed");
			return false;
		}
		//------------------------------------
		if (!$this->HasPrivilege(XUSER_PRIV_ADMIN)) // only admin can check if a name is used, mostly for adding new users
		{
			XLogWarn("XLogin::GetIDByName validate privilege to test users exist failed");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidUserName($tUser))
		{
			XLogNotify("XLogin::GetIDByName verify valid user name failed '$tUser'");
			return false;
		}
		//------------------------------------
		$xdbUserFields->ClearValues();
		$xdbUserFields->SetValue(XDB_USER_ID);
		//------------------------------------
		$sql = $xdbUserFields->scriptSelect(XDB_USER_USER."='$tUser'", false /*orderby*/, 1 /*limit*/);
		//------------------------------------
		if (!$qr = $db->Query($sql))
		{
			XLogNotify("XLogin::GetIDByName scriptSelect query failed");
			return false;
		}
		//------------------------------------
		if (!$r = $qr->GetRow())
			return false; // not found
		//------------------------------------
		return $r[0];
	}
	//--------------------------------------------------
	function GetUserRealName($tUser)
	{
		global $db, $xdbUserFields;
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::GetUserRealName verify logged in failed");
			return false;
		}
		//------------------------------------
		if (!$this->HasPrivilege(XUSER_PRIV_ADMIN)) // only admin can check if a name is used, mostly for adding new users
		{
			XLogWarn("XLogin::GetUserRealName validate privilege to test users exist failed");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidUserName($tUser))
		{
			XLogNotify("XLogin::GetUserRealName verify valid user name failed '$tUser'");
			return false;
		}
		//------------------------------------
		$xdbUserFields->ClearValues();
		$xdbUserFields->SetValue(XDB_USER_REALNAME);
		//------------------------------------
		$sql = $xdbUserFields->scriptSelect(XDB_USER_USER."='$tUser'", false /*orderby*/, 1 /*limit*/);
		//------------------------------------
		if (!$qr = $db->Query($sql))
		{
			XLogNotify("XLogin::GetUserRealName scriptSelect query failed");
			return false;
		}
		//------------------------------------
		if (!$r = $qr->GetRow())
			return false; // not found
		//------------------------------------
		return $r[0];
	}
	//--------------------------------------------------
	/*private*/ function LoadUserInfo($UserID)
	{
		global $db;
		//------------------------------------
		if ( !($qr = $db->Query("SELECT ".XDB_USER_USER.",".XDB_USER_REALNAME.",".XDB_USER_PRIV.",".XDB_USER_TZ.",".XDB_LOCKOUT.
					" FROM ".XDB_USER_TABLE." WHERE ".XDB_USER_ID."='". $db->sanitize($UserID)."'")) )
			return false;
		//------------------------------------
		return $qr->GetRowArray();
	}
	//--------------------------------------------------
	// $bypasPrivCheck is for Install to create first admin user
	function AddUser($tUser, $tRealName, $tPriv = '', $tPassword = false, $tTz = false, $bypasPrivCheck = false)
	{
		global $db, $xdbUserFields;
		//------------------------------------
		if ($tPassword === false) $tPassword = $tUser;
		//------------------------------------
		if ($bypasPrivCheck !== false && !defined('IS_INSTALL'))
		{
			XLogError("XLogin::AddUser verify installing when bypassPrivCheck specified failed");
			return false;
		}
		//------------------------------------
		if ($bypasPrivCheck !== true && !$this->LoggedIn)
		{
			XLogError("XLogin::AddUser verify logged in failed");
			return false;
		}
		//------------------------------------
		if ($bypasPrivCheck !== true && !$this->HasPrivilege(XUSER_PRIV_ADMIN))
		{
			XLogError("XLogin::AddUser validate privilege to add another user failed");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidUserName($tUser))
		{
			XLogNotify("XLogin::AddUser verify valid user name failed '$tUser'");
			return false;
		}
		//------------------------------------
		if (false !== $this->GetUserRealName($tUser))
		{
			XLogNotify("XLogin::AddUser user name already taken '$tUser'");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidRealName($tRealName))
		{
			XLogNotify("XLogin::AddUser verify valid user real name failed '$tRealName'");
			return false;
		}
		//------------------------------------
		if ($tTz === false)
			$tTz = "UTC";
		//------------------------------------
		if (!$this->IsValidTimezone($tTz))
		{
			XLogNotify("XLogin::AddUser verify valid user time zone failed '$tTz'");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidPriv($tPriv))
		{
			XLogNotify("XLogin::AddUser verify valid privilege failed '$tPriv'");
			return false;
		}
		//------------------------------------
		$xdbUserFields->ClearValues();
		$xdbUserFields->SetValue(XDB_USER_USER, $tUser);
		$xdbUserFields->SetValue(XDB_USER_PASS, $this->EncryptPassword($tPassword));
		$xdbUserFields->SetValue(XDB_USER_REALNAME, $tRealName);
		$xdbUserFields->SetValue(XDB_USER_PRIV, $tPriv);
		$xdbUserFields->SetValue(XDB_USER_TZ, $tTz);
		//------------------------------------
		$sql = $xdbUserFields->scriptInsert();
		//------------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("XLogin::AddUser scriptInsert execute failed");
			return false;
		}
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function IsValidUserName($name)
	{
		//------------------------------------
		if (preg_match('/[^a-z0-9\_\-\.]/i', $name) != 0)
			return false; // only letters numbers underscore period and dash allowed
		//------------------------------------
		$l = strlen($name); 
		if ($l < 5 || $l > XLogin_User_Name_Length)
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function IsValidRealName($name)
	{
		//------------------------------------
		if (preg_match('/[^a-z0-9\_\-\. ]/i', $name) != 0)
			return false; // only letters numbers underscore period space and dash allowed
		//------------------------------------
		$l = strlen($name);
		if ($l < 4 || $l > XLogin_Real_Name_Length)
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function IsValidPassword($pass)
	{
		//------------------------------------
		if (preg_match('/[^a-z0-9\_\-\. \+\$\!\@\#\%\*\^\&]/i', $pass) != 0)
			return false; // alpha number space and  _-.+$!@#%*^&
		//------------------------------------
		$l = strlen($pass); 
		if ($l < 5 || $l > XLogin_User_Pass_Length)
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function IsValidTimezone($tz)
	{
		//------------------------------------
		if (preg_match('/[^a-z0-9 \/]/i', $tz) != 0)
			return false; // alpha number space and /
		//------------------------------------
		$l = strlen($tz); 
		if ($l < 3 || $l > XLogin_User_TZ_Length) // UTC is shortest in practice
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function IsValidPriv($priv)
	{
		//------------------------------------
		if (preg_match('/[^a-z0-9]/i', $priv) != 0)
			return false; // alpha number only
		//------------------------------------
		if (strlen($priv) > XLogin_User_Priv_Length)
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function UpdateUser($tID, $tUser, $tRealName, $tTz, $tPriv = false)
	{
		global $db, $xdbUserFields;
		//------------------------------------
		if (!is_numeric($tID))
		{
			XLogError("XLogin::UpdateUser validate id failed '$tID'");
			return false;
		}
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::UpdateUser verify logged in failed");
			return false;
		}
		//------------------------------------
		if ($this->UserID != $tID && !$this->HasPrivilege(XUSER_PRIV_ADMIN))
		{
			XLogError("XLogin::UpdateUser validate privilege to modify another user failed");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidUserName($tUser))
		{
			XLogNotify("XLogin::UpdateUser verify valid user name failed '$tUser'");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidRealName($tRealName))
		{
			XLogNotify("XLogin::UpdateUser verify valid user real name failed '$tRealName'");
			return false;
		}
		if (!$this->IsValidTimezone($tTz))
		{
			XLogNotify("XLogin::UpdateUser verify valid user time zone '$tTz'");
			return false;
		}
		//------------------------------------
		//XLogError("Updating: ID $tID user '$tUser' pass '".trim($tPass)."' real name '$tRealName'");
		$xdbUserFields->ClearValues();
		$xdbUserFields->SetValue(XDB_USER_USER, $tUser);
		$xdbUserFields->SetValue(XDB_USER_REALNAME, $tRealName);
		$xdbUserFields->SetValue(XDB_USER_TZ, $tTz);
		//------------------------------------
		if ($tPriv !== false && $this->HasPrivilege(XUSER_PRIV_ADMIN))
		{
			if (!$this->IsValidPriv($tPriv))
			{
				XLogNotify("XLogin::UpdateUser verify valid privilege failed '$tPriv'");
				return false;
			}
			$xdbUserFields->SetValue(XDB_USER_PRIV, $tPriv);
		}
		//------------------------------------
		$sql = $xdbUserFields->scriptUpdate(XDB_USER_ID."='$tID' LIMIT 1");
		//------------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("XLogin::UpdateUser execute scriptUpdate failed: \n$sql");
			return false;
		}
		//------------------------------------
		if ($this->UserID == $tID)
			if (!$this->RestoreLoginByID($this->UserID))
			{
				XLogError("XLogin::UpdateUser RestoreLoginByID failed");
				return false;
			}
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function SetUserPassword($tID, $tUser, $oldPassword, $newPassword)
	{
		global $db, $xdbUserFields;
		//------------------------------------
		if (!is_numeric($tID))
		{
			XLogError("XLogin::SetUserPassword validate id failed '$tID'");
			return false;
		}
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::SetUserPassword verify logged in failed");
			return false;
		}
		//------------------------------------
		if ($this->UserID != $tID && !$this->HasPrivilege(XUSER_PRIV_ADMIN))
		{
			XLogError("XLogin::SetUserPassword validate privilege to modify another user failed");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidUserName($tUser))
		{
			XLogNotify("XLogin::SetUserPassword verify valid user name failed '$tUser'");
			return false;
		}
		//------------------------------------
		if ($oldPassword === false && (!$this->HasPrivilege(XUSER_PRIV_ADMIN) || $this->UserID == $tID))
		{
			XLogNotify("XLogin::SetUserPassword old password required but not supplied");
			return false;
		}
		//------------------------------------
		if ($oldPassword !== false && !$this->IsValidPassword($oldPassword))
		{
			XLogNotify("XLogin::SetUserPassword verify valid old user password failed '$oldPassword'");
			return false;
		}
		//------------------------------------
		if (!$this->IsValidPassword($newPassword))
		{
			XLogNotify("XLogin::SetUserPassword verify valid new user password failed '$newPassword'");
			return false;
		}
		//------------------------------------
		if ($oldPassword !== false && !$this->TestLogin($tUser, $oldPassword))
		{
			sleep(12);
			XLogWarn("XLogin::SetUserPassword verify current credentials failed");
			return false;
		}
		//------------------------------------
		//XLogError("Updating: ID $tID user '$tUser' pass '".trim($tPass)."' real name '$tRealName'");
		$xdbUserFields->ClearValues();
		$xdbUserFields->SetValue(XDB_USER_PASS, $this->EncryptPassword($newPassword));
		//------------------------------------
		$sql = $xdbUserFields->scriptUpdate(XDB_USER_ID."='$tID' AND ".XDB_USER_USER."='$tUser' LIMIT 1");
		//------------------------------------
		if (!$db->Execute($sql))
		{
			XLogError("XLogin::SetUserPassword execute failed: \n$sql");
			return false;
		}
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function DeleteUser($tID)
	{
		global $db;
		//------------------------------------
		if (!is_numeric($tID))
		{
			XLogError("XLogin::DeleteUser validate id failed '$tID'");
			return false;
		}
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::DeleteUser verify logged in failed");
			return false;
		}
		//------------------------------------
		if ($this->UserID == $tID)
		{
			XLogWarn("XLogin::DeleteUser you cannot delete yourself");
			return false;
		}
		//------------------------------------
		if (!$this->HasPrivilege(XUSER_PRIV_ADMIN))
		{
			XLogWarn("XLogin::DeleteUser insufficient privileges");
			return false;
		}
		//------------------------------------
		if (!$db->Execute("DELETE FROM ".XDB_USER_TABLE." WHERE ".XDB_USER_ID."='$tID' LIMIT 1") )
		{
			XLogError("XLogin::DeleteUser db execute failed");
			return false;
		}
		//------------------------------------
		// Removed. Never reset auto_inc so that new users always get unique ID
		// sets auto inc to highest index +1 (does not set to 1)
		//if (!$db->Execute("ALTER TABLE ".XDB_USER_TABLE." AUTO_INCREMENT = 1"))
		//	return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function EncryptPassword($Plain, $Salt = NULL, $Ver = XLOGIN_PASS_HASH_VER)
	{
		if ($Salt === NULL)
			$Salt = substr(md5(uniqid(XRand(), true)), 0, XLOGIN_SALT_LEN);
		return XLOGIN_PASS_HASH_VER.$Salt.md5($Plain.$Salt);
	}
	//--------------------------------------------------
	function TestPassword($Plain, $Crypt)
	{
		
		if ($Plain == '') return false;
		$Ver = substr($Crypt, 0, 2);
		$Salt = substr($Crypt, 2, XLOGIN_SALT_LEN);
		$Hash = substr($Crypt, 2 + XLOGIN_SALT_LEN);// to end
		if (XLOGIN_DEBUG_TESTPASSWORD)
			XLogDebug("XLogin::TestPassword plain '$Plain' ver $Ver Salt: $Salt Hash: $Hash PassCrypt: $Crypt TestCrypt: ".$this->EncryptPassword($Plain, $Salt, $Ver));
		if ($this->EncryptPassword($Plain, $Salt, $Ver) !== $Crypt)
		{
			XLogDebug("XLogin::TestPassword no match");
			return false;
		}
		if (XLOGIN_DEBUG_TESTPASSWORD)
			XLogDebug("XLogin::TestPassword matched");
		return true;
	}
	//--------------------------------------------------
	function GetUserFailCount($uID)
	{
		global $db;
		//------------------------------------
		$sql = "SELECT ".XDB_LOCKOUT." FROM ".XDB_USER_TABLE." WHERE ".XDB_USER_ID."='$uID' LIMIT 1";
		//------------------------------------
		if (!$qr = $db->Query($sql))
			return false;
		//------------------------------------
		if (!$r = $qr->GetRow())
			return false;
		//------------------------------------
		return $r[0];
	}
	//--------------------------------------------------
	function SetUserFailCount($uID, $Cnt)
	{
		global $db;
		//------------------------------------
		$sql = "UPDATE ".XDB_USER_TABLE." SET ".XDB_LOCKOUT."='$Cnt' WHERE ".XDB_USER_ID."='$uID' LIMIT 1";
		XLogNotify("SetUserFailCount user failed login count: $Cnt");
		//------------------------------------
		if (!$db->Execute($sql))
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function ResetUserFailCount($uID)
	{
		return $this->SetUserFailCount($uID, 0);
	}
	//--------------------------------------------------
	function IncrementUserFailCount($uID)
	{
		global $db;
		//------------------------------------
		$sql = "UPDATE ".XDB_USER_TABLE." SET ".XDB_LOCKOUT."=".XDB_LOCKOUT." + 1 WHERE ".XDB_USER_ID."='$uID' LIMIT 1";
		//------------------------------------
		if (!$db->Execute($sql))
			return false;
		//------------------------------------
		return true;
	}
	//--------------------------------------------------
	function getLocalTime($dt)
	{
		//------------------------------------
		if (!is_object($dt) && get_class($dt) !== "DateTime")
		{
			XLogError("XLogin::getLocalTime verify DateTime object to convert failed");
			return false;
		}
		//------------------------------------
		if (!$this->LoggedIn)
		{
			XLogError("XLogin::getLocalTime verify logged in failed");
			return false;
		}
		//------------------------------------
		if (!is_null($this->tz) && is_string($this->tz) && $this->tz !== "")
			if ($dt->setTimeZone(new DateTimeZone($this->tz)) === false)
			{
				XLogWarn("XLogin::getLocalTime setTimeZone failed for dt: ".XVarDump($dt).", tz: ".XVarDump($this->tz));
				return false;
			}
		//------------------------------------
		return $dt;
	}
	//--------------------------------------------------
	function getLocalTimeString($strUTC, $outFormat = 'c', $strInTimeZone = 'UTC')
	{
		//------------------------------------
		if (!is_string($strUTC))
		{
			XLogError("XLogin::getLocalTimeString verify UTC string failed: ".XVarDump($strUTC));
			return false;
		}
		//------------------------------------
		try
		{
			$dt = new DateTime($strUTC, new DateTimeZone($strInTimeZone));
		}
		catch (Exception $e)
		{
			$dt = false;
		}
		//------------------------------------
		if ($dt === false)
		{
			XLogError("XLogin::getLocalTimeString convert string to DateTime failed: $strUTC");
			return false;
		}
		//------------------------------------
		$dt = $this->getLocalTime($dt);
		if ($dt === false)
		{
			XLogError("XLogin::getLocalTimeString getLocalTime failed");
			return false;
		}
		//------------------------------------
		$outStr = $dt->format($outFormat); 
		//------------------------------------
		if ($outStr === false)
		{
			XLogError("XLogin::getLocalTimeString format DateTime failed. out format: $outFormat");
			return false;
		}
		//------------------------------------
		return $outStr;
	}
	//--------------------------------------------------
} // class XLogin
//-------------------------------------------------------------
?>
