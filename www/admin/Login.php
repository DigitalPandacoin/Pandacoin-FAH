<?php
//-------------------------------------------------------------
/*
*	Login.php
*
*/
//-------------------------------------------------------------
define('IS_LOGIN', true);
define('IS_ADMIN', true);
//-------------------------------------------------------------
require('./include/Init.php');
//-------------------------------------------------------------
$p = XGetPost("p");
if ($p != "" && is_numeric($p))
{
	if ($p == 0) header("Location: ./Index.php");
	else         header("Location: ./Login.php");
}
else $_GET['p'] = 1;
//-------------------------------------------------------------
if (isset($_SESSION[SESS_SECURITY_TOKEN]))
	if (XPost('action') == 'Login')
	{
		XLogDebug("Processing Login");
		if (!isset($_SESSION[SESS_SECURITY_TOKEN]) || (XPost(PUB_SECURIY_TOKEN) !== $_SESSION[SESS_SECURITY_TOKEN]) )
		{
			XLogError("Session invalid. Trace:\n".XStackTrace());
			ThrowToLogin();
			exit; //reminder
		}
		if ($Login->Login(XPost('admin_name'), XPost('admin_pass')))
		{
			// Login Success
			if (!$Session->Login($Login->User))
				die("Session Login failed");;
			XLogDebug("Login Successfull for '{$Login->User}' from ".$_SERVER['REMOTE_ADDR']);
			Header("Location: ./Admin.php?".SecToken());
			exit;
		}
		else	
		{
			XLogWarn("Login failed for '".XPost('admin_name')."' from '".$_SERVER['REMOTE_ADDR']."'");
			sleep(12);
			ThrowToLogin("./Login.php?error=login_fail");
			exit; // reminder
		}
	} // Action = Login
//-------------------------------------------------------------
$title = X_TITLE." - Administration";
//---------------------------------
echo htmlHeader($title);
echo htmlHeaderBar($title);
//---------------------------------
$fnMenuItems = array();
$fnMenuItems[] = "Home";
$fnMenuItems[] = "Admin";
//---------------
echo htmlMenu($fnMenuItems, "./Login.php");
//---------------------------------
echo "<!--content -->\n";
//-------------------------------------------------------------
if (!isset($_SESSION[SESS_SECURITY_TOKEN]))
{
	echo '<div class="loginError">Security token missing. Please refresh this page to continue.</div>'."\n";
	exit;
}
//-------------------------------------------------------------
if (XGet('error') == 'login_fail')
{
	echo '<div class="loginError">Login failed. Please check your user name and password, then try again.</div>'."\n";
}
//-------------------------------------------------------------
?>
<div style="margin: 60px;">
<form action="./Login.php" method="post" enctype="multipart/form-data">
	<fieldset  class="loginBox" style="margin:auto; width:200px;padding: 10px; background-color:#AAAAAA;color:black;">
		<legend style="font-weight:bold;margin-top:30px;">Admin Login</legend>
		<br/>
		<?php PrintSecTokenInput(); ?>
		<div>
			<label class="loginLabel" for="name">User name:</label><br/>
			<input style="width:100%;" id="name" type="text" name="admin_name" value="" />
		</div>
		<div>
			<label class="loginLabel" for="pass">Password:</label><br/>
			<input style="width:100%;" id="pass" type="password" name="admin_pass" value="" />
		</div>
		<br/>
		<input type="submit" name="action" value="Login" />
	</fieldset>
</form>
</div>
<?php
//---------------------------------
echo "<!--content end-->\n";
//---------------------------------
echo htmlFooter();
//---------------------------------
?>