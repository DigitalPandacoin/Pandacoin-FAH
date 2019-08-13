<?php
//---------------------------------
/*
 * include/pages/admin/Workers.php
 * 
 * 
*/
//---------------------------------
global $Login;
$Workers = new Workers() or die("Create object failed");
$Wallet = new Wallet() or die("Create object failed");
$Display = new Display() or die("Create object failed");
$pagenum = XGetPost('p');
$action = XPost('action');
$widx = XGetPost('idx');
if (!is_numeric($widx))
	$widx = 0;
//---------------------------------
$maxidx = XGetPost('maxidx');
if ($maxidx == "")
	$maxidx = -1;
//------------------------
$listSort = XGetPost('sort');
if (!is_numeric($listSort) || $listSort > 2)
	$listSort = 0;
//---------------------------------
$filterDisabled = XGetPost('fdis');
$filterDisabled = ($filterDisabled != "" ? true : false);
//---------------------------------
$filterInvalid = XGetPost('finv');
$filterInvalid = ($filterInvalid != "" ? true : false);
//---------------------------------
$onlyDisabled = XGetPost('odis');
$onlyDisabled = ($onlyDisabled != "" ? true : false);
//---------------------------------
$onlyInvalid = XGetPost('oinv');
$onlyInvalid = ($onlyInvalid != "" ? true : false);
//---------------------------------
if ($filterDisabled && $onlyDisabled)
	$filterDisabled = $onlyDisabled = false;
//---------------------------------
if ($filterInvalid && $onlyInvalid)
	$filterInvalid = $onlyInvalid = false;
