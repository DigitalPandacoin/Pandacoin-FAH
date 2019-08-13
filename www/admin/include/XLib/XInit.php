<?php
//---------------
/*
* Expected defined input values:
* 
 * XIS_DEBUG (bool) [optional]
 * XINCLUDE_DIR (path string with trailing slash) [required unless root]
 * XLOG_FILENAME (path and file name string) [required writable by PHP/Server]
 * 
*/
//-------------------------------------------------------------
if (defined('XIS_DEBUG') && XIS_DEBUG) 
{
	error_reporting(E_ALL);
}
else				     
{
	error_reporting(0);
}
//-------------------------------------------------------------
define('XINIT_DEBUG', false); // verbose
//-------------------------------------------------------------
include_once(XINCLUDE_DIR.'XGeneralFunctions.php');
include_once(XINCLUDE_DIR.'XLogger.php');
include_once(XINCLUDE_DIR.'XLoggerInstance.php');
//-------------------------------------------------------------
if (XINIT_DEBUG)
	XLogDebug("XInit Logger created");
//-------------------------------------------------------------
include_once(XINCLUDE_DIR.'XMySqlDB.php');
include_once(XINCLUDE_DIR.'XDBFields.php');
include_once(XINCLUDE_DIR.'XLogin.php');
//-------------------------------------------------------------
//XLogDebug("Connecting to: ".XDATABASE_HOST.", ".XDATABASE_USER.", ".XDATABASE_PASS);
$db = new TXMySqlDB() or die("Create object failed");
if (!$db->Connect(XDATABASE_HOST, XDATABASE_USER, XDATABASE_PASS, XDATABASE_NAME))
	die('Connect to database failed.');
//-------------------------------------------------------------
if (!$db->SelectDatabase(XDATABASE_NAME))
	die('Database not accessable.');
//-------------------------------------------------------------
// Login is needed in non-admin for User's RealName lookup
$Login = new TXLogin() or die("Create object failed");
//-------------------------------------------------------------
if (defined('IS_ADMIN') && IS_ADMIN === true)
{
	//---------------------------------------------
	if (XINIT_DEBUG)
		XLogDebug("XInit IS Admin...");
	//---------------------------------------------
	require(XINCLUDE_DIR.'XSession.php');
	$Session = new TXSession() or die("Create object failed");
	//---------------------------------------------
	$Session->Start(X_SESSION_NAME);
	//---------------------------------------------
	if ( !(defined('IS_LOGIN') && IS_LOGIN) && !(defined('IS_INSTALL') && IS_INSTALL) )
	{
		//-----------------------------------------------
		$Session->Test(true); // restores existing sessions or throws to login
		if (!$Login->RestoreLogin($_SESSION[SESS_USER]))
		{
			XLogError("XInit.php User session not set or Login RestoreLogin failed");
			ThrowToLogin();
		}
		//$Session->Login($Login->User, true /*Restore*/);
		//-----------------------------------------------
	} // if not IS_LOGIN
	//---------------------------------------------
} // if IS_ADMIN
//-------------------------------------------------------------
if (XINIT_DEBUG)
	XLogDebug("XInit complete");
//-------------------------------------------------------------
define('IS_XINITTED', true);
//-------------------------------------------------------------
?>
