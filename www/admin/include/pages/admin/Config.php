<?php
//---------------------------------
/*
 * admin/include/pages/admin/Config.php
 * 
 * 
*/
//---------------------------------
global $Login;
$config = new Config() or die("Create object failed");
$pagenum = XGetPost('p');
$action = XGetPost('action');
$selCfg = XGetPost('cfg');
$showdbvers = (XGetPost('showdbvers') != "" ? true : false);
$selValue = "";
//---------------------------------
function actionConfig($action)
{
	$FahClient = new FahClient() or die("Create object failed");
	//---------------------------------
	if ($action == 'Team')
	{
		XLogError("Config Page - Team action starting...");
		if (!$FahClient->pollTeamOverview())
		{
			XLogError("Config Page - FahClient pollTeamOverview failed");
			return;
		}
		XLogError("Config Page - Team action starting...DONE");
	}
	//---------------------------------
}
//---------------------------------
function printConfig($showdbvers, $pagenum)
{
	//---------------------------------
	XLogError("Config Page - Team action print: '$showdbvers', '$pagenum'");
	//---------------------------------
	echo "<div class='col1 pad_left1'><!-- Start Right Column -->
		  <div class='admin-edit' style='width:250px;'>
		  <form action='./Admin.php' method='post' enctype='multipart/form-data'>
			  <fieldset class='loginBox'>\n";
	//---------------------------------
	PrintSecTokenInput();
	//---------------------------------
	echo "		<input type='submit' name='action' value='Team' />
				<input type='hidden' name='p' value='$pagenum'/><!-- Page -->
				<input id='showdbvers' type='checkbox' name='showdbvers' value='1' "
				.($showdbvers ?  " checked='checked'" : "")."/>Show Database Versions&nbsp;<input type='submit' name='faction' value='Set' />";
	//---------------------------------
	echo "	</fieldset>
		</form>
		</div>
		</div>";
	//---------------------------------
}
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1"><!-- Start Left Column -->
		<div>
			<h1>Config:</h1>
			<p>
			Folding @ Home General Configuration.
			</p>
			<br/>
		</div>
		<?php
		//---------------------------------
		actionConfig($action);
		printConfig($showdbvers, $pagenum);
		//---------------------------------
		if ($action == 'Set')
		{
			//---------------------------------
			if ($selCfg != "" && $Login->HasPrivilege(PRIV_CONFIG_MANAGE))
			{
				//---------------------------------
				$newValue = XPost('newValue');
				//---------------------------------
				if (!$config->Set($selCfg, $newValue))
				{
					XLogError("Admin Config set failed.");
					echo "<div>Set Config failed.</div>\n";
				}
				else
					echo "<div>Config updated.</div>\n";
				//---------------------------------
			}
			//---------------------------------
		}
		else if ($action == 'New')
		{
			//---------------------------------
			$newCfg = XPost('newCfg');
			//---------------------------------
			if (!$config->Set($newCfg, ""))
			{
				XLogError("Admin Config New failed.");
				echo "<div>Set Config failed.</div>\n";
			}
			else
				echo "<div>Config created.</div>\n";
			//---------------------------------
		}
		else if ($action == 'Delete')
		{
			//---------------------------------
			// Configs doesn't have a delete, only Clear which simply sets the value to null
			/*
			if (!$configs->Set($newCfg, ""))
			{
				XLogError("Admin Config New failed.");
				echo "<div>Set Config failed.</div>\n";
			}
			else
				echo "<div>Config created.</div>\n";
			*/
			//---------------------------------
		}
		//---------------------------------
		if ($Login->HasPrivilege(PRIV_CONFIG_MANAGE))
			echo "<div>(click row to select for editting or deleting)</div>\n";
		else
			echo "<div>(read only privileges)</div>\n";
		?>
		<div class="col1 pad_left1"><!-- Start Right Column -->
		<div class="admin-edit" style="width:250px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<input id='showdbvers' type='checkbox' name='showdbvers' value='1' <?php echo ($showdbvers ?  " checked='checked'" : ""); ?>/>Show Database Versions
				&nbsp;<input type='submit' name='faction' value='Set' />
			</fieldset>
		</form>
		</div>
		
		<table class='admin-table'>
		<tr><th style='width:120px;'>Name</th><th style='width:120px;'>Value</th></tr>
		<?php
			//------------------------	
			$clist = $config->getList( ($showdbvers !== false ? false : DB_CFG_NAME." NOT LIKE 'dbtable%'") );
			if ($clist === false)
			{
				XLogError("Config admin page - getList failed");
				echo "<tr class='admin-row'><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
			}
			else
			{
				//------------------------
				foreach ($clist as $name => $value)
				{
					//------------------------
					if ($name == $selCfg)
					{
						$rowClass = 'admin-row-sel';
						$selValue = $value;
					}
					else 
						$rowClass = 'admin-row';
					//------------------------
					echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;cfg=".XEncodeHTML($name)."&amp;showdbvers=".($showdbvers ? "1" : "")."&amp;".SecToken()."';\">";
					echo "<td>".XEncodeHTML($name)."</td><td>".XEncodeHTML($value)."</td></tr>\n";
					//------------------------
				}
				//------------------------
			}
			//------------------------
			echo "</table>\n";		
			//------------------------	
		?>
		</div> <!-- End Left Column -->
		<?php 
		if ($Login->HasPrivilege(PRIV_CONFIG_MANAGE))
		{
		?>
		<div class="col1 pad_left1"><!-- Start Right Column -->
		<div class="admin-edit" style="width:250px;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style="font-weight:bold;font-size:120%;">Configuration:</legend>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<?php if ($selCfg != "") { ?>
					<input type="hidden" name="cfg" value="<?php echo $selCfg ?>" />
					<div>
						<label class="loginLabel" for="newValue"><?php echo $selCfg; ?></label><br/>
						<input style="margin-top: 8px; width:220px;" id="newValue" type="text" name="newValue" value="<?php echo XEncodeHTML($selValue);?>" maxlength="<?php echo C_MAX_CONFIG_VALUE_LENGTH;?>" />
						<br/>
					</div>
					<br/>
					<input type="submit" name="action" value="Set" />
					<br/>
					<br/>
					<!-- <br/>
					<input type="submit" name="action" value="Delete" />
					<br/>
					<br/> -->
				<?php 
				} // if ($selCfg != "") 
				?>
				<input style="margin-top: 8px; width:220px;" id="newCfg" type="text" name="newCfg" value="<?php echo XEncodeHTML($selCfg);?>" maxlength="<?php echo C_MAX_NAME_TEXT_LENGTH;?>" />
				<br/>
				<br/>
				<input type="submit" name="action" value="New" />
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
		<?php 
		} // if ($Login->HasPrivilege(PRIV_CONFIG_MANAGE))
		?>
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->