//---------------------------------
$wID = "";
$wUserName = "";
$wAddress = "";
$wActivity = 0;
$wdtCreated = "";
$wdtUpdated = "";
$wUpdatedBy = "";
$wDisabled = false;
$wStatus = "";
//---------------------------------
?>
<div id="content"><!-- Start Container 1 -->
	<div class="under pad_bot1"><!-- Start Container 2 -->
	<br/>
	<div class="line1 wrapper pad_bot2"><!-- Start Container 3 -->
		<div class="col1" style="width: 850px;" ><!-- Start Left Column -->
		<div>
			<h1>Workers:</h1>
			<p>
			Folding @ Home worker list.
			</p>
			<br/>
		</div>
		<?php
			//------------------------
			if ($action == "Add")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_ADD.PRIV_WORKER_MANAGE))
				{
					//------------------------
					$wUserName 		= XPost('wName');
					$wAddress		= XPost('wAddr');
					//------------------------
					if ($wUserName == "" || strlen($wUserName) > C_MAX_NAME_TEXT_LENGTH)
					{
						XLogNotify("Admin Workers Add worker name is invalid");
						echo "Add worker failed. Worker name invalid or is too long.<br/>";
					}
					else if ($wAddress != "" && !$Wallet->isValidAddress($wAddress))
					{
						XLogNotify("Admin Workers Add worker wallet address '$wAddress' is invalid");
						echo "Add worker failed. The wallet address is not valid (it can be blank for now).<br/>";
					}
					else
					{
						//------------------------
						if (!$Workers->addWorker($wUserName, $wAddress, "(".$Login->UserID.")".$Login->UserName))
						{
							$widx = 0;
							XLogWarn("Admin Workers Add Workers addWorker failed");
							echo "Add worker failed.<br/>";
						}
						else
						{
							$widx = $maxidx+1;
							XLogNotify("Worker admin page - adding worker (uname: $wUserName, address: $wAddress) by $Login->UserName");
							echo "Added worker successfully.</br></br>\n";
						}
						//------------------------
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers Add worker doesn't have proper privileges: $Login->UserName");
					echo "Add worker failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Update")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
				{
					//------------------------
					$wUserName 		= XPost('wName');
					$wAddress		= XPost('wAddr');
					$wActivity		= XPost('wAct');
					//------------------------
					if (strlen($wUserName) > C_MAX_NAME_TEXT_LENGTH)
					{
						XLogNotify("Admin Workers Update worker name is invalid. (".strlen($wUserName)." of ".C_MAX_NAME_TEXT_LENGTH."): ".var_export($wUserName));
						echo "Update worker failed. Worker name contains invalid characters or is too long.<br/>";
					}
					else if ($wAddress != "" && !$Wallet->isValidAddress($wAddress))
					{
						XLogNotify("Admin Workers Update worker wallet address '$wAddress' is invalid");
						echo "Update worker failed. The wallet address is not valid (it can be blank for now).<br/>";
					}
					else if (!is_numeric($wActivity) || $wActivity < -4 || $wActivity > 4)
					{
						XLogNotify("Admin Workers Update worker wallet Activity Score '$wActivity' is invalid.");
						echo "Update worker failed. The wallet activity score is not valid (Integer inclusively between -4 and 4 required).<br/>";
					}
					else
					{
						//------------------------
						$w = $Workers->getWorker($widx);
						//------------------------
						if ($w === false)
						{
							$widx = 0;
							XLogWarn("Admin Workers Update Worker idx $widx not found failed");
							echo "Update worker failed. Worker not found.<br/>";
						}
						else
						{
							$w->uname = $wUserName;
							$w->address = $wAddress;
							$w->activity = $wActivity;
							if (!$w->Update("(".$Login->UserID.")".$Login->UserName))
							{

								XLogNotify("Worker admin page - Worker Update failed (idx: $widx, uname: $wUserName, address: $wAddress)");
								echo "Update worker failed.<br/></br>\n";
							}
							else
							{
								XLogNotify("Worker admin page - updating worker (idx: $widx, uname: $wUserName, address: $wAddress) by $Login->UserName");
								echo "Updated worker successfully.<br/></br>\n";
							}
						}
						//------------------------
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers Update worker doesn't have proper privileges: $Login->UserName");
					echo "Update worker failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Delete")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
				{
					//------------------------
					if (!$Workers->deleteWorker($widx))
					{
						$widx = 0;
						XLogWarn("Admin Workers Delete Workers deleteWorker $widx failed");
						echo "Delete worker failed.<br/>";
					}
					else
					{
						$widx = $maxidx+1;
						XLogNotify("Worker admin page - deleting worker ($widx) by $Login->UserName");
						echo "Deleted worker successfully.<br/></br>\n";
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers Delete worker doesn't have proper privileges: $Login->UserName");
					echo "Delete worker failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Enable" || $action == "Disable")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
				{
					//------------------------
					$w = $Workers->getWorker($widx);
					//------------------------
					if ($w === false)
					{
						$widx = 0;
						XLogWarn("Admin Workers $action Worker idx $widx not found failed");
						echo "$action worker failed. Worker not found.<br/>";
					}
					else
					{
						$w->disabled = ($action == "Disable" ? true : false);
						if (!$w->Update("(".$Login->UserID.")".$Login->UserName))
						{

							XLogNotify("Worker admin page - $action update worker failed (idx: $widx)");
							echo "$action worker failed.<br/></br>\n";
						}
						else
						{
							XLogNotify("Worker admin page - $action Updated worker (idx: $widx) by $Login->UserName");
							echo "Updated worker successfully.<br/></br>\n";
						}
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers $actione worker doesn't have proper privileges: $Login->UserName");
					echo "$action worker failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Reset All Address Validations")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
				{
					//------------------------
					if (!$Workers->resetAllAddressValidations())
					{

						XLogNotify("Worker admin page - Reset All Address Validations failed");
						echo "Reset All Address Validations failed.<br/></br>\n";
					}
					else
					{
						XLogNotify("Worker admin page - Reset All Address Validations by $Login->UserName");
						echo "Reset All Address Validations successfully.<br/></br>\n";
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers Reset All Address Validations doesn't have proper privileges: $Login->UserName");
					echo "Reset All Address Validations for workers failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Re-test All Invalid Addresses")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
				{
					//------------------------
					if (!$Workers->retestInvalidAddresses("(".$Login->UserID.")".$Login->UserName))
					{

						XLogNotify("Worker admin page - Workers retestInvalidAddresses failed");
						echo "Retest Invalid Addresses failed.<br/></br>\n";
					}
					else
					{
						XLogNotify("Worker admin page - Retest invalid Addresses by $Login->UserName");
						echo "Retested Invalid Addresses successfully.<br/></br>\n";
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers Retest Invalid Addresses doesn't have proper privileges: $Login->UserName");
					echo "Retest Invalid Addresses for workers failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			else if ($action == "Invalid all username address mismatches")
			{
				//------------------------
				if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
				{
					//------------------------
					if (!$Workers->invalidateAllUsernameAddressMismatches("(".$Login->UserID.")".$Login->UserName))
					{

						XLogNotify("Worker admin page - Workers invalidateAllUsernameAddressMismatches failed");
						echo "Retest Invalid Addresses failed.<br/></br>\n";
					}
					else
					{
						XLogNotify("Worker admin page - Invalidated missmateched username/addresses by $Login->UserName");
						echo "Invalidated missmateched username/addresses successfully.<br/></br>\n";
					}
					//------------------------
				} 
				else
				{
					XLogNotify("Admin Workers Invalidated missmateched username/addresses doesn't have proper privileges: $Login->UserName");
					echo "Invalidated missmateched username/addresses failed. Action requires proper privileges.<br/>";
				}
				//------------------------
			}
			//------------------------
			$Columns = array(	array(	"45px",		"ID", 				0),
								array(	"400px",	"Name", 			1),
								array(	"400px",	"Wallet Address", 	2),
								array(	"35px",		"Act", 				3)
								);
			//------------------------	
			?>
			<div>(Click row to select for editting or deleting. Click header to sort.)</div>
			<form action="./Admin.php" method="get" enctype="multipart/form-data">
				<fieldset class="loginBox">
					<?php PrintSecTokenInput(); ?>
					<input type="hidden" name="idx" value="<?php echo $widx; ?>" />
					<input type="hidden" name="maxidx" value="<?php echo $maxidx; ?>" />
					<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
					<input type='hidden' name='sort' value='<? echo $listSort; ?>' />
					<div style="padding: 5px;background:#AAAAAA;width:445px;">
					<div style="font-weight:bold;">List filters:</div>
					<div style="margin-left:5px;">
						<div style="float:left;margin-right: 10px;">
							<input id='fdis' type='checkbox' name='fdis' value='1' <?php echo ($filterDisabled ?  " checked='checked'" : ""); ?>/>Filter Disabled<br/>
							<input id='finv' type='checkbox' name='finv' value='1' <?php echo ($filterInvalid ?  " checked='checked'" : ""); ?>/>Filter Invalid Address
						</div>
						<div style="margin:auto;">
							<input id='odis' type='checkbox' name='odis' value='1' <?php echo ($onlyDisabled ?  " checked='checked'" : ""); ?>/>Only Disabled<br/>
							<input id='oinv' type='checkbox' name='oinv' value='1' <?php echo ($onlyInvalid ?  " checked='checked'" : ""); ?>/>Only Invalid Address
						</div>
					</div>
					<input type='submit' name='faction' value='Update' />
					</div>
				</fieldset>
			</form>
			<?php			
			//------------------------	
			echo "<table class='admin-table'>\n<thead class='admin-scrolltable-header'>\n<tr>\n";
			//------------------------	
			foreach ($Columns as $col)
			{
				$style = "";
				if ($col[0] !== false)
					$style .= "width:$col[0];max-width:$col[0];";
				if ($listSort == $col[2])
					$style .= "background:#EEEEEE;";
				echo "\t<th".($style != "" ? " style='$style'" : "");
				echo " onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;idx=$widx&amp;".SecToken();
				if ($filterDisabled) echo "&amp;fdis=1";
				if ($filterInvalid) echo "&amp;finv=1";
				echo "&amp;sort=".$col[2]."';\"";
				echo ">$col[1]</th>\n";
			}
			//------------------------	
			echo "</tr></thead>\n<tbody class='admin-scrolltable-body'>\n";
			//------------------------	
			if ($listSort == 1)
				$sort = "uname";
			else if ($listSort == 2)
				$sort = "address";
			else if ($listSort == 3)
				$sort = "activity";
			else
				$sort = false;
			//------------------------	
			if ($filterDisabled || $filterInvalid || $onlyDisabled || $onlyInvalid)
			{
				$filter = array();
				if ($filterDisabled) $filter['disabled'] = false;
				if ($onlyDisabled)  $filter['disabled'] = true;
				if ($filterInvalid) $filter['valid'] = true;
				if ($onlyInvalid) $filter['valid'] = false;
			}
			else $filter = false;
			//------------------------	
			//XLogDebug("Worker listSort: ".XVarDump($listSort).", sort: ".XVarDump($sort).", filtDisabled: ".($filterDisabled ? "true" : "false").", onlyDisabled: ".($onlyDisabled ? "true" : "false").", filtInvalid: ".($filterInvalid ? "true" : "false").", onlyInvalid: ".($onlyInvalid ? "true" : "false").", filter: ".XVarDump($filter));
			$wlist = $Workers->getWorkers($sort, $filter);
			if ($wlist === false)
				XLogError("Workers admin page - getWorkers failed");
			if ($wlist === false || sizeof($wlist) == 0)
			{
				//------------------------
				echo "<tr class='admin-row'>";
				foreach ($Columns as $col)
					echo "<td".($col[0] !== false ? " style='width:".$col[0].";'" : "").">&nbsp;</td>";
				echo "</tr>\n";
				//------------------------
			}
			else
			{
				//------------------------
				$rowIdx = 0;
				//------------------------
				foreach ($wlist as $w)
				{
					//------------------------
					if ( ($action == "Enable All" || $action == "Disable All") && $Login->HasPrivilege(PRIV_WORKER_MANAGE))
					{
						//------------------------
						$w->disabled = ($action == "Disable All" ? true : false);
						if (!$w->Update("(".$Login->UserID.")".$Login->UserName))
						{

							XLogNotify("Worker admin page - $action update worker failed (idx: $w->id)");
							echo "<br/></br>$action worker failed.<br/></br>\n";
						}
						//------------------------
					}
					//------------------------
					if ($w->id == $widx)
					{
						$rowClass = 'admin-row-sel';
						$wID = $w->id;
						$wUserName = $w->uname;
						$wAddress = $w->address;
						$wActivity = $w->activity;
						$wdtCreated = $Display->htmlLocalDateTime($w->dtCreated);
						$wdtUpdated = $Display->htmlLocalDateTime($w->dtUpdated);
						$wUpdatedBy = $w->updatedBy;
						$wDisabled = $w->disabled;
						if ($w->disabled)
							$wStatus = "Disabled";
						else
							$wStatus = "Ready";
						if ($w->validAddress)
							$wStatus .= ", address validated";
					}
					else 
						$rowClass = 'admin-row';
					//------------------------
					$colData = array(	$w->id,
										XEncodeHTML($w->uname),
										($w->address == "" ? "" : "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($w->address)."'>".XEncodeHTML($w->address)."</a>"),
										$w->activity
									);
					//------------------------
					echo "<tr class='$rowClass' onclick=\"document.location.href='./Admin.php?p=$pagenum&amp;idx=$w->id&amp;".SecToken();
					if ($filterDisabled) echo "&amp;fdis=1";
					if ($filterInvalid) echo "&amp;finv=1";
					if ($onlyDisabled) echo "&amp;odis=1";
					if ($onlyInvalid) echo "&amp;oinv=1";
					if ($listSort != 0) echo "&amp;sort=$listSort";
					echo "';\">";
					//------------------------
					for ($i = 0;$i < sizeof($colData);$i++)
					{
						$style = "";
						if (/*$rowIdx == 0 &&*/ $Columns[$i][0] !== false)
							$style .= "width:".$Columns[$i][0].";max-width: ".$Columns[$i][0].";";
						//if ($Columns[$i][1] == "Status")
						//	$style .= "text-align:right;";
						if ($style != "")
							$style = " style='$style'";
						echo "<td$style>".$colData[$i]."</td>";
					}
					//------------------------
					echo "</tr>\n";
					//------------------------
					$rowIdx++;
					//------------------------
				}
				//------------------------
			}
			//------------------------
			echo "</tbody></table>\n";		
			//------------------------	
		?>
		</div><!-- End Left Column -->
		<div class="col1 pad_left1"><!-- Start Right Column -->
		<div class="admin-edit" style="width:90%;">
		<form action="./Admin.php" method="post" enctype="multipart/form-data">
			<fieldset class="loginBox">
				<legend style="font-weight:bold;font-size:120%;"><?php echo ($widx != "" ? "Worker Details:" : "Add Worker:") ?></legend>
				<br/>
				<?php PrintSecTokenInput(); ?>
				<input type="hidden" name="idx" value="<?php echo $widx; ?>" />
				<input type="hidden" name="maxidx" value="<?php echo $maxidx; ?>" />
				<input type="hidden" name="p" value="<?php echo $pagenum; ?>"/><!-- Page -->
				<?php 
					//---------------------------
					if ($filterDisabled) echo "<input type='hidden' name='fdis' value='1' />\n";
					if ($filterInvalid) echo "<input type='hidden' name='finv' value='1' />\n";
					if ($onlyDisabled) echo "<input type='hidden' name='odis' value='1' />\n";
					if ($onlyInvalid) echo "<input type='hidden' name='oinv' value='1' />\n";
					if ($listSort != 0) echo "<input type='hidden' name='sort' value='$listSort' />\n";
					//---------------------------
					if ($widx != "")
					{
						//---------------------------
						$attributes = array(	'ID' => $wID,
												'User Name' => XEncodeHTML($wUserName),
												'Created' => $wdtCreated,
												'Updated' => $wdtUpdated.($wUpdatedBy != "" ? " by ".XEncodeHTML($wUpdatedBy) : ""),
												'Payout Address' => ($wAddress == "" || $wAddress == "x" ? $wAddress : "<a href='".BRAND_ADDRESS_LINK.XEncodeHTML($wAddress)."'>".XEncodeHTML($wAddress)."</a>"),
												'Status'  => $wStatus,
												'Activity Score' => $wActivity
											);
						//---------------------------
						echo "<table class='admin-table'>\n<tr><th style='width:120px;'>Attribute</th><th >Value</th></tr>\n";
						//---------------------------
						foreach ($attributes as $name => $value)
							echo "<tr class='admin-row'><td>$name</td><td><div style='overflow: auto;'>$value</div></td></tr>\n";
						//---------------------------
						echo "</table>\n";
						//---------------------------
					} // if ($widx != "")
					//---------------------------
					if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
					{
						
				?>
				<br/>
				<legend style="font-weight:bold;font-size:120%;">Add/Modify:</legend>
				<br/>
				<div>
					<label class="loginLabel" for="wName">User Name:</label>
					<input style="width:270px;" id="wName" type="text" name="wName" value="<?php echo XEncodeHTML($wUserName);?>" maxlength="<?php echo C_MAX_NAME_TEXT_LENGTH;?>" />
				</div>
				<br/>
				<div>
					<label class="loginLabel" for="wAddr">Payout Address:</label>
					<input style="width:270px;" id="wAddr" type="text" name="wAddr" value="<?php echo XEncodeHTML($wAddress);?>" maxlength="<? echo C_MAX_WALLET_ADDRESS_LENGTH;?>" />
				</div>
				<br/>
				<?php if ($widx != "") {?>
					<div>
						<label class="loginLabel" for="wAct">Activity Score (-4 to +4):</label>
						<input style="width:70px;" id="wAct" type="text" name="wAct" value="<?php echo XEncodeHTML($wActivity);?>" maxlength=5 />
					</div>
					<br/>
				<?php } // if ($widx != "") ?>
				<?php
						if ($widx != "" && $Login->HasPrivilege(PRIV_WORKER_MANAGE))
							echo "<input type='submit' name='action' value='".($wDisabled ? "Enable" : "Disable")."' />&nbsp;\n<br/>\n<br/>\n";
						if ($Login->HasPrivilege(PRIV_WORKER_ADD.PRIV_WORKER_MANAGE))
							echo "<input type='submit' name='action' value='Add' />&nbsp;\n";
						if ($Login->HasPrivilege(PRIV_WORKER_MANAGE))
						{
							if ($widx != "")
							{
								echo "<input type='submit' name='action' value='Update' />&nbsp;";
								echo "<input type='submit' name='action' value='Delete' />\n";
							}
							echo "<br/><br/><input type='submit' name='action' value='Reset All Address Validations' />&nbsp;\n<br/>\n<br/>\n";
							echo "<br/><input type='submit' name='action' value='Re-test All Invalid Addresses' /> (not disabled, includes incorrectly valid blank addresses)<br/>";
							echo "<br/>\n<div>(All listed)<br/>\n";
							echo "<input type='submit' name='action' value='Enable All' />&nbsp;";
							echo "<input type='submit' name='action' value='Disable All' /><br/>\n";
							echo "<br/>\n";
							echo "<input type='submit' name='action' value='Invalid all username address mismatches' /><br/>\n";
							echo "</div>\n";
						}
					} // hanging if from above name/address input
				?>
				<br/>
			</fieldset>
		</form>
		</div>
		<br/>
		</div><!-- End Right Column -->
	</div><!-- End Container 3 -->
	</div><!-- End Container 2 -->
</div><!-- End Container 1 -->
