<?php
//-------------------------------------------------------------
// TODO:   TestSession should validate the IP host data and expiration date
//
//-------------------------------------------------------------
define('XSESSION_DEBUG', false); // verbose
//-------------------------------------------------------------
define('SESS_PREFIX', 			'ss_sess_');
define('SESS_LOGGED_IN',      	SESS_PREFIX.'loggedin');
define('SESS_SECURITY_TOKEN', 	SESS_PREFIX.'sectoken');
define('SESS_USER', 			SESS_PREFIX.'user');
define('PUB_SECURIY_TOKEN', 	'st');
//-------------------------------------------------------------
if (!defined('XLOGGER_INSTANCE'))
	die('XSession - XLoggerInstance required');
//------------------------------------------------------------------------------
function ThrowToLogin($page = "")
{
	global $Login, $Session;
	if (XSESSION_DEBUG)
		XLogDebug("XSession.php ThrowToLogin - Throw login\nTrace: ".XStackTrace()."\nGET: ".XVarDump($_GET)."\nPOST: ".XVarDump($_POST)."\nSESSION: ".XVarDump($_SESSION));
	else
		XLogDebug("XSession.php ThrowToLogin User: ".XSession(SESS_USER, "(unknown)"));
	$Login->Logoff();
	$Session->Destroy();
	if ($page == "")
		$page = "./Login.php";
	header("Location: $page");
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
class TXSession
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
		@session_destroy();
	}
	//-------------------------------------------------------------
	function Reset()
	{
		$old_sessid = session_id(); 
		//-------------------------------------------------------------
		if (headers_sent($filename, $linenum))
		   XLogError("XSession::Reset - headers already sent on ($linenum) by '$filename'");
		//-------------------------------------------------------------
		if (!session_regenerate_id()) //get a new session id (must do this before destroying the old session)
		{
			XLogError("XSession::Reset - session_regenerate_id failed");
			return false;
		}
		//-------------------------------------------------------------
		$new_sessid = session_id(); //save new session id so we can get back to it
		session_id($old_sessid);
		//-------------------------------------------------------------
		unset($old_sessid);
		session_destroy(); //destroy the session they got before they logged in
		//-------------------------------------------------------------
		session_id($new_sessid);
		session_start(); //start the new session
		//-------------------------------------------------------------
		if (session_id() != $new_sessid)
		{
			XLogWarn("XSession::Reset - validate session_id failed");
			return false;
		}
		//-------------------------------------------------------------
		unset($new_sessid);
		//-------------------------------------------------------------
		return true;
	}
	//-------------------------------------------------------------
	function Start($Name)
	{
		//-------------------------------------------------------------
		if (XSESSION_DEBUG)
			XLogDebug("TXSession::Start Starting session");
		//-------------------------------------------------------------
		session_name($Name) or die("Session failure");
		session_start() or die("Session failure");
		//-------------------------------------------------------------
		if (!isset($_SESSION[SESS_SECURITY_TOKEN]))
		{
			XLogDebug("TXSession::Start Setting security token");
			if (!isset($_SESSION)) $_SESSION = array();
			$_SESSION[SESS_SECURITY_TOKEN] = md5(uniqid(XRand(), true));
		}
		//-------------------------------------------------------------
	}
	//-------------------------------------------------------------
	function Test($CloseWriteSession = true)
	{
		global $Login;
		//-------------------------------------------------------------
		if (!isset($_SESSION[SESS_SECURITY_TOKEN]) || (XGetPost(PUB_SECURIY_TOKEN) !== $_SESSION[SESS_SECURITY_TOKEN]) )
		{
			XLogUser("Verify session security token failed from ".$_SERVER['REMOTE_ADDR']);
			XLogDebug("TXSession::Test Session Test Verify security token failed from ".$_SERVER['REMOTE_ADDR']);
			XLogDebug("TXSession::Test session token '".(!isset($_SESSION[SESS_SECURITY_TOKEN]) ? "<not set>" :  $_SESSION[SESS_SECURITY_TOKEN])."' GetPost token: '".XGetPost(PUB_SECURIY_TOKEN)."'");
			ThrowToLogin();
		}
		//-------------------------------------------------------------
		if (!isset($_SESSION[SESS_LOGGED_IN]) || $_SESSION[SESS_LOGGED_IN] !== true || !isset($_SESSION[SESS_USER]))
		{
			XLogUser("Verify session flags failed from ".$_SERVER['REMOTE_ADDR']);
			XLogError("XSession::Test - Session Test Loggedin/user not set from".$_SERVER['REMOTE_ADDR']);
			ThrowToLogin();
		}
		//-------------------------------------------------------------
		$this->Login($_SESSION[SESS_USER], true /*$Restore*/);
		//-------------------------------------------------------------
		if ($CloseWriteSession)
		{
			if (XSESSION_DEBUG)
				XLogDebug("TXSession::Test Session writing is closed");
			$this->Close();
		}
		//-------------------------------------------------------------
	}
	//--------------------------------------------------------------------
	// Restore doesnt reset the session like a new login
	function Login($User, $Restore = false)
	{
		//-------------------------------------------------------------
		if (XSESSION_DEBUG)
			XLogDebug("TXSession::Login Logging in session for user: $User");
		//-------------------------------------------------------------
		if (!$Restore)
		{
			//-------------------------------------------------------------
			XLogUser("User '$User' logged in from ".$_SERVER['REMOTE_ADDR']);
			XLogDebug("TXSession::Login Resetting session");
			$SecToken = XSession(SESS_SECURITY_TOKEN);
			//------------------------------------
			if (!$this->Reset())
			{
				XLogError("XSession::Login - Reset Session failed");
				return false;
			}
			//-------------------------------------------------------------
			$_SESSION = array();
			$_SESSION[SESS_SECURITY_TOKEN] = $SecToken;
			//-------------------------------------------------------------
		}
		else if (defined('XIS_DEBUG') && XIS_DEBUG) XLogUser("User '$User' session restored from ".$_SERVER['REMOTE_ADDR']);
		//-------------------------------------------------------------
		$_SESSION[SESS_LOGGED_IN] = true;
		$_SESSION[SESS_USER] = $User;
		//-------------------------------------------------------------
		return true;
	}
	//--------------------------------------------------------------------
	function Logoff()
	{
		//-------------------------------------------------------------
		if (XSESSION_DEBUG)
			XLogDebug("TXSession::Logoff Logging off session");
		$_SESSION[SESS_LOGGED_IN] = false;
		unset($_SESSION[SESS_USER]);
		//-------------------------------------------------------------
	}
	//--------------------------------------------------------------------
} // class XSession
//-------------------------------------------------------------
?>