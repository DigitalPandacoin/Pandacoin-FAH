<?php
/*
 * include/pages/admin/Users.php
 * 
 * 
*/
//------------------------
global $Login;
global $Session;
global $Privileges;
//------------------------
// For manual stat adding
$Stats = new Stats() or die("Create object failed");
//------------------------
$pagenum = XGetPost('p');
$action = XPost('action');
$uidx = XGetPost('idx');
if (!is_numeric($uidx))
	$uidx = 0;
//------------------------
$maxidx = XGetPost('maxidx');
if ($maxidx == "")
	$maxidx = -1;
//------------------------
$userName = "";
$userRealName = "";
$userID = "";
$userPriv = "";
$userTz = "";
$userPass = "";
$failCount = 0;
//------------------------
function getPrivilegeTable($curPrivs, $readonly = false)
{
	global $Privileges;
	//------------------------
	$out = "<table class='admin-priv-table'>\n";
	//------------------------
	$out .= "<tr><th style='width: 25px;'>&nbsp;</th><th style='font-size: 70%;'>(hover for details)</th></tr>\n";
	//------------------------
	if ($readonly === true)
		$readonly = " disabled='disabled'";
	else
		$readonly = "";
	//------------------------
	foreach ($Privileges as $priv)
	{
		$out .= "<tr class='admin-priv-row'><td><input id='priv_$priv[0]' type='checkbox' name='priv_$priv[0]' value='1'"; 
		if (strpos($curPrivs, $priv[0]) !== false)
			$out .= " checked='checked'";
		$out .= "$readonly /></td><td title='$priv[2]'>$priv[1]</td></tr>\n";
	}
	//------------------------
	$out .= "</table>\n";	
	//------------------------
	return $out;
}
//------------------------
function readPostedPrivileges()
{
	global $Privileges;
	//------------------------
	$out = "";
	//------------------------
	foreach ($Privileges as $priv)
	{
		$test = XPost("priv_$priv[0]", false);
		if ($test !== false && $test == "1")
			$out .= $priv[0];
	}
	//------------------------
	return $out;
}
//------------------------
function getTimeZones($selected = "", $readonly = false)
{
	//------------------------	
	$out = "<select name='userTz' id='userTz' ".($readonly !== false ? " disabled" : "").">\n";
	//------------------------	
	$timezones  = DateTimeZone::listIdentifiers();
	//------------------------	
	foreach ($timezones as $tz)
		$out .= "<option value='$tz'".($tz === $selected ? " selected" : "").">$tz</option>\n";
	//------------------------	
	$out .= "</select>\n";
	//------------------------	
	return $out;
}
//------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<?php
		//------------------------
		if ($Login->HasPrivilege(XUSER_PRIV_ADMIN))
		{ // hanging if that extends to nearly end of Users.php
		//------------------------
	?>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1"><!-- Start Left Column -->
		<div>
			<h1>Users:</h1>
			<p>
			These users are permitted to login to the<br/>
			administration section of this website.
			</p>
			<br/>
		</div>
		<?php
			//------------------------
			if ($action == "Add")
			{
				//------------------------
				$userName 		= XPost('uName');
				$userRealName 	= XPost('uRName');
				$userTz			= XPost('userTz');
				$userPriv 		= readPostedPrivileges();
				//------------------------
				if (!$Login->IsValidUserName($userName))
				{
					XLogNotify("Admin Users Add user name is invalid");
					echo "Add user failed. User name contains invalid characters or is not long enough.<br/>";
				}
				else if (false !== ($rn = $Login->GetUserRealName($userName)))
				{
					XLogNotify("Admin Users Add user name is already taken '$userName'->'$rn'");
					echo "Add user failed. User name '".XEncodeHTML($userName)."' is already taken by '".XEncodeHTML($rn)."'.<br/>";
				}
				else if (!$Login->IsValidRealName($userRealName))
				{
					XLogNotify("Admin Users Add user real name is invalid");
					echo "Add user failed. User real name contains invalid characters or is not long enough.<br/>";
				}
				else if (!$Login->IsValidTimezone($userTz))
				{
					XLogNotify("Admin Users Add user time zone is invalid");
					echo "Add user failed. User time zone is invalid.<br/>";
				}
				else if (!$Login->IsValidPriv($userPriv))
				{
					XLogNotify("Admin Users Add user priv is invalid");
					echo "Add user failed. User privilege contains invalid characters.<br/>";
				}
				else if (!$Login->AddUser($userName, $userRealName, $userPriv, false/*password, defaults to userName*/, $userTz))
				{
					XLogError("Users admin page - Login failed to AddUser");
					echo "<div>Add user failed.</div>\n";
				} 
				else 
				{
					XLogNotify("Users admin page - adding user ($userName, $userRealName, $userPriv)");
					echo "Added user successfully.<br/></br>\n";
					$uidx = $maxidx+1;
				}					
				//------------------------
			}
			else if ($action == "Update")
			{
				//------------------------
				$userID 		= XPost('uID');
				$userName 		= XPost('uName');
				$userRealName 	= XPost('uRName');
				$userTz			= XPost('userTz');
				$userPriv 		= readPostedPrivileges();
				//------------------------
				if (!is_numeric($userID))
				{
					XLogError("Admin Users Update validate user ID failed '$testUserID'");
					echo "Update security validation failed.<br/>";
				}
				else if (!$Login->IsValidUserName($userName))
				{
					XLogNotify("Admin Users Update user name is invalid");
					echo "Update failed. User name contains invalid characters or is not long enough.<br/>";
				}
				else if (!$Login->IsValidRealName($userRealName))
				{
					XLogNotify("Admin Users Update user real name is invalid");
					echo "Update failed. User real name contains invalid characters or is not long enough.<br/>";
				}
				else if (!$Login->IsValidTimezone($userTz))
				{
					XLogNotify("Admin Users Update user time zone is invalid");
					echo "Update user failed. User time zone is invalid.<br/>";
				}
				else if (!$Login->IsValidPriv($userPriv))
				{
					XLogNotify("Admin Users Update user priv is invalid");
					echo "Update failed. User privilege contains invalid characters.<br/>";
				}
				else if (!$Login->UpdateUser($userID, $userName, $userRealName, $userTz, $userPriv))
				{
					XLogError("Admin Users Update - Login failed to UpdateUser");
					echo "<div>Update user failed.</div>\n";
				} 
				else
				{
					XLogNotify("Users admin page - user update ($userID, $userName, $userRealName,  $userPriv)");
					echo "Updated user successfully.<br/></br>\n";
					$currUser = ($Login->UserID === $userID ? true : false);
					$currUserName = $Login->UserName;
					if ($currUser && $userName != $currUserName) // user name changed, this will break session
					{
						$Login->Logoff();
						$Session->Destroy();
						echo "You must re-login because of these changes.<br/><br/>\n";
						echo "Redirecting in 5 seconds...<br/><br/>\n";
						echo "</div></div></div>\n";
						echo "<script type='text/javascript'>self.setTimeout(\"self.location.href = './Login.php';\", 5000); </script>\n";
						exit();
					}
				}
				//------------------------
			}
			else if ($action == "Change Password")
			{
				//------------------------
				$userID 		= XPost('pID');
				$userName 		= XPost('pName');
				$oldPass		= XPost('oPass');
				$newPass1 		= XPost('pPass1');
				$newPass2 		= XPost('pPass2');
				//------------------------
				if ($oldPass == "")
					$oldPass = false;
				//------------------------
				if (!is_numeric($userID))
				{
					XLogError("Admin Users Change Password validate user ID failed '$testUserID'");
					echo "Change user password security validation failed.<br/>";
				}
				else if (!$Login->IsValidUserName($userName))
				{
					XLogNotify("Admin Users Change Password user name is invalid '$userName'");
					echo "Change user password failed. User name contains invalid characters or is not long enough.<br/>";
				}
				else if ($newPass1 !== $newPass2)
				{
					XLogNotify("Admin Users Change Password new passwords don't match");
					echo "Change user password failed. The new passwords do not match.<br/>";
				}
				else if ($oldPass === false && $Login->UserID === $userID)
				{
					XLogNotify("Admin Users Change Password old password required to change your own password");
					echo "Change user password failed. Old password is required to change your own password.<br/>";
				}
				else if ($oldPass !== false && !$Login->IsValidPassword($oldPass))
				{
					XLogNotify("Admin Users Change Password old password is invalid '$oldPass'");
					echo "Change user password failed. Old password contains invalid characters or is not long enough. Old password is required to change your own password.<br/>";
				}
				else if (!$Login->IsValidPassword($newPass1))
				{
					XLogNotify("Admin Users Change Password new password is invalid '$newPass1'");
					echo "Change user password failed. New passwords contains invalid characters or is not long enough.<br/>";
				}
				else if (!$Login->SetUserPassword($userID, $userName, $oldPass, $newPass1))
				{
					XLogError("Admin Users Change Password - Login failed to SetUserPassword");
					echo "<div>Change user password failed.</div>\n";
				} 
				else
				{
					XLogNotify("Users admin page - user password changed ($userID, $userName)");
					echo "Changed user password successfully.<br/></br>\n";
					//ThrowToLogin();
				}
				//------------------------
			}
			else if ($action == "Delete")
			{
				//------------------------
				$userID 		= XPost('uID');
				$userName 		= XPost('uName');
				//------------------------
				XLogNotify("Users admin page - user delete ($userID) '$userName'");
				//------------------------
				if ($userID != "")
				{
					if (!$Login->DeleteUser($userID))
					{
						XLogNotify("Users admin page - user failed to DeleteUser ($userID) '$userName'");
						echo "<div>Delete user failed.</div>\n";
					} 
					else echo "Deleted user successfully.<br/></br>\n";
				}
				//------------------------
			}
			else if ($action == "Reset Ban")
			{
				//------------------------
				$userID 		= XPost('uID');
				$userName 		= XPost('uName');
				//------------------------
				XLogNotify("Users admin page - user reset ban ($userID) '$userName'");
				//------------------------
				if ($userID != "")
				{
					if (!$Login->ResetUserFailCount($userID))
					{
						XLogNotify("Users admin page - user failed to ResetUserFailCount ($userID) '$userName'");
						echo "<div>Reset user ban failed.</div>\n";
					} 
					else echo "Reset user ban successfully.<br/></br>\n";
				}
				//------------------------
			}
			//------------------------
		?>
		<div>(click row to select for editting or deleting)</div>
		<table class='admin-table'>
		<tr><th>User</th><th>Name</th><th style='width:70px;'>Priv</th><th style='width:40px;'>Ban</th></tr>
		<?php
			//------------------------	
			$qr = $Login->GetUserList();
			if (!$qr)
				XLogError("Users admin page - GetUserList failed");
			if (!$qr || $qr->RowCount() == 0)
				echo "<tr class='admin-row'><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
			else
			{
				//------------------------
				$i = 0;
				while ($u = $qr->GetRowArray())
				{
					//------------------------
					if ($i == $uidx)
					{
						$rowClass = 'admin-row-sel';
						$userID			= $u[XDB_USER_ID];
						$userName		= $u[XDB_USER_USER];
						$userRealName	= $u[XDB_USER_REALNAME];
						$userPriv		= $u[XDB_USER_PRIV];
						$userTz			= $u[XDB_USER_TZ];
						$failCount 		= $u[XDB_LOCKOUT];					
					}
					else $rowClass = 'admin-row';
					//------------------------
					echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;idx=$i&amp;".SecToken()."';\">";
					echo "<td style='width:80px;'>".XEncodeHTML($u[XDB_USER_USER])."</td><td>".XEncodeHTML($u[XDB_USER_REALNAME])."</td><td style='width:70px;'>".XEncodeHTML($u[XDB_USER_PRIV])."</td>";
					echo "<td style='width:40px;'>".($failCount >= XLOGIN_FAIL_BAN ? "<span style='color:red;font-weight:bold;'>&nbsp;x&nbsp;</span>" : "&nbsp;&nbsp;&nbsp;")."</td></tr>\n";
					//------------------------
					$maxidx = $i; // before increment
					$i++;
					//------------------------
				}
				//------------------------
			}
			//------------------------
			echo "</table>\n";		
			//------------------------	
		?>
		</div><!-- End Left Column -->
		<div class="under line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1 pad_left1" style="width:350px;"><!-- Start Left Column -->
		<div class="admin-edit" style="width:250px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox" style="width:245px;">
				<legend style="font-weight:bold;font-size:120%;">Update User Details:</legend>
				<br/>
				<?php
					//---------------
					if ($Login->UserID === $userID)
						echo "<div style='text-align:center;width:100%;font-weight:bold;color:#800000;'>**Currently logged in user**</div><br/>";
					//---------------
				?>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="idx" value="<?php echo $uidx; ?>" />
				<input type="hidden" name="maxidx" value="<?php echo $maxidx; ?>" />
				<input type="hidden" name="uID" value="<?php echo $userID; ?>" />
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<div>
					<label class="loginLabel" for="uName">User:</label><br/>
					<input id="uName" type="text" name="uName" value="<?php echo XEncodeHTML($userName);?>" maxlength="<?php echo XLogin_User_Name_Length;?>" />
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="uRName">Real Name:</label><br/>
					<input style="width:180px;" id="uRName" type="text" name="uRName" value="<?php echo XEncodeHTML($userRealName);?>" maxlength="<?php echo XLogin_Real_Name_Length;?>" />
				</div>
				<br/>
				<div>
					<div>Time Zone:</div>
					<?php echo getTimeZones($userTz);?>
				</div>
				<br/>
				<div>
					<div>Privileges:</div>
					<?php echo getPrivilegeTable($userPriv); ?>
				</div>
				<br/>
				<input type="submit" name="action" value="Add" />&nbsp;
				<input type="submit" name="action" value="Update" />&nbsp;
				<input type="submit" name="action" value="Delete" /><br/>
				<br/>
				<input type="submit" name="action" value="Reset Ban" />&nbsp;&nbsp;Fail count:&nbsp;<?php echo "<span".($failCount >= XLOGIN_FAIL_BAN ? " style='color:red;'" : "").">$failCount/".XLOGIN_FAIL_BAN."</span>"; ?><br/>
				<br/>
			</fieldset>
		</form>
		</div>
		</div><!-- End Left Column -->
		<div class="col1 pad_left1" style="width:350px;"><!-- Start Right Column -->
		<div class="admin-edit" style="width:250px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style="font-weight:bold;font-size:120%;">Change User Password:</legend>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="idx" value="<?php echo $uidx; ?>" />
				<input type="hidden" name="pID" value="<?php echo $userID; ?>" />
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<div>
					<label class="loginLabel" for="pName">User (read only):</label><br/>
					<input id="pName" type="text" name="pName" value="<?php echo XEncodeHTML($userName);?>" readonly="readonly" />
				</div>
				<br/>
				<?php
					//---------------
					if ($Login->UserID === $userID)
						echo "<div><label class='loginLabel' for='oPass'>Current password:</label><br/><input id='oPass' type='password' name='oPass' value='' maxlength='".XLogin_User_Pass_Length."' /></div><br/>";
					//---------------
				?>
				<div>
					<label class="loginLabel" for="pPass1">Password:</label><br/>
					<input id="pPass1" type="password" name="pPass1" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<div>
					<label class="loginLabel" for="pPass2">Password(repeat):</label><br/>
					<input id="pPass2" type="password" name="pPass2" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<br/>
				<input type="submit" name="action" value="Change Password" />&nbsp;
				<br/>
			</fieldset>
		</form>
		</div>
		</div><!-- End Right Column -->
		</div><!-- End Container 3 -->
	</div><!-- End Container 3 -->
	<?php 
		//------------------
		} // if ($Login->HasPrivilege(XUSER_PRIV_ADMIN))  starts nearly at beginning of Users.php
		else // if ($Login->HasPrivilege(XUSER_PRIV_ADMIN))
		{
			//------------------
			if (!$Login->LoggedIn)
			{
				XLogError("Admin Users not logged in! Aborting...");
				exit("Security panic! Halting!");				
			}
			//------------------
			$userID 		= $Login->UserID;
			$userName 		= $Login->User;
			$userRealName 	= $Login->UserName;
			$userTz			= $Login->tz;
			$userPriv 		= $Login->UserPriv;
			if ($userPriv == "")
				$userPriv = "(none)";
			//------------------
			if ($action == "Update")

			{
				//------------------
				$testUserID  = XPost('uID');
				$testOldName = XPost('uOldName');
				$newName 	 = XPost('uName');
				$newRName 	 = XPost('uRName');
				$newTz		 = XPost('userTz');
				//------------------
				if (!is_numeric($testUserID) || $userID !== $testUserID)
				{
					XLogError("Admin Users Update user id mismatch. Logged in as $userID posted '$testUserID'");
					echo "Update security validation failed.<br/>";
				}
				else if ($testOldName !== $userName)
				{
					XLogError("Admin Users Update user name mismatch. Logged in as '$userName' posted old user name '$testOldName'");
					echo "Update security validation failed.<br/>";
				}
				else if ($newName != $userName && !$Login->IsValidUserName($newName))
				{
					XLogNotify("Admin Users Update new user name is invalid");
					echo "Update failed. New user name contains invalid characters or is not long enough.<br/>";
				}
				else if ($newRName != $userRealName && !$Login->IsValidRealName($newRName))
				{
					XLogNotify("Admin Users Update new user real name is invalid");
					echo "Update failed. New user real name contains invalid characters or is not long enough.<br/>";
				}
				else if ($userTz != $newTz && !$Login->IsValidTimezone($newTz))
				{
					XLogNotify("Admin Users Update new user time zon is invalid");
					echo "Update failed. New user time zon is invalid.<br/>";
				}
				else if (!$Login->UpdateUser($userID, $newName, $newRName, $newTz))
				{
					XLogNotify("Admin Users Update UpdateUser failed");
					echo "Update failed. A database error or security validation failed. Please try again.<br/>";
				}
				else
				{
					//------------------
					 echo "Updated user successfully<br/>\n";
					//------------------
					$userID 		= $Login->UserID;
					$userName 		= $Login->User;
					$userRealName 	= $Login->UserName;
					$userTz			= $Login->tz;
					$userPriv 		= $Login->UserPriv;
					if ($userPriv == "")
						$userPriv = "(none)";
					//------------------
				}
				//------------------
			}
			else if ($action == "Change Password")
			{
				//------------------
				$testUserID   = XPost('pID');
				$testUserName = XPost('pName');
				$oldPass 	  = XPost('oPass');
				$newPass1 	  = XPost('nPass1');
				$newPass2 	  = XPost('nPass2');
				//------------------
				if (!is_numeric($testUserID) || $userID !== $testUserID)
				{
					XLogError("Admin Users Change Password user id mismatch. Logged in as $userID posted '$testUserID'");
					echo "Change password security validation failed.<br/>";
				}
				else if ($testUserName !== $userName)
				{
					XLogError("Admin Users Change Password user name mismatch. Logged in as '$userName' posted user name '$testUserName'");
					echo "Change password security validation failed.<br/>";
				}
				else if ($newPass1 !== $newPass2)
				{
					XLogNotify("Admin Users Change Password new passwords didn't match");
					echo "Change password failed. The new passwords didn't match.<br/>";
				}
				else if (!$Login->IsValidPassword($oldPass))
				{
					XLogNotify("Admin Users Change Password old password is invalid");
					echo "Change password failed. The old password contains invalid characters or is not long enough.<br/>";
				}
				else if (!$Login->IsValidPassword($newPass1))
				{
					XLogNotify("Admin Users Change Password new password is invalid");
					echo "Change password failed. New user password contains invalid characters or is not long enough.<br/>";
				}
				else if (!$Login->SetUserPassword($userID, $userName, $oldPass, $newPass1 ))
				{
					XLogNotify("Admin Users Change Password SetUserPassword failed");
					echo "Change password failed. A the old password was incorrect or another security validation failed. Please try again.<br/>";
				}
				else echo "Changed user password successfully<br/>\n";
				//------------------
			}
			
			else if (false) //disabled // $action == "Add Manual Stat")
			{
				//------------------
				$rIdx 	  = XPost('msRidx');
				$wIdx 	  = XPost('msWidx');
				$work 	  = XPost('msWork');
				$mode	  = XPost('msRMode');
				//------------------
				if (!is_numeric($rIdx) || !is_numeric($wIdx) || !is_numeric($work))
				{
					XLogError("Admin Add Manual Stat validate fields failed");
					echo "Add Manual Stat validate fields failed.<br/>";
				}
				else
				{
					if (!$Stats->addStat($rIdx, $mode, $wIdx, false/*reload*/))
					{
						XLogError("Admin Add Manual Stat Stats addStat failed");
						echo "Add Manual Stat Stats addStat failed.<br/>";
					}
					else
					{
						$stat = $Stats->findStat($rIdx, $wIdx);
						if ($stat === false)
						{
							XLogError("Admin Add Manual Stat Stats findStat failed");
							echo "Add Manual Stat Stats addStat failed.<br/>";
						}
						else
						{
							//------------------
							$Config = new Config() or die("Create object failed");
							//------------------
							$teamId = $Config ->Get(CFG_ROUND_TEAM_ID);
							if ($teamId === false || !is_numeric($teamId))
							{
								XLogError("Team::calcTeamStats get team ID config failed");
								echo "Add Manual Stat Stat Update failed.<br/>";
							}
							else
							{
								//------------------
								$nowUtc = new DateTime('now',  new DateTimeZone('UTC'));
								$stat->dtPolled 	= $nowUtc->format(MYSQL_DATETIME_FORMAT);
								$stat->team			= $teamId;
								$stat->weekPoints	= $work;
								if (!$stat->Update())
								{
									XLogError("Admin Add Manual Stat stat Update failed");
									echo "Add Manual Stat Stat Update failed.<br/>";
								}
								else echo "Added manual stat for round $rIdx, worker $wIdx, with work $work successfull.<br/>\n";
								//------------------
							}
							//------------------
						}
					}
					
				}
				//------------------
			}
			//------------------
		   ?>
		   
		   <?php 
		    // Dissabled Manual Add Stat
		   /*
			<div class="col1 pad_left1" style="width:350px;"><!-- Start extra -->
			<div class="admin-edit" style="margin:auto; width:250px;">
			<form action="./Admin.php" method="post" enctype="multipart/form-data">
				<fieldset class="loginBox">
					<legend style="font-weight:bold;font-size:120%;">Add Manual Stat Work:</legend>
					<br/>
					<?php PrintSecTokenInput(); ?>
					<input type="hidden" name="pID" value="<?php echo $userID; ?>" />
					<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/>
					<div>
				</div>
				<div>
					<label class="loginLabel" for="msRidx">Round Idx:</label><br/>
					<input id="oPass" type="text" name="msRidx" value="98" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<div>
					<label class="loginLabel" for="msRMode">Round Mode:</label><br/>
					<input id="oPass" type="text" name="msRMode" value="3" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<div>
					<label class="loginLabel" for="msWidx">Worker Idx:</label><br/>
					<input id="oPass" type="text" name="msWidx" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="msWork">Work:</label><br/>
					<input id="nPass1" type="text" name="msWork" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<br/>
				<input type="submit" name="action" value="Add Manual Stat" />&nbsp;
				<br/>
				<br/>
				</fieldset>
			</form>
			</div><!-- Form container -->
		</div><!-- Start extra -->
		*/
		   ?>
		   
			<div class="under line1 wrapper pad_bot2"><!-- Start Container 3 -->
			<div class="col1 pad_left1" style="width:350px;"><!-- Start Left Column -->
			<div class="admin-edit" style="width:250px;">
			<form action="./Admin.php" method="post" enctype="multipart/form-data">
				<fieldset class="loginBox" style="width:245px;">
					<legend style="font-weight:bold;font-size:120%;">Update User Details:</legend>
					<br/>
					<?php PrintSecTokenInput(); ?>
					<input type="hidden" name="uID" value="<?php echo $userID; ?>" />
					<input type="hidden" name="uOldName" value="<?php echo XEncodeHTML($userName); ?>" />
					<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
					<div>
					<label class="loginLabel" for="uName">User:</label><br/>
					<input id="uName" type="text" name="uName" value="<?php echo XEncodeHTML($userName);?>" maxlength="<?php echo XLogin_User_Name_Length;?>" />
				</div>
				<div>
					<label class="loginLabel" for="uRName">Real Name:</label><br/>
					<input style="width:180px;" id="uRName" type="text" name="uRName" value="<?php echo XEncodeHTML($userRealName);?>" maxlength="<?php echo XLogin_Real_Name_Length;?>" />
				</div>
				<br/>
				<div>
					<div>Time Zone:</div>
					<?php echo getTimeZones($userTz);?>
				</div>
				<br/>
				<div>
					<div>Privileges (read only):</div>
					<?php echo getPrivilegeTable($userPriv, true/*read only*/); ?>
				</div>
				<br/>
				<input type="submit" name="action" value="Update" />&nbsp;
				<br/>
				<br/>
				</fieldset>
			</form>
			</div><!-- Form container -->
			</div><!-- End Left Column -->
			<div class="col1 pad_left1" style="width:350px;"><!-- Start Right Column -->
			<div class="admin-edit" style="margin:auto; width:250px;">
			<form action="./Admin.php" method="post" enctype="multipart/form-data">
				<fieldset class="loginBox">
					<legend style="font-weight:bold;font-size:120%;">Change User Password:</legend>
					<br/>
					<?php PrintSecTokenInput(); ?>
					<input type="hidden" name="pID" value="<?php echo $userID; ?>" />
					<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/>
					<div>
					<label class="loginLabel" for="pName">User (read only):</label><br/>
					<input id="pName" type="text" name="pName" value="<?php echo XEncodeHTML($userName);?>" readonly="readonly" />
				</div>
				<div>
					<label class="loginLabel" for="oPass">Current password:</label><br/>
					<input id="oPass" type="password" name="oPass" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="nPass1">New Password:</label><br/>
					<input id="nPass1" type="password" name="nPass1" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<div>
					<label class="loginLabel" for="nPass2">New Password(again):</label><br/>
					<input id="nPass2" type="password" name="nPass2" value="" maxlength="<?php echo XLogin_User_Pass_Length;?>" />
				</div>
				<br/>
				<input type="submit" name="action" value="Change Password" />&nbsp;
				<br/>
				<br/>
				</fieldset>
			</form>
			</div><!-- Form container -->
		</div><!-- End Right Column -->
		</div><!-- End Container 3 -->
		<br/><br/>
		<div style='font-weight:bold;'>Your currently logged in user does not have privileges to add or modify other user accounts.<br/>Please contact your administrator for help with managing administration users.</div>
		<?php
			//-----------
			} // else // if ($Login->HasPrivilege(XUSER_PRIV_ADMIN))
			//-----------
		?>
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->
