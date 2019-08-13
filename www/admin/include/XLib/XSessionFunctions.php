<?php
//-------------------------------------------------------------
// TODO:   TestSession should validate the IP host data and expiration date
//
// Options:
//     NEED_SESSION_WRITES will prevent the session write from being closed after session start
//
//-------------------------------------------------------------
define('SESS_PREFIX', 'ss_sess_');
define('SESS_LOGGED_IN',      	SESS_PREFIX.'loggedin');
define('SESS_SECURITY_TOKEN', 	SESS_PREFIX.'sectoken');
define('SESS_USER', 		SESS_PREFIX.'user');
define('PUB_SECURIY_TOKEN', 	'st');
//-------------------------------------------------------------
function ThrowToLogin($Params = "")
{
	global $Login, $Session;
	$Login->Logoff();
	$Session->Destroy();
	header("Location: ./Login.php".($Params != "" ? '?'.$Params : ''));
	exit;
}
//-------------------------------------------------------------
function SecToken()
{
	return PUB_SECURIY_TOKEN."=".XSession(SESS_SECURITY_TOKEN);
}
//-------------------------------------------------------------
function PrintSecTokenInput()
{
	echo '<input type="hidden" name="'.PUB_SECURIY_TOKEN.'" value="'.XSession(SESS_SECURITY_TOKEN).'" />'."\r";
}
//-------------------------------------------------------------
class XSession
{
	//-------------------------------------------------------------
	function Close()
	{
		session_write_close();
	}
	//-------------------------------------------------------------
	function Destroy()
	{
		$_SESSION = array();
		session_destroy();
	}
	//-------------------------------------------------------------
	function Reset()
	{
		$old_sessid = session_id(); 
		if(!session_regenerate_id()) //get a new session id (must do this before destroying the old session)
			die("Couldn't regenerate your session id.");
		$new_sessid = session_id(); //save new session id so we can get back to it
		session_id($old_sessid);
		unset($old_sessid);
		session_destroy(); //destroy the session they got before they logged in
		session_id($new_sessid);
		session_start(); //start the new session
		if (session_id() != $new_sessid)
			return false;
		unset($new_sessid);
		return true;
	}
	//-------------------------------------------------------------
	function Start($Name)
	{
		session_name($Name) or die("Session failure");
		session_start() or die("Session failure");
		if (!isset($_SESSION[SESS_SECURITY_TOKEN]))
		{
			if (!isset($_SESSION)) $_SESSION = array();
			$_SESSION[SESS_SECURITY_TOKEN] = md5(uniqid(XRand(), true));
		}
	}
	//-------------------------------------------------------------
	function Test($CloseWriteSession = true)
	{
		global $Login;
		//-------------------------------------------------------------
		if (!isset($_SESSION[SESS_SECURITY_TOKEN]) || (XGetPost(PUB_SECURIY_TOKEN) !== $_SESSION[SESS_SECURITY_TOKEN]) )
		{
			ThrowToLogin();
		}
		//-------------------------------------------------------------
		if (!isset($_SESSION[SESS_LOGGED_IN]) || $_SESSION[SESS_LOGGED_IN] !== true || !isset($_SESSION[SESS_USER]))
		{
			ThrowToLogin();
		}
		//-------------------------------------------------------------
		LoginSession($_SESSION[SESS_USER], true /*$Restore*/);
		//-------------------------------------------------------------
		if ($CloseWriteSession)
			$this->Close();
		//-------------------------------------------------------------
	}
	//--------------------------------------------------------------------
	// Restore doesnt reset the session like a new login
	function Login($User, $Restore = false)
	{
		//-------------------------------------------------------------
		if (!$Restore)
		{
			//-------------------------------------------------------------
			$SecToken = XSession(SESS_SECURITY_TOKEN);
			//------------------------------------
			if (!$this->Reset())
				return false;
			//-------------------------------------------------------------
			$_SESSION = array();
			$_SESSION[SESS_SECURITY_TOKEN] = $SecToken;
			//-------------------------------------------------------------
		}
		//-------------------------------------------------------------
		$_SESSION[SESS_LOGGED_IN] = true;
		$_SESSION[SESS_USER] = $User;
		//-------------------------------------------------------------
	}
	//--------------------------------------------------------------------
	function Logoff()
	{
		//-------------------------------------------------------------
		$_SESSION[SESS_LOGGED_IN] = false;
		unset($_SESSION[SESS_USER]);
		//-------------------------------------------------------------
	}
	//--------------------------------------------------------------------
} // class XSession
//-------------------------------------------------------------
?>